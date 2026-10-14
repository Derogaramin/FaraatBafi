# راهنمای ماژول‌ها

۱۳ ماژول فعال، هرکدام خودبسنده با `module.json`، `routes.php`، و در صورت نیاز `events.php` و `menu.php`.

| ماژول | فاز | نقش | کنترلرهای اصلی |
|---|---|---|---|
| **Home** | ۱ | صفحهٔ فرود، درباره، تماس | HomeController |
| **Auth** | ۱ | ورود/ثبت‌نام/خروج/بازیابی | AuthController |
| **Marketplace** | ۱ | بازارگاه عمومی + مدیریت سفارش‌ها | Catalog, Cart, Checkout, Tracking, Authenticity, AdminOrder |
| **Catalog** | ۱ | انواع/مدل فنی/رنگ/طرح/SKU | AdminType, AdminModel, AdminColor, AdminPattern, AdminVariant |
| **Production** | ۱ | سفارش بافت، مواد، QC، انبار | AdminProduction, AdminInventory |
| **Members** | ۱ | بافندگان/نمایندگی‌ها/مشتریان/اعضا/پرسنل | AdminWeaver, AdminAgency, AdminCustomer, AdminMember |
| **Weaver** | ۱ | پنل بافنده (موبایل‌محور) | WeaverController |
| **Agency** | ۲ | پنل نمایندگی + POS | AgencyController |
| **Account** | ۱ | پنل مشتری | AccountController |
| **Finance** | ۲ | دستمزد/پورسانت/اسناد/گزارش | AdminFinanceController |
| **Blog** | ۱ | وبلاگ عمومی + مدیریت محتوا | Blog, AdminPost |
| **CRM** | ۱ | تیکت‌ها + اعلان‌ها | Ticket, AdminTicket, Notification |
| **Admin** | ۱/۳ | داشبورد، BI، حاکمیت، تنظیمات | Dashboard, Bi, Governance, Settings |

## نقشهٔ مسیرها (خلاصه)

### عمومی (بدون ورود)
```
GET  /                              صفحهٔ فرود
GET  /marketplace                   کاتالوگ + فیلتر (?type=&color=&pattern=&q=&sort=&stock=1&page=)
GET  /marketplace/product/{sku}     جزئیات محصول
GET  /cart  |  POST /cart/add|update|remove|clear
GET  /checkout  |  POST /checkout   پرداخت مهمان/واردشده + کوپن
GET  /orders/track  |  POST …       پیگیری با کد ORD + موبایل
GET  /orders/{code}                 وضعیت سفارش (با ?phone= یا ورود مالک)
GET  /authenticity[/{code}]         استعلام اصالت + صفحهٔ QR
GET  /authenticity/{code}/qr        خروجی SVG مستقیم QR
GET  /blog  |  /blog/{slug}         وبلاگ
GET  /about | /contact | POST /contact
GET  /login | /register | /forgot-password | POST … | POST /logout
GET|POST /verify-2fa                     رمز یک‌بارمصرف (TOTP)
GET  /search?q=…                         جست‌وجوی جهانی — کد FUCS = پرش مستقیم
GET  /cart/drawer                        محتوای سبد کشویی (AJAX)
GET  /notifications/unread-count         polling نشانگر اعلان‌ها (JSON)
```

### پنل مدیر `/admin/*` (نقش M/S با مجوز RBAC)
```
GET  /admin                         داشبورد KPI + نمودار + هشدارها
GET  /admin/orders[/{id}]           سفارش‌ها — POST status|ship|pay
GET  /admin/catalog/…               types | models | colors | patterns | variants (+sku-preview)
GET  /admin/production[/{id}]       WOها — POST assign|status|materials|qc|authenticity
GET  /admin/inventory/…             materials (+adjust) | movements | defects
GET  /admin/weavers[/{id}|/create]  بافندگان — POST store|update|status
GET  /admin/agencies[/{id}]         نمایندگی‌ها — POST consign
GET  /admin/customers[/{id}]        مشتریان
GET  /admin/hr/members|staff        اعضا و پرسنل (M)
GET  /admin/finance/…               payments (+pay) | commissions (M) | reports | documents (M)
GET  /admin/blog[/{id}/edit]        محتوا
GET  /admin/tickets[/{id}]          پشتیبانی — POST reply|status
GET  /admin/bi | /admin/bi/weavers  هوش تجاری
GET  /admin/governance/…            meetings | votes (M) | letters (M)
GET  /admin/settings                تنظیمات (M)
GET  /admin/security                امنیت حساب: 2FA + تاریخچهٔ ورود
GET  /admin/audits                  ممیزی تغییرات فیلدهای حساس
GET  /admin/orders/{id}/print       فاکتور چاپی A4
GET  /admin/orders/export           خروجی CSV (با فیلترهای جاری)
GET  /admin/finance/payments/{id}/print   فیش دستمزد چاپی
```

> POSTهای حساس (اسناد مالی، پورسانت، جلسات/مصوبات/رأی/نامه، تنظیمات) علاوه بر
> `csrf` میان‌افزار `2fa` دارند: اعضای ثبت‌نام‌نکرده به `/admin/security` هدایت می‌شوند.

### پنل بافنده `/weaver/*` (نقش W — موبایل‌محور)
```
GET  /weaver                        داشبورد: WOهای فعال + شمارش معکوس + آمار
GET  /weaver/orders[/{id}]          فهرست و جزئیات + نوار پیشرفت
POST /weaver/orders/{id}/status     action: receive_materials | start_weaving | deliver
GET  /weaver/materials              مواد دریافتی (تجمیع + جزئیات)
GET  /weaver/payments               فیش‌های دستمزد (پرداخت‌شده/معوق)
GET  /weaver/profile                پروفایل و شناسهٔ بافندگی
```

### پنل نمایندگی `/agency/*` (نقش A)
```
GET  /agency                        داشبورد فروش منطقه‌ای
GET  /agency/pos                    فروش حضوری (اسکن SKU)
POST /agency/pos/add|sell|clear     افزودن قلم / ثبت فروش / تخلیه
GET  /agency/consignment            کالای امانی
GET  /agency/commissions            پورسانت‌ها (COM)
GET  /agency/orders                 سفارش‌های نمایندگی
```

### پنل کاربر `/my/*` (همهٔ نقش‌های واردشده)
```
GET  /my                            میز کار مشتری
GET  /my/orders[/{id}]              سفارش‌های من
GET  /my/profile | POST …           پروفایل + تغییر گذرواژه
GET  /my/tickets[…]                 تیکت‌ها (create/show/reply)
GET  /notifications                 اعلان‌ها — POST read-all
```

## افزودن ماژول جدید (چک‌لیست)

1. پوشهٔ `app/Modules/NewModule/` با `module.json`
2. `routes.php` — closure که `Router` را ثبت می‌کند
3. کنترلرها در `Controllers/` (ارث از `Core\Controller`)
4. مدل‌ها در `Models/` (ارث از `Core\Model`)
5. ویوها در `Views/` (نام‌گذاری `admin.*` برای پنل‌ها)
6. در صورت نیاز: `events.php` (شنونده‌ها) و `menu.php` (آیتم منو با `permission`)
7. هیچ ثبت دستی لازم نیست — `Modules::boot()` خودکار کشف می‌کند

## غیرفعال‌سازی ماژول

در `module.json` مقدار `"enabled": false` — مسیرها، منوها و رویدادهایش ثبت نمی‌شوند.
