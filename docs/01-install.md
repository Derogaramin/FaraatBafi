# راهنمای نصب و پیکربندی

## پیش‌نیازها

| نرم‌افزار | نسخهٔ حداقل | توضیح |
|---|---|---|
| PHP | 8.2 | با افزونه‌های `pdo_mysql`، `mbstring`، `json`، `gd` (برای آپلود تصویر) |
| MySQL / MariaDB | 5.7 / 10.4 | موتور InnoDB و `utf8mb4`؛ قیدهای CHECK با REGEXP استفاده می‌شوند |
| Composer | 2.x | نصب وابستگی‌ها |

## گام‌های نصب

### ۱) دریافت کد و وابستگی‌ها

```bash
composer install
```

وابستگی‌های پروژه کم و سبک‌اند:
- `chillerlan/php-qrcode` — تولید QR (خروجی SVG، بدون وابستگی باینری)
- `phpunit/phpunit` — فقط برای توسعه

### ۲) فایل محیطی

```bash
cp .env.example .env
```

| کلید | توضیح | نمونه |
|---|---|---|
| `APP_ENV` | `local` یا `production` | `local` |
| `APP_DEBUG` | نمایش جزئیات خطا (**فقط در توسعه**) | `1` |
| `APP_URL` | آدرس پایهٔ سامانه | `http://localhost:8080` |
| `PUBLIC_URL` | آدرس عمومی درج‌شده در QR اصالت | `https://faraat.ir` |
| `DB_HOST` / `DB_PORT` | میزبان پایگاه داده | `127.0.0.1` / `3306` |
| `DB_NAME` / `DB_USER` / `DB_PASS` | اعتبارنامهٔ پایگاه داده | — |

### ۳) ساخت پایگاه داده

```sql
CREATE DATABASE faraatbafi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'faraat'@'localhost' IDENTIFIED BY 'گذرواژه';
GRANT ALL PRIVILEGES ON faraatbafi.* TO 'faraat'@'localhost';
-- برای تست‌ها:
CREATE DATABASE faraatbafi_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON faraatbafi_test.* TO 'faraat'@'localhost';
```

### ۴) مهاجرت و دانه‌گذاری

```bash
php bin/faraat migrate        # ساخت ۴۲ جدول
php bin/faraat seed           # دادهٔ نمونه (کاربران، محصولات، سفارش‌ها…)
# یا هر دو با هم:
php bin/faraat fresh
```

### ۵) اجرا

```bash
php bin/faraat serve          # سرور توسعه روی پورت ۸۰۸۰
php bin/faraat serve 9000     # پورت دلخواه
```

## دستورات کنسول (`bin/faraat`)

| دستور | کار |
|---|---|
| `serve [port]` | اجرای سرور توسعه |
| `migrate` | اجرای مهاجرت‌های باقی‌مانده |
| `migrate:fresh` | حذف و ساخت مجدد همهٔ جداول |
| `seed` | درج دادهٔ نمونه (idempotent) |
| `fresh` | `migrate:fresh` + `seed` |
| `test` | اجرای PHPUnit |
| `routes` | فهرست همهٔ مسیرهای ثبت‌شده |
| `modules` | فهرست ماژول‌ها و وضعیتشان |
| `optimize` | ساخت کش مسیرهای کامپایل‌شده (production) |
| `optimize:clear` | حذف کش مسیرها (پس از تغییر routes.php) |
| `queue:work [--once]` | کارگر صف رویدادها (daemon یا تک‌اجرا برای cron) |

## نصب در زیرپوشه (اختیاری)

اگر سامانه زیر دامنهٔ روت نصب می‌شود (مثلاً `example.com/faraat`)، در `.env` مقدار
`APP_URL=https://example.com/faraat` را تنظیم کنید؛ `Request` به‌طور خودکار
`base_path` را از ابتدای مسیرها حذف می‌کند و همهٔ پیوندها با `url()` صحیح ساخته می‌شوند.

## نکات عیب‌یابی نصب

- **خطای اتصال به DB**: از ساخت کاربر با میزبان درست (`localhost` در برابر `127.0.0.1`) مطمئن شوید.
- **فونت‌ها بارگذاری نمی‌شوند**: فایل‌های `public/assets/fonts/*.woff2` باید موجود باشند (در مخزن هستند).
- **آپلود تصویر کار نمی‌کند**: `public/assets/uploads/` باید برای کاربر وب‌سرور نوشتنی باشد:
  `chmod -R 775 public/assets/uploads storage`
