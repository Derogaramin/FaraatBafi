# معماری سامانه

سامانه بر پایهٔ یک **هستهٔ سبک دست‌ساز** (بدون فریم‌ورک خارجی) و چهار الگوی معماری ترکیبی ساخته شده است:

1. **MVC** — تفکیک کنترلر / مدل / ویو در هر درخواست
2. **Modular + HMVC** — ۱۳ ماژول خودبسنده با سلسله‌مراتب لایه‌ها و زیردرخواست داخلی
3. **ED (رویدادمحور)** — جریان‌های کسب‌وکار با انتشار/شنود رویداد به هم متصل‌اند
4. **Service Layer** — منطق کسب‌وکار در سرویس‌ها متمرکز است، نه کنترلرها

```
HTTP Request
     │
     ▼
public/index.php ──► Core\App::boot()          (پیکربندی، خطاها، نشست، کشف ماژول‌ها)
     │                    │
     │                    ├─ Modules::registerRoutes()   ← routes.php هر ماژول
     │                    └─ Modules::registerEvents()   ← events.php هر ماژول
     ▼
Core\App::handle(Request)
     │
     ├─ Router::match()            → یافتن مسیر + استخراج پارامترها
     ├─ اجرای middlewareها          → auth / guest / csrf / role:M,S / can:perm
     ├─ Controller@action          → اعتبارسنجی + فراخوانی Service
     │        │
     │        ├─ Model / Database  → PDO با Prepared Statement
     │        ├─ Events::dispatch  → شنونده‌های ماژول‌ها (ED)
     │        └─ View::render      → قالب + لایه + کامپوننت‌ها
     ▼
Response::send()
```

## هسته (`core/`)

| کلاس | مسئولیت |
|---|---|
| `App` | بوت‌استرپ، dispatch مرکزی، مدیریت خطا، صفحات خطای برند |
| `Router` | مسیرهای `{param}` و `{id:int}`، گروه با پیشوند/میان‌افزار، مسیر نام‌گذاری‌شده |
| `Request` / `Response` | کپسوله‌سازی ورودی (params+query+post+files) و خروجی (html/json/redirect/svg) |
| `View` | موتور قالب PHP خالص: لایه (`extend`)، بخش‌ها (`start/end/section`)، کامپوننت‌ها، ویوی ماژول با `Module::path` |
| `Database` | PDO تنبل (Lazy Singleton)، تراکنش با Rollback خودکار |
| `QueryBuilder` / `ModelQuery` | کوئری روان؛ `Model::builder()` نتایج را به آبجکت مدل تبدیل می‌کند |
| `Model` | Active Record سبک: fillable، casts، timestamps، save/update/delete |
| `Auth` | ورود با کد/موبایل/ایمیل، نقش از پیشوند کد FUCS، `can()` با ماتریس `config/auth.php` |
| `Events` | گذرگاه رویداد: `on/dispatch/register` + ثبت خودکار در `activity_logs` |
| `Modules` | کشف ماژول‌ها، ثبت مسیر/رویداد/منو، `Modules::run()` برای HMVC |
| `CodeGenerator` | تولید همهٔ کدهای FUCS با `GET_LOCK` + جدول `code_counters` |
| `Jalali` | تبدیل و قالب‌بندی تاریخ شمسی (الگوریتم مرجع jalaali) |
| `Validator` | اعتبارسنجی با قواعد فارسی (phone ایرانی، کد ملی، تاریخ شمسی، unique/exists) |
| `Session` | نشست، فلش، old input، خطاها، توکن CSRF |
| `Upload` | آپلود امن تصویر (finfo، سقف حجم، نام یکتا) |
| `QR` | QR SVG با chillerlan/php-qrcode — محتوای QR «آدرس صفحهٔ اصالت» است |
| `Icons` | ~۶۰ آیکون SVG درون‌خطی (بدون CDN — کاملاً آفلاین) |
| `Statuses` | برچسب/رنگ فارسی همهٔ وضعیت‌های کسب‌وکاری |
| `Migrator` / `Seeder` | مهاجرت SQL شماره‌دار و دانه‌گذار |
| `Paginator` | صفحه‌بندی با لینک فارسی |
| `Enums\OrderStatus` / `Enums\WorkOrderStatus` | منبع حقیقت وضعیت‌ها: برچسب، رنگ، **گذارهای مجاز**، ستون زمانی (ADR-003) |
| `Business\OrderFlow` | تنها نقطهٔ تغییر وضعیت سفارش: گارد ماشین حالت + بازگشت موجودی لغو + رویدادها + ممیزی |
| `Audit` | ممیزی قبل/بعد فیلدهای حساس در `field_audits` (ADR-004) |
| `Throttle` | محدودسازی نرخ ورود — آستانهٔ دوگانهٔ شناسه/IP (ADR-009) |
| `Totp` | رمز یک‌بارمصرف RFC 6238 (بدون وابستگی) برای احراز دو مرحله‌ای (ADR-008) |
| `Sms` | انتزاع پیامک با درایور `log` (پیش‌فرض) و آمادهٔ درایور واقعی |
| `QueueWorker` | پردازش `event_queue` با retry=۳ — `php bin/faraat queue:work` (ADR-002) |

## ماژول‌ها (HMVC)

هر ماژول در `app/Modules/<Name>/` یک واحد خودبسنده است:

```
Marketplace/
├── module.json     ← شناسنامه: نام فارسی، آیکون، فاز، فعال/غیرفعال
├── routes.php      ← closure(Router) — مسیرهای عمومی و مدیریتی
├── events.php      ← شنونده‌های رویداد (ED)
├── menu.php        ← آیتم‌های منوی پنل‌ها (با فیلتر RBAC)
├── Controllers/    ← لایهٔ وب
├── Models/         ← لایهٔ داده (مالکیت جداول این دامنه)
├── Services/       ← منطق کسب‌وکار تراکنشی
└── Views/          ← قالب‌های ایزوله (admin./public./widgets.)
```

**قواعد بین‌ماژولی:**
- ماژول‌ها مدل‌های ماژول دیگر را **فقط خواندنی** مصرف می‌کنند (سطح داده اشتراکی).
- تعامل نوشتاری بین ماژول‌ها فقط از طریق **رویدادها** یا **سرویس‌ها** انجام می‌شود.
- ویجت مشترک (مثل زنگولهٔ اعلان) با `Modules::run('CRM','NotificationController','widget')`
  به‌صورت **زیردرخواست داخلی** (بدون HTTP) در لایهٔ پنل جاسازی می‌شود — نمونهٔ HMVC.
- منوی هر پنل از تجمیع `menu.php` همهٔ ماژول‌ها ساخته و با `Auth::can()` فیلتر می‌شود؛
  افزودن یک ماژول جدید = افزودن یک پوشه، بدون تغییر لایه‌ها.

## معماری رویدادمحور (ED)

رویدادها در `events.php` ماژول‌ها ثبت می‌شوند. جدول رویدادهای اصلی:

| رویداد | منتشرکننده | شنونده‌ها و اثر |
|---|---|---|
| `order.placed` | CheckoutService | CRM: اعلان به مشتری+مدیران؛ Production: — |
| `order.confirmed` | AdminOrderController | Production: ساخت WO برای اقلام، وضعیت سفارش → `in_production` |
| `order.ready` | ProductionService | CRM: اعلان «آمادهٔ ارسال» به مشتری |
| `order.status_changed` | AdminOrderController | CRM: اعلان تغییر وضعیت به مشتری |
| `workorder.assigned` | ProductionService | CRM: اعلان به کاربر بافنده |
| `workorder.delivered` | ProductionService | Production: اعلان بازرسی به مدیران |
| `workorder.qc_passed` | ProductionService | Production: صدور کدهای FB؛ Finance: فیش PAY + ماندهٔ بافنده |
| `payment.paid` | PayrollService | CRM: اعلان پرداخت به بافنده |
| `payment.issued` | PayrollService | (قابل توسعه) |
| `pos.sale` | PosService | (قابل توسعه: BI/مالی) |
| `ticket.replied` | AdminTicketController | CRM: اعلان به کاربر |
| `user.registered` / `weaver.created` / `agency.created` | UserService | (قابل توسعه) |

همهٔ رویدادها در `activity_logs` نیز ثبت می‌شوند (`app.log_events = true`) — ممیزی کامل.

## لایهٔ دسترسی (RBAC)

- نقش کاربر از **پیشوند کد FUCS** استنتاج می‌شود: `M`→member، `S`→staff، `W`→weaver، `A`→agency، `C`→customer.
- ماتریس مجوزها در `config/auth.php` — مثال: `'finance.view' => ['member']`.
- اعمال مجوز در سه سطح: middleware مسیر (`can:orders.view`)، کنترلر (`authorizeCan`)، منوها (`menu.php`).
- عضو اصلی (`M`) سوپرکاربر است.

## جریان یک درخواست نمونه (خرید مهمان)

1. `POST /checkout` → middleware `csrf` → `CheckoutController::place`
2. `Validator` فرم را می‌سنجد (خطا → فلش+old → ریدایرکت)
3. `CheckoutService::place` در یک تراکنش: مشتری `C` (findOrCreate) → کوپن → سفارش `ORD` → اقلام با اسکنپشوت → کسر انبار شرطی (`WHERE stock >= ?`) → فاکتور `INV` → حرکت انبار
4. `Events::dispatch('order.placed')` → اعلان‌ها
5. پاسخ: ریدایرکت به `/orders/{code}` با کد سفارش در فلش — طبق مستندات، **id + کد تولیدشده** همواره بازگردانده می‌شود.

## تصمیم‌های طراحی مهم

- **بدون ORM سنگین**: مدل‌ها نازک‌اند و پرس‌وجوهای گزارش‌گونه SQL صریح‌اند (خوانایی و کنترل).
- **تاریخ‌ها میلادی در DB، شمسی در نمایش**: تبدیل با `Jalali` در لبهٔ ویو؛ فیلترهای گزارش با `DATE_FORMAT` میلادی و برچسب شمسی.
- **کدها یکتا و قفل‌شده**: `GET_LOCK` + شمارندهٔ حوزه‌دار (سالانه/بافنده‌محور) → بدون برخورد حتی در اجرای همزمان.
- **CSS Variables برای تم**: یک `theme.css` با `:root` و `[data-theme="dark"]`؛ تغییر تم بدون رفرش و بدون FOUC (اسکریپت inline در `<head>`).
- **آیکون و فونت لوکال**: بدون وابستگی به CDN — سامانه کاملاً آفلاین کار می‌کند.
