#!/usr/bin/env bash
# ============================================================
# اسموک‌تست جریان‌های اصلی سامانهٔ فرت‌بافی
# نکته: توکن CSRF در هر نشست یک‌بار مصرف نیست، اما برای اطمینان
#       پیش از هر POST توکن از همان جار (نشست) تازه خوانده می‌شود.
# ============================================================
set -u
BASE="http://127.0.0.1:8080"
JAR="/tmp/faraat-cookies.txt"
PASS=0; FAIL=0
DB="mariadb faraatbafi -N -e"

get_token() { # استخراج توکن CSRF از متاتگ (در همهٔ چیدمان‌ها هست)
  curl -s -b "$JAR" -c "$JAR" "$1" | grep -o '<meta name="csrf-token" content="[^"]*"' | head -1 | sed 's/.*content="//;s/"//'
}

post() { # post <url> <data...> → کد وضعیت
  local url="$1"; shift
  local token; token=$(get_token "$url")
  curl -s -b "$JAR" -c "$JAR" -o /tmp/last-body.html -w '%{http_code}' -X POST "$url" --data-urlencode "_token=$token" "$@"
}

check() { if [ "$3" = "$2" ]; then PASS=$((PASS+1)); echo "  ✓ $1"; else FAIL=$((FAIL+1)); echo "  ✗ $1 — انتظار «$2»، مقدار «$3»"; fi; }
check_ne() { if [ -n "$3" ]; then PASS=$((PASS+1)); echo "  ✓ $1 ($3)"; else FAIL=$((FAIL+1)); echo "  ✗ $1 — مقدار خالی است"; fi; }
check_contains() {
  if [ -z "$3" ]; then FAIL=$((FAIL+1)); echo "  ✗ $1 — رشتهٔ انتظار خالی"; return; fi
  if grep -q -- "$3" "$2" 2>/dev/null; then PASS=$((PASS+1)); echo "  ✓ $1"; else FAIL=$((FAIL+1)); echo "  ✗ $1 — «$3» یافت نشد"; fi
}
q() { $DB "$1" 2>/dev/null; }

rm -f "$JAR"
echo "── ۱) خرید مهمان از بازارگاه ──────────────────"
SKU=$(q "SELECT sku FROM product_variants WHERE is_active=1 AND stock>0 ORDER BY id LIMIT 1")
STOCK0=$(q "SELECT stock FROM product_variants WHERE sku='$SKU'")
check_ne "SKU نمونه از پایگاه داده" "$SKU" "$SKU"

CODE=$(post "$BASE/cart/add" --data-urlencode "sku=$SKU" --data-urlencode "qty=2")
check "افزودن به سبد (۲ عدد)" "302" "$CODE"
CNT=$(curl -s -b "$JAR" -c "$JAR" "$BASE/cart" | grep -c -- "$SKU")
[ "$CNT" -ge 1 ] && { PASS=$((PASS+1)); echo "  ✓ قلم در سبد دیده می‌شود"; } || { FAIL=$((FAIL+1)); echo "  ✗ سبد خالی است"; }

LOC=$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{redirect_url}' -X POST "$BASE/checkout" \
  --data-urlencode "_token=$(get_token "$BASE/checkout")" \
  --data-urlencode "receiver=تست خرید" --data-urlencode "phone=09121234567" \
  --data-urlencode "province=خراسان رضوی" --data-urlencode "city=مشهد" \
  --data-urlencode "address=خیابان تست، کوچهٔ آزمون، پلاک ۱" --data-urlencode "postal_code=9137610000" \
  --data-urlencode "note=اسموک‌تست")
ORDER_URL=$(echo "$LOC" | sed -E 's#^https?://[^/]+##')
case "$ORDER_URL" in /orders/ORD-*) PASS=$((PASS+1)); echo "  ✓ ثبت سفارش → $ORDER_URL";;
  *) FAIL=$((FAIL+1)); echo "  ✗ ثبت سفارش — ریدایرکت نامعتبر: «$ORDER_URL»";; esac
ORDER_CODE=$(basename "$ORDER_URL")
ORDER_ID=$(q "SELECT id FROM orders WHERE code='$ORDER_CODE'")
check_ne "شناسهٔ سفارش در DB" "$ORDER_ID" "$ORDER_ID"

ST=$(q "SELECT status FROM orders WHERE id=$ORDER_ID")
check "وضعیت اولیه = pending" "pending" "$ST"
QTY=$(q "SELECT quantity FROM order_items WHERE order_id=$ORDER_ID AND sku='$SKU'")
check "تعداد قلم سفارش = ۲" "2" "$QTY"
STOCK1=$(q "SELECT stock FROM product_variants WHERE sku='$SKU'")
check "کسر موجودی انبار" "$((STOCK0 - 2))" "$STOCK1"
INV=$(q "SELECT code FROM invoices WHERE order_id=$ORDER_ID")
case "$INV" in INV-*) PASS=$((PASS+1)); echo "  ✓ فاکتور صادر شد: $INV";; *) FAIL=$((FAIL+1)); echo "  ✗ فاکتور صادر نشد";; esac
curl -s -b "$JAR" -c "$JAR" "$BASE/cart" -o /tmp/cart-after.html
check_contains "سبد پس از خرید خالی شد" /tmp/cart-after.html "سبد خرید شما خالی است"

curl -s -b "$JAR" -c "$JAR" "$BASE$ORDER_URL?phone=09121234567" -o /tmp/order-page.html
check_contains "صفحهٔ پیگیری کد سفارش دارد" /tmp/order-page.html "$ORDER_CODE"
check_contains "وضعیت «در انتظار تأیید» نمایش داده شد" /tmp/order-page.html "در انتظار تأیید"

echo "── ۲) ورود مدیر و داشبورد ─────────────────────"
rm -f "$JAR"
CODE=$(post "$BASE/login" --data-urlencode "identifier=M001" --data-urlencode "password=faraat1403")
check "ورود M001" "302" "$CODE"
CODE=$(curl -s -b "$JAR" -c "$JAR" -o /tmp/admin-dash.html -w '%{http_code}' "$BASE/admin")
check "داشبورد مدیر ۲۰۰" "200" "$CODE"
check_contains "کارت KPI بافندگان فعال" /tmp/admin-dash.html "بافندگان فعال"
check_contains "ویجت اعلان‌ها (HMVC)" /tmp/admin-dash.html "notif-popover"
check_contains "نمودار فروش" /tmp/admin-dash.html "bar-chart"

echo "── ۳) تأیید سفارش → تولید خودکار (ED) ─────────"
CODE=$(post "$BASE/admin/orders/$ORDER_ID/status" --data-urlencode "status=confirmed")
check "تغییر وضعیت به confirmed" "302" "$CODE"
sleep 1
WO_CODE=$(q "SELECT code FROM work_orders WHERE order_id=$ORDER_ID LIMIT 1")
check_ne "رویداد order.confirmed سفارش تولید ساخت" "$WO_CODE" "$WO_CODE"
ST=$(q "SELECT status FROM orders WHERE id=$ORDER_ID")
check "سفارش «در حال بافت» شد" "in_production" "$ST"

echo "── ۴) پنل بافنده و چرخهٔ بافت ─────────────────"
WO_ID=$(q "SELECT id FROM work_orders WHERE code='$WO_CODE'")
W_ID=$(q "SELECT id FROM weavers WHERE code='W001'")
CODE=$(post "$BASE/admin/production/$WO_ID/assign" --data-urlencode "weaver_id=$W_ID")
check "تخصیص بافنده توسط مدیر" "302" "$CODE"
NOTIF=$(q "SELECT COUNT(*) FROM notifications WHERE user_id=(SELECT user_id FROM weavers WHERE id=$W_ID)")
[ "$NOTIF" -ge 1 ] && { PASS=$((PASS+1)); echo "  ✓ اعلان تخصیص برای بافنده ثبت شد"; } || { FAIL=$((FAIL+1)); echo "  ✗ اعلان تخصیص ثبت نشد"; }

MAT_ID=$(q "SELECT id FROM materials LIMIT 1")
STOCK_M0=$(q "SELECT stock FROM materials WHERE id=$MAT_ID")
CODE=$(post "$BASE/admin/production/$WO_ID/materials" --data-urlencode "material_id=$MAT_ID" --data-urlencode "quantity=1.5" --data-urlencode "note=اسموک‌تست")
check "توزیع مادهٔ اولیه" "302" "$CODE"
STOCK_M1=$(q "SELECT stock FROM materials WHERE id=$MAT_ID")
EXPECT_M=$(awk -v a="$STOCK_M0" 'BEGIN{printf "%.3f", a-1.5}')
check "کسر ماده از انبار" "$EXPECT_M" "$STOCK_M1"

rm -f "$JAR"
CODE=$(post "$BASE/login" --data-urlencode "identifier=W001" --data-urlencode "password=faraat1403")
check "ورود بافنده W001" "302" "$CODE"
CODE=$(curl -s -b "$JAR" -c "$JAR" -o /tmp/weaver-dash.html -w '%{http_code}' "$BASE/weaver")
check "داشبورد بافنده ۲۰۰" "200" "$CODE"
check_contains "سفارش بافت در داشبورد بافنده" /tmp/weaver-dash.html "$WO_CODE"
check_contains "ناوبری پایین موبایل" /tmp/weaver-dash.html "bottom-nav"

CODE=$(post "$BASE/weaver/orders/$WO_ID/status" --data-urlencode "action=receive_materials")
check "بافنده: دریافت مواد" "302" "$CODE"
check "وضعیت = materials_received" "materials_received" "$(q "SELECT status FROM work_orders WHERE id=$WO_ID")"
CODE=$(post "$BASE/weaver/orders/$WO_ID/status" --data-urlencode "action=start_weaving")
check "بافنده: شروع بافت" "302" "$CODE"
check "وضعیت = weaving" "weaving" "$(q "SELECT status FROM work_orders WHERE id=$WO_ID")"
CODE=$(post "$BASE/weaver/orders/$WO_ID/status" --data-urlencode "action=deliver")
check "بافنده: تحویل به انبار" "302" "$CODE"
check "وضعیت = delivered" "delivered" "$(q "SELECT status FROM work_orders WHERE id=$WO_ID")"
PV_ID=$(q "SELECT variant_id FROM work_orders WHERE id=$WO_ID")
MV=$(q "SELECT COUNT(*) FROM inventory_movements WHERE ref_type='delivery' AND ref_id=$WO_ID")
check "ورود محصول به انبار ثبت شد" "1" "$MV"
# گذار غیرمجاز باید رد شود
CODE=$(post "$BASE/weaver/orders/$WO_ID/status" --data-urlencode "action=receive_materials")
check "گذار غیرمجاز → ریدایرکت با خطا (وضعیت عوض نشود)" "delivered" "$(q "SELECT status FROM work_orders WHERE id=$WO_ID")"

echo "── ۵) بازرسی کیفیت → اصالت + فیش دستمزد ──────"
rm -f "$JAR"
post "$BASE/login" --data-urlencode "identifier=S001" --data-urlencode "password=faraat1403" >/dev/null
CODE=$(post "$BASE/admin/production/$WO_ID/qc" --data-urlencode "result=passed" --data-urlencode "score=95" --data-urlencode "note=بافت عالی")
check "ثبت QC (قبول)" "302" "$CODE"
check "وضعیت WO = qc_passed" "qc_passed" "$(q "SELECT status FROM work_orders WHERE id=$WO_ID")"
QC=$(q "SELECT code FROM quality_inspections WHERE work_order_id=$WO_ID")
case "$QC" in QC-*) PASS=$((PASS+1)); echo "  ✓ کد بازرسی ساخته شد: $QC";; *) FAIL=$((FAIL+1)); echo "  ✗ کد بازرسی ساخته نشد";; esac
FB=$(q "SELECT code FROM authenticity_codes WHERE work_order_id=$WO_ID LIMIT 1")
check_ne "کد اصالت FB صادر شد" "$FB" "$FB"
PAY=$(q "SELECT code FROM payments WHERE work_order_id=$WO_ID")
case "$PAY" in PAY-W001-*) PASS=$((PASS+1)); echo "  ✓ فیش دستمزد صادر شد: $PAY";; *) FAIL=$((FAIL+1)); echo "  ✗ فیش دستمزد صادر نشد ($PAY)";; esac
BAL=$(q "SELECT balance FROM weavers WHERE id=$W_ID")
[ "$BAL" -gt 0 ] && { PASS=$((PASS+1)); echo "  ✓ ماندهٔ دستمزد بافنده: $BAL تومان"; } || { FAIL=$((FAIL+1)); echo "  ✗ ماندهٔ بافنده ثبت نشد"; }
ORD_READY=$(q "SELECT status FROM orders WHERE id=$ORDER_ID")
check "سفارش مشتری «آمادهٔ ارسال» شد" "ready" "$ORD_READY"

echo "── ۶) صفحهٔ اصالت عمومی با QR ─────────────────"
CODE=$(curl -s -o /tmp/fb.html -w '%{http_code}' "$BASE/authenticity/$FB")
check "صفحهٔ اصالت ۲۰۰" "200" "$CODE"
check_contains "QR در صفحهٔ اصالت" /tmp/fb.html "<svg"
check_contains "پیام تأیید اصالت" /tmp/fb.html "اصالت این محصول"
check_contains "نام بافنده در صفحهٔ اصالت" /tmp/fb.html "$(q "SELECT name FROM weavers WHERE code='W001'")"
CODE=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/authenticity/FB-9999-9999-W999")
check "کد اصالت جعلی → ۴۰۴" "404" "$CODE"
CODE=$(curl -s -o /tmp/qr.svg -w '%{http_code}' "$BASE/authenticity/$FB/qr")
check "خروجی SVG مستقیم QR" "200" "$CODE"

echo "── ۷) پنل نمایندگی و فروش حضوری (POS) ─────────"
rm -f "$JAR"
post "$BASE/login" --data-urlencode "identifier=A01" --data-urlencode "password=faraat1403" >/dev/null
check "داشبورد نمایندگی ۲۰۰" "200" "$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE/agency")"
check "صفحهٔ POS ۲۰۰" "200" "$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE/agency/pos")"
CSKU=$(q "SELECT pv.sku FROM consignments cs JOIN product_variants pv ON pv.id=cs.variant_id JOIN agencies a ON a.id=cs.agency_id WHERE a.code='A01' AND cs.quantity>0 AND pv.stock>0 LIMIT 1")
check_ne "کالای امانی موجود" "$CSKU" "$CSKU"
curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/agency/pos/add" -H 'X-Requested-With: XMLHttpRequest' \
  --data-urlencode "_token=$(get_token "$BASE/agency/pos")" --data-urlencode "sku=$CSKU" --data-urlencode "qty=1" -o /tmp/pos-add.json
check_contains "POS افزودن قلم" /tmp/pos-add.json '"ok": true'
B4=$(q "SELECT COUNT(*) FROM orders WHERE channel='agency'")
CODE=$(post "$BASE/agency/pos/sell" --data-urlencode "receiver=خریدار حضوری تست" --data-urlencode "phone=09155550000" --data-urlencode "city=تربت جام")
check "ثبت فروش حضوری" "302" "$CODE"
AF=$(q "SELECT COUNT(*) FROM orders WHERE channel='agency'")
check "سفارش نمایندگی اضافه شد" "$((B4 + 1))" "$AF"
COMM=$(q "SELECT code FROM commissions ORDER BY id DESC LIMIT 1")
case "$COMM" in COM-A01-*) PASS=$((PASS+1)); echo "  ✓ پورسانت صادر شد: $COMM";; *) FAIL=$((FAIL+1)); echo "  ✗ پورسانت صادر نشد ($COMM)";; esac
POS_ORD=$(q "SELECT id FROM orders WHERE channel='agency' ORDER BY id DESC LIMIT 1")
check "سفارش POS تحویل‌شده است" "delivered" "$(q "SELECT status FROM orders WHERE id=$POS_ORD")"
check "فاکتور POS پرداخت‌شده" "1" "$(q "SELECT paid FROM invoices WHERE order_id=$POS_ORD")"

echo "── ۸) پنل مشتری و تیکت ────────────────────────"
rm -f "$JAR"
post "$BASE/login" --data-urlencode "identifier=C000001" --data-urlencode "password=faraat1403" >/dev/null
check "میز کار مشتری ۲۰۰" "200" "$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE/my")"
check "تیکت‌های من ۲۰۰" "200" "$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE/my/tickets")"
B4T=$(q "SELECT COUNT(*) FROM tickets")
CODE=$(post "$BASE/my/tickets" --data-urlencode "subject=تست تیکت اسموک" --data-urlencode "body=این یک تیکت آزمایشی برای بررسی جریان پشتیبانی است." --data-urlencode "category=other" --data-urlencode "priority=normal")
check "ثبت تیکت" "302" "$CODE"
AFT=$(q "SELECT COUNT(*) FROM tickets")
check "تیکت در DB اضافه شد" "$((B4T + 1))" "$AFT"
TKT=$(q "SELECT code FROM tickets ORDER BY id DESC LIMIT 1")
case "$TKT" in TKT-*) PASS=$((PASS+1)); echo "  ✓ کد تیکت: $TKT";; *) FAIL=$((FAIL+1)); echo "  ✗ کد تیکت نامعتبر";; esac

echo "── ۹) کنترل دسترسی (RBAC) ──────────────────────"
check "مشتری → /admin = ۴۰۳" "403" "$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE/admin")"
check "مشتری → /weaver = ۴۰۳" "403" "$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE/weaver")"
check "مشتری → /agency = ۴۰۳" "403" "$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE/agency")"
rm -f "$JAR"
check "مهمان → /my = ریدایرکت به ورود" "302" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/my")"
check "مهمان → /admin = ریدایرکت به ورود" "302" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/admin")"

echo "── ۱۰) امنیت و اعتبارسنجی ─────────────────────"
check "POST بدون توکن CSRF = ۴۱۹" "419" "$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/cart/add" -d 'sku=X&qty=1')"
rm -f "$JAR"
CODE=$(post "$BASE/login" --data-urlencode "identifier=M001" --data-urlencode "password=wrongpass")
check "گذرواژهٔ نادرست → ریدایرکت به فرم ورود" "302" "$CODE"
CODE=$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE/admin")
check "با ورود ناموفق، /admin همچنان ممنوع است" "302" "$CODE"
LOGGED=$(curl -s -b "$JAR" -c "$JAR" "$BASE/login" | grep -c 'پنل من')
check "کاربر واردنشده در /login می‌ماند" "0" "$LOGGED"

echo ""
echo "═══════════════════════════════════════"
echo " نتیجه: $PASS موفق، $FAIL ناموفق"
echo "═══════════════════════════════════════"
[ "$FAIL" -eq 0 ]
