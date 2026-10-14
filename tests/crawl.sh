#!/usr/bin/env bash
# خزش روی همهٔ صفحات GET سامانه با نقش‌های مختلف — شکار خطاهای ۵۰۰
BASE="http://127.0.0.1:8080"
JAR="/tmp/crawl-jar.txt"
FAIL=0; TOTAL=0

get_token() { curl -s -b "$JAR" -c "$JAR" "$1" | grep -o '<meta name="csrf-token" content="[^"]*"' | head -1 | sed 's/.*content="//;s/"//'; }
login() {
  rm -f "$JAR"
  curl -s -b "$JAR" -c "$JAR" -o /dev/null -X POST "$BASE/login" \
    --data-urlencode "_token=$(get_token "$BASE/login")" \
    --data-urlencode "identifier=$1" --data-urlencode "password=faraat1403"
}
crawl() { # crawl <label> <urls...>
  local label="$1"; shift
  for u in "$@"; do
    TOTAL=$((TOTAL+1))
    CODE=$(curl -s -b "$JAR" -c "$JAR" -o /tmp/crawl-body.html -w '%{http_code}' "$BASE$u")
    if [ "$CODE" != "200" ]; then FAIL=$((FAIL+1)); echo "  ✗ [$CODE] $u"; else echo "  ✓ $u"; fi
  done
}

q() { mariadb faraatbafi -N -e "$1"; }
SKU=$(q "SELECT sku FROM product_variants WHERE is_active=1 LIMIT 1")
WO_ID=$(q "SELECT id FROM work_orders ORDER BY id LIMIT 1")
ORD_ID=$(q "SELECT id FROM orders ORDER BY id LIMIT 1")
ORD_CODE=$(q "SELECT code FROM orders ORDER BY id LIMIT 1")
WV_ID=$(q "SELECT id FROM weavers ORDER BY id LIMIT 1")
AG_ID=$(q "SELECT id FROM agencies ORDER BY id LIMIT 1")
CU_ID=$(q "SELECT id FROM customers ORDER BY id LIMIT 1")
FB=$(q "SELECT code FROM authenticity_codes LIMIT 1")
TKT_ID=$(q "SELECT id FROM tickets LIMIT 1")
MT_ID=$(q "SELECT id FROM meetings LIMIT 1")
WO_ORD=$(q "SELECT id FROM work_orders WHERE weaver_id=(SELECT id FROM weavers WHERE code='W001') LIMIT 1")
MY_ORD=$(q "SELECT id FROM orders WHERE user_id=(SELECT id FROM users WHERE code='C000001') LIMIT 1")

echo "═══ مهمان ═══"
rm -f "$JAR"
crawl guest "/" "/marketplace" "/marketplace?type=HT" "/marketplace?q=%D8%AD%D9%88%D9%84%D9%87" "/marketplace/product/$SKU" \
  "/cart" "/orders/track" "/authenticity" "/authenticity/$FB" "/blog" "/about" "/contact" "/login" "/register" "/forgot-password" \
  "/search" "/search?q=%D8%AD%D9%88%D9%84%D9%87" "/cart/drawer"
BLOG_SLUG=$(q "SELECT slug FROM posts WHERE status='published' LIMIT 1")
crawl guest "/blog/$BLOG_SLUG"

echo "═══ مدیر (M001) ═══"
login M001
crawl admin "/admin" "/admin/orders" "/admin/orders/$ORD_ID" \
  "/admin/catalog/variants" "/admin/catalog/variants/create" "/admin/catalog/models" "/admin/catalog/models/create" \
  "/admin/catalog/colors" "/admin/catalog/patterns" "/admin/catalog/patterns/create" "/admin/catalog/types" \
  "/admin/production" "/admin/production/create" "/admin/production/$WO_ID" \
  "/admin/inventory/materials" "/admin/inventory/movements" "/admin/inventory/defects" \
  "/admin/weavers" "/admin/weavers/create" "/admin/weavers/$WV_ID" "/admin/weavers/$WV_ID/edit" \
  "/admin/agencies" "/admin/agencies/create" "/admin/agencies/$AG_ID" \
  "/admin/customers" "/admin/customers/$CU_ID" \
  "/admin/hr/members" "/admin/hr/staff" \
  "/admin/finance/payments" "/admin/finance/commissions" "/admin/finance/reports" "/admin/finance/documents" \
  "/admin/blog" "/admin/blog/create" "/admin/tickets" "/admin/tickets/$TKT_ID" \
  "/admin/bi" "/admin/bi/weavers" \
  "/admin/governance/meetings" "/admin/governance/meetings/$MT_ID" "/admin/governance/votes" "/admin/governance/letters" \
  "/admin/settings" "/notifications" "/my/profile" \
  "/admin/security" "/admin/audits" "/admin/orders/$ORD_ID/print"

PAY_ID=$(q "SELECT id FROM payments LIMIT 1")
crawl admin "/admin/finance/payments/$PAY_ID/print"
TOTAL=$((TOTAL+1))
CODE=$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE/search?q=$ORD_CODE")
if [ "$CODE" = "302" ]; then PASS=$((PASS+1)); echo "  ✓ جست‌وجوی کد سفارش → پرش مستقیم (۳۰۲)"; else FAIL=$((FAIL+1)); echo "  ✗ [$CODE] /search?q=$ORD_CODE باید ۳۰۲ می‌شد"; fi

echo "═══ مدیر اجرایی (S001) — بدون دسترسی مالی/حاکمیت ═══"
login S001
crawl staff "/admin" "/admin/orders" "/admin/production" "/admin/blog"
for u in "/admin/finance/commissions" "/admin/governance/votes"; do
  TOTAL=$((TOTAL+1)); CODE=$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE$u")
  if [ "$CODE" = "403" ]; then echo "  ✓ [403] $u (ممنوع برای S — درست)"; else FAIL=$((FAIL+1)); echo "  ✗ [$CODE] $u باید ۴۰۳ می‌شد"; fi
done

echo "═══ بافنده (W001) ═══"
login W001
crawl weaver "/weaver" "/weaver/orders" "/weaver/orders/$WO_ORD" "/weaver/materials" "/weaver/payments" "/weaver/profile" "/notifications"

echo "═══ نمایندگی (A01) ═══"
login A01
crawl agency "/agency" "/agency/pos" "/agency/consignment" "/agency/commissions" "/agency/orders" "/notifications"

echo "═══ مشتری (C000001) ═══"
login C000001
crawl customer "/my" "/my/orders" "/my/orders/$MY_ORD" "/my/profile" "/my/tickets" "/my/tickets/create" "/my/tickets/$TKT_ID" "/notifications"

echo "═══ خطاها ═══"
rm -f "$JAR"
for u in "/no-such-page" "/marketplace/product/FAKE-SKU" "/orders/ORD-9999-999999"; do
  TOTAL=$((TOTAL+1)); CODE=$(curl -s -o /dev/null -w '%{http_code}' "$BASE$u")
  if [ "$CODE" = "404" ]; then echo "  ✓ [404] $u"; else FAIL=$((FAIL+1)); echo "  ✗ [$CODE] $u باید ۴۰۴ می‌شد"; fi
done

echo ""
echo "═══════════════════════════════════"
echo " خزیدن: $TOTAL صفحه — خطا: $FAIL"
echo "═══════════════════════════════════"
[ "$FAIL" -eq 0 ]
