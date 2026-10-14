# ساختار پایگاه داده

۴۵ جدول در ۷ مهاجرت شماره‌دار (`database/migrations/`) با موتور **InnoDB**،
نویسهٔ **utf8mb4_unicode_ci**، کلیدهای خارجی صریح و **قید CHECK با REGEXP**
برای همهٔ ستون‌های کد (اعتبارسنجی در سطح پایگاه داده طبق مستندات FUCS).

## نقشهٔ دامنه‌ها

```
۰۰۱ identity   → users, members, staff, weavers, agencies, customers, code_counters
۰۰۲ catalog    → product_types, colors, patterns, product_models, product_variants
۰۰۳ sales      → coupons, orders, order_items, invoices, receipts, shipments, returns
۰۰۴ production → materials, work_orders, material_distributions, inventory_movements,
                 defect_types, quality_inspections, authenticity_codes, consignments
۰۰۵ finance    → payments, commissions, accounting_documents
۰۰۶ platform   → posts, tickets, ticket_replies, notifications, meetings, resolutions,
                 votes, vote_ballots, internal_letters, activity_logs, settings, migrations
۰۰۷ security   → login_attempts, field_audits, event_queue
                 + ستون‌های totp_secret/must_change_password روی users
                 + هفت ایندکس مرکب روی پرس‌وجوهای داغ
```

> **نکتهٔ منطقهٔ زمانی (ADR-007)**: اتصال PDO بلافاصله `SET time_zone` را با
> offset منطقهٔ زمانی PHP هم‌تراز می‌کند؛ در غیر این صورت مقایسهٔ `NOW()` با
> زمان‌های سمت PHP (مثل پنجرهٔ Throttle یا KPI «سفارش‌های امروز») دچار خطا می‌شود.

## جدول‌های کلیدی

### users — کاربر واحد برای همهٔ نقش‌ها
`code` یکتا با پیشوند نقش (M/S/W/A/C) — نقش از همین پیشوند استنتاج می‌شود.
پروفایل تخصصی هر نقش در جدول خودش با `user_id` قرار دارد.

### product_models — مدل فنی (لایهٔ اول کدگذاری محصول)
`code` مثل `HT-2LBSTD-CH125` + ستون‌های تفکیک‌شدهٔ `weave/knot/stitch/chelle`.
**تفکیک دغدغه‌ها**: مدل فنی از رنگ و طرح جداست؛ تغییر طرح هرگز کد مدل را عوض نمی‌کند.

### product_variants — SKU (لایهٔ فروشگاهی)
`sku = model-color-pattern` (UNIQUE) + ترکیب `(model_id,color_id,pattern_id)` نیز UNIQUE.
`stock` و `reserved` برای مدیریت موجودی؛ کسر موجودی در تراکنش با شرط `stock >= qty`.

### work_orders — سفارش تولید
ماشین حالت وضعیت‌ها در `WorkOrder::TRANSITIONS`:
`draft → assigned → materials_received → weaving → delivered → qc_passed|qc_rejected → completed`
ستون‌های زمانی هر گذار (`assigned_at … completed_at`) برای تایم‌لاین و BI.

### authenticity_codes — کد اصالت مشتری‌محور
`code` مثل `FB-1405-0012-W001` + `qr_url` که **آدرس صفحهٔ اصالت** است (نه کد خام).
`status`: reserved → sold → returned/void؛ اتصال به variant، work_order، weaver و order.

### orders / order_items
`code` مثل `ORD-1405-000125`؛ اقلام **اسکنپشوت** عنوان/قیمت/SKU را در لحظهٔ خرید نگه می‌دارند
(تغییر کاتالوگ، اسناد فروش قدیمی را عوض نمی‌کند). `channel`: web/agency/phone/admin.

### code_counters — موتور کدگذاری
`(scope PK, last_value)` — حوزه‌ها مثل `order.1405` یا `payment.W045.1405`.
تولید کد: `GET_LOCK('fucs_'+md5(scope))` → `SELECT … FOR UPDATE` → افزایش → `RELEASE_LOCK`
همه داخل تراکنش؛ بدون برخورد در اجرای همزمان.

### inventory_movements — دفتر حرکت انبار
هر ورود/خروج مواد یا محصول با `ref_type` (purchase/distribution/delivery/sale/return/adjust/consignment)
و `ref_id` ثبت می‌شود → ردیابی کامل و گزارش‌پذیری.

## روابط اصلی (ER خلاصه)

```
users 1──1 members / staff / weavers / agencies / customers
product_types 1──N product_models N──1 product_variants (با colors و patterns)
orders 1──N order_items N──1 product_variants
orders 1──N work_orders N──1 weavers
work_orders 1──N material_distributions N──1 materials
work_orders 1──N quality_inspections N──0..1 defect_types
work_orders 1──N authenticity_codes N──1 weavers
weavers 1──N payments | agencies 1──N commissions | orders 1──1 invoices 1──N receipts
tickets 1──N ticket_replies | votes 1──N vote_ballots | meetings 1──N resolutions
```

## قواعد نام‌گذاری

- جداول: جمع snake_case (`work_orders`)؛ ستون‌ها snake_case.
- همهٔ ستون‌های `*_code` دارای **UNIQUE INDEX** (الزام مستندات).
- زمان‌ها: `*_at` (DATETIME) و روزها: `*_date` (DATE) — همیشه میلادی در DB.
- مبالغ: `BIGINT UNSIGNED` به **تومان** (بدون اعشار ریالی).
- مقدارهای شمارشی انبار: `DECIMAL(12,3)` (پنبه/نخ با وزن دقیق).
