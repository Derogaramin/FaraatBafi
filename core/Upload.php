<?php
/**
 * کلاس Upload — مدیریت آپلود امن تصاویر
 *
 * تصاویر در public/assets/uploads/[dir]/ با نام یکتا ذخیره می‌شوند.
 * فقط پسوندهای مجاز و اندازهٔ تعیین‌شده پذیرفته می‌شود.
 */

declare(strict_types=1);

namespace Core;

use RuntimeException;

final class Upload
{
    // SVG عمداً مجاز نیست: قابلیت اجرای script دارد (Stored XSS — نقد S1)
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    private const MAX_BYTES = 4_194_304; // ۴ مگابایت

    /**
     * ذخیرهٔ فایل آپلودشده.
     *
     * @return string|null مسیر نسبی ذخیره‌شده (برای DB) یا null اگر فایلی ارسال نشده
     */
    public static function image(?array $file, string $dir = 'products'): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage((int) $file['error']));
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('حجم تصویر نباید بیش از ۴ مگابایت باشد.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException('فقط تصاویر jpg، png، webp، gif یا svg مجاز هستند.');
        }

        $dir = preg_replace('/[^a-z0-9_\-]/', '', $dir) ?: 'misc';
        $target = App::basePath("public/assets/uploads/{$dir}");
        if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
            throw new RuntimeException('ساخت پوشهٔ آپلود ناموفق بود.');
        }

        $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.webp';
        $dest = "{$target}/{$name}";

        // تبدیل به WebP با کیفیت ۸۲ (حجم کمتر برای موبایل بافندگان/مشتریان)
        $converted = self::toWebp($file['tmp_name'], $mime, $dest);
        if (!$converted) {
            // بازگشت به ذخیرهٔ مستقیم در صورت نبود GD
            $ext = self::ALLOWED_MIME[$mime];
            $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = "{$target}/{$name}";
            if (!move_uploaded_file($file['tmp_name'], $dest) && !rename($file['tmp_name'], $dest)) {
                if (!copy($file['tmp_name'], $dest)) {
                    throw new RuntimeException('ذخیرهٔ فایل ناموفق بود.');
                }
            }
        }
        return "{$dir}/{$name}";
    }

    /** حذف فایل آپلودشده (بی‌خطر اگر وجود نداشته باشد) */
    public static function delete(?string $relativePath): void
    {
        if (!$relativePath || str_contains($relativePath, '..')) {
            return;
        }
        $full = App::basePath('public/assets/uploads/' . ltrim($relativePath, '/'));
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /** تبدیل تصویر ورودی به WebP — در صورت موفقیت false برنمی‌گرداند */
    private static function toWebp(string $source, string $mime, string $dest): bool
    {
        if (!function_exists('imagewebp')) {
            return false;
        }
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png'  => @imagecreatefrompng($source),
            'image/webp' => @imagecreatefromwebp($source),
            'image/gif'  => @imagecreatefromgif($source),
            default      => false,
        };
        if ($image === false) {
            return false;
        }
        // حداکثر عرض ۱۴۰۰ پیکسل
        if (imagesx($image) > 1400) {
            $image = imagescale($image, 1400, -1, IMG_BILINEAR_FIXED);
        }
        // پس‌زمینهٔ شفاف PNG روی سفید تثبیت شود
        if ($mime === 'image/png' || $mime === 'image/gif') {
            $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($flat, 0, 0, (int) imagecolorallocate($flat, 255, 255, 255));
            imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            imagedestroy($image);
            $image = $flat;
        }
        $ok = imagewebp($image, $dest, 82);
        imagedestroy($image);
        return $ok;
    }

    private static function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'حجم فایل آپلودی بیش از حد مجاز است.',
            UPLOAD_ERR_PARTIAL    => 'فایل به‌طور ناقص آپلود شد.',
            UPLOAD_ERR_NO_FILE    => 'فایلی انتخاب نشده است.',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشهٔ موقت سرور موجود نیست.',
            UPLOAD_ERR_CANT_WRITE => 'ذخیرهٔ فایل روی دیسک ناموفق بود.',
            default               => 'خطای نامشخص در آپلود فایل.',
        };
    }
}
