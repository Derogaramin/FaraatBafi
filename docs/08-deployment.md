# استقرار در محیط عملیاتی

## پیش‌نیازهای سرور

- PHP-FPM 8.2 + Nginx (یا Apache با mod_rewrite)
- MySQL 8 / MariaDB 10.6+
- HTTPS (الزامی برای کوکی نشست امن)

## گام‌ها

### ۱) کد و وابستگی‌ها

```bash
git clone <repo> /var/www/faraatbafi && cd /var/www/faraatbafi
composer install --no-dev --optimize-autoloader
```

### ۲) محیط

```bash
cp .env.example .env
```
```ini
APP_ENV=production
APP_DEBUG=0                       # بسیار مهم: جزئیات خطا به کاربر نشان داده نشود
APP_URL=https://faraat.ir
PUBLIC_URL=https://faraat.ir      # آدرس درج‌شده در QR اصالت
DB_HOST=127.0.0.1
DB_NAME=faraatbafi
DB_USER=faraat
DB_PASS=<گذرواژهٔ قوی>
```

### ۳) پایگاه داده

```bash
mysql -u root -p -e "CREATE DATABASE faraatbafi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'faraat'@'localhost' IDENTIFIED BY '<گذرواژه>'; GRANT ALL ON faraatbafi.* TO 'faraat'@'localhost';"
php bin/faraat migrate
# seed فقط برای محیط آزمایشی است؛ در عملیات از php bin/faraat seed استفاده نکنید
```

### ۴) مجوزها

```bash
chown -R www-data:www-data storage public/assets/uploads
chmod -R 775 storage public/assets/uploads
chmod 640 .env
```

### ۵) Nginx (نمونه)

```nginx
server {
    listen 443 ssl http2;
    server_name faraat.ir;
    root /var/www/faraatbafi/public;
    index index.php;

    # هدرهای امنیتی
    add_header X-Frame-Options SAMEORIGIN always;
    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # جلوگیری از دسترسی به فایل‌های حساس
    location ~ /\. { deny all; }

    # کش دارایی‌ها
    location ~* \.(css|js|woff2|svg|jpg|png|webp)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

برای Apache، فایل `public/.htaccess` آماده است (mod_rewrite + هدرهای امنیتی + فشرده‌سازی + کش).

### ۶) PHP-FPM

```ini
; /etc/php/8.2/fpm/conf.d/99-faraat.ini
upload_max_filesize = 4M
post_max_size = 8M
memory_limit = 256M
max_execution_time = 60
```

### ۷) بهینه‌سازی و کارگر صف

```bash
php bin/faraat optimize          # کش مسیرها (پس از هر تغییر routes.php: optimize:clear)
```

```cron
# کارگر صف رویدادها هر دقیقه (یا به‌صورت daemon با supervisor/systemd)
* * * * * cd /var/www/faraatbafi && php bin/faraat queue:work --once >> storage/logs/queue.log 2>&1
```

### ۸) استقرار با Docker

```bash
docker compose up -d --build     # app (php-fpm) + web (nginx:8081) + db (mariadb) + queue worker
docker compose exec app php bin/faraat migrate
```

فایل‌ها: `Dockerfile`، `docker-compose.yml`، `docker/nginx.conf`.
متغیرهای محیطی `DB_PASS` و `DB_ROOT_PASS` را در `.env` یا environment ست کنید.

### ۹) پس از استقرار

1. با حساب عضو اصلی وارد شوید و `admin/settings` را تنظیم کنید.
2. گذرواژهٔ همهٔ حساب‌های نمونه را تغییر دهید یا کاربران غیرضروری را غیرفعال کنید
   (حساب‌های ساخته‌شده با گذرواژهٔ پیش‌فرض، پرچم «تغییر اجباری» دارند و تا تغییر آن فقط به پروفایل دسترسی دارند).
3. `PUBLIC_URL` را بررسی کنید — QR کدهای اصالت بر اساس آن ساخته می‌شوند.
4. برای اعضای هیئت‌مدیره (نقش M) احراز دو مرحله‌ای را از `admin/security` فعال کنید —
   POSTهای مالی/حاکمیت بدون آن مسدودند.
4. زمانبندی پشتیبان‌گیری:

```cron
# بکاپ روزانهٔ پایگاه داده
0 3 * * * mysqldump -u faraat -p'<pass>' faraatbafi | gzip > /backups/faraat-$(date +\%F).sql.gz
```

## ارتقا

```bash
git pull
composer install --no-dev --optimize-autoloader
php bin/faraat migrate        # فقط مهاجرت‌های جدید اجرا می‌شوند
```

## مقیاس‌پذیری (برای رشد تعاونی)

مقیاس فعلی: ۱۰ عضو، ~۱۰۰ بافنده، ۵ نمایندگی، ۲۰۰ مشتری.
- تولید کد با `GET_LOCK` همزمان ایمن است؛ برای بار بالا می‌توان شمارنده‌ها را به صف منتقل کرد.
- جداول پررشد (`notifications`, `activity_logs`, `inventory_movements`) ایندکس‌های لازم را دارند؛
  آرشیو سالانه توصیه می‌شود.
- نشست‌ها در فایل هستند؛ برای چند سرور، `session.save_handler=redis` پیشنهاد می‌شود.
