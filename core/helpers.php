<?php
/**
 * helpers.php — توابع کمکی سراسری سامانهٔ فرت‌بافی
 *
 * این توابع در تمام ویوها و کنترلرها در دسترس‌اند.
 */

declare(strict_types=1);

use Core\App;
use Core\Jalali;
use Core\Session;
use Core\View;

if (!function_exists('e')) {
    /** گریزکاراکترهای HTML (جلوگیری از XSS) */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('fa')) {
    /** تبدیل رقم‌ها به فارسی */
    function fa(string|int|float|null $value): string
    {
        return Jalali::digits((string) ($value ?? ''));
    }
}

if (!function_exists('en_digits')) {
    /** تبدیل رقم‌های فارسی/عربی به انگلیسی */
    function en_digits(string $value): string
    {
        return Jalali::toLatinDigits($value);
    }
}

if (!function_exists('jdate')) {
    /** قالب‌بندی تاریخ شمسی از timestamp */
    function jdate(string $format = 'Y/m/d', ?int $timestamp = null): string
    {
        return Jalali::format($format, $timestamp);
    }
}

if (!function_exists('jdt')) {
    /** قالب‌بندی رشتهٔ تاریخ MySQL به شمسی */
    function jdt(?string $sqlDatetime, string $format = 'Y/m/d', bool $withTime = false): string
    {
        return Jalali::formatSql($sqlDatetime, $format, $withTime);
    }
}

if (!function_exists('jrelative')) {
    /** زمان نسبی فارسی */
    function jrelative(?string $sqlDatetime): string
    {
        return Jalali::relative($sqlDatetime);
    }
}

if (!function_exists('url')) {
    /** ساخت آدرس مطلق بر پایهٔ BASE_URL */
    function url(string $path = ''): string
    {
        $base = rtrim((string) Core\Config::get('app.base_url', ''), '/');
        $path = ltrim($path, '/');
        return $path === '' ? ($base === '' ? '/' : $base) : $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    /** آدرس دارایی‌های استاتیک (css/js/فونت/تصویر) با cache-buster */
    function asset(string $path): string
    {
        $file = App::basePath('public/' . ltrim($path, '/'));
        $version = is_file($file) ? (string) filemtime($file) : (string) Core\Config::get('app.version', '1');
        return url('assets/' . ltrim($path, '/')) . '?v=' . $version;
    }
}

if (!function_exists('upload_asset')) {
    /** آدرس فایل‌های آپلودشده */
    function upload_asset(?string $path, string $fallback = 'images/placeholder-product.svg'): string
    {
        if (!$path) {
            return asset($fallback);
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return url('assets/uploads/' . ltrim($path, '/'));
    }
}

if (!function_exists('old')) {
    /** مقدار قبلی ورودی فرم (بعد از خطای اعتبارسنجی) */
    function old(string $key, mixed $default = ''): mixed
    {
        return Session::getOld($key, $default);
    }
}

if (!function_exists('csrf_field')) {
    /** فیلد پنهان CSRF برای فرم‌ها */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Session::csrfToken()) . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::csrfToken();
    }
}

if (!function_exists('view')) {
    /** رندر ویو و برگرداندن رشتهٔ HTML */
    function view(string $name, array $data = []): string
    {
        return View::capture($name, $data);
    }
}

if (!function_exists('component')) {
    /** رندر کامپوننت اشتراکی (app/Views/components) */
    function component(string $name, array $data = []): string
    {
        return View::capture('components/' . $name, $data);
    }
}

if (!function_exists('toman')) {
    /** قالب‌بندی مبلغ به تومان با جداکنندهٔ هزارگان و رقم فارسی */
    function toman(int|float|null $amount, bool $withUnit = true): string
    {
        $formatted = number_format((float) ($amount ?? 0));
        return fa($formatted) . ($withUnit ? ' تومان' : '');
    }
}

if (!function_exists('toman_short')) {
    /** مبلغ خلاصه برای کارت‌های KPI: «۱۲٫۵ میلیون» */
    function toman_short(int|float|null $amount): string
    {
        $amount = (float) ($amount ?? 0);
        if ($amount >= 1_000_000_000) {
            return fa(rtrim(rtrim(number_format($amount / 1_000_000_000, 1), '0'), '.')) . ' میلیارد';
        }
        if ($amount >= 1_000_000) {
            return fa(rtrim(rtrim(number_format($amount / 1_000_000, 1), '0'), '.')) . ' میلیون';
        }
        if ($amount >= 1_000) {
            return fa(rtrim(rtrim(number_format($amount / 1_000, 1), '0'), '.')) . ' هزار';
        }
        return fa((string) $amount);
    }
}

if (!function_exists('dd')) {
    /** دیباگ: چاپ و توقف */
    function dd(mixed ...$values): never
    {
        foreach ($values as $v) {
            echo '<pre dir="ltr" style="text-align:left;background:#222;color:#0f0;padding:12px;border-radius:8px;overflow:auto">';
            var_dump($v);
            echo '</pre>';
        }
        exit(1);
    }
}

if (!function_exists('array_get')) {
    /** خواندن کلید تو در تو با نقطه‌notation */
    function array_get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        $value = $array;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('normalize_phone')) {
    /** یکسان‌سازی شمارهٔ موبایل ایرانی: 09xxxxxxxxx */
    function normalize_phone(string $phone): string
    {
        $phone = en_digits(preg_replace('/\s|-|\(|\)/', '', $phone) ?? '');
        if (str_starts_with($phone, '+98')) {
            $phone = '0' . substr($phone, 3);
        } elseif (str_starts_with($phone, '98') && strlen($phone) === 12) {
            $phone = '0' . substr($phone, 2);
        } elseif (str_starts_with($phone, '9') && strlen($phone) === 10) {
            $phone = '0' . $phone;
        }
        return $phone;
    }
}

if (!function_exists('str_slug')) {
    /** ساخت اسلاگ از متن (فارسی/لاتین) */
    function str_slug(string $text, string $sep = '-'): string
    {
        $text = trim($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', $sep, $text) ?? $text;
        $text = trim($text, $sep);
        return $text === '' ? bin2hex(random_bytes(3)) : mb_strtolower($text);
    }
}

if (!function_exists('flash')) {
    /** ثبت پیام یک‌بارمصرف (success/error/warning/info) */
    function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }
}

if (!function_exists('menu_match')) {
    /**
     * تطبیق آیتم منو با مسیر جاری.
     * مقدار match: «exact:admin» (تطابق کامل) یا «admin/orders» (پیشوند).
     */
    function menu_match(?string $match): bool
    {
        $match = (string) $match;
        if ($match === '') {
            return false;
        }
        $base = rtrim((string) Core\Config::get('app.base_path', ''), '/');
        $current = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
        if ($base !== '' && str_starts_with($current, $base)) {
            $current = substr($current, strlen($base)) ?: '/';
        }
        if (str_starts_with($match, 'exact:')) {
            return $current === '/' . trim(substr($match, 6), '/');
        }
        return str_starts_with($current, '/' . trim($match, '/'));
    }
}

if (!function_exists('menu_active')) {
    /** کلاس active برای لینک‌های ساده (پیشوند مسیر) */
    function menu_active(string $prefix, string $exact = ''): string
    {
        if ($exact !== '' && menu_match('exact:' . ltrim(parse_url($exact, PHP_URL_PATH) ?? $exact, '/'))) {
            return ' active';
        }
        if ($prefix !== '/' && menu_match($prefix)) {
            return ' active';
        }
        if ($prefix === '/') {
            $base = rtrim((string) Core\Config::get('app.base_path', ''), '/');
            $current = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
            if ($base !== '' && str_starts_with($current, $base)) {
                $current = substr($current, strlen($base)) ?: '/';
            }
            return $current === '/' ? ' active' : '';
        }
        return '';
    }
}

if (!function_exists('icon')) {
    /** آیکون SVG درون‌خطی سامانه (بدون وابستگی بیرونی) */
    function icon(string $name, string $class = '', int $size = 20): string
    {
        return Core\Icons::render($name, $size, $class);
    }
}

if (!function_exists('weave_visual')) {
    /**
     * تصویر جایگزین «بافته‌شده» برای محصولات بدون تصویر.
     *
     * یک SVG قطعی (deterministic) می‌سازد: تار و پود با رنگ واقعی محصول
     * + لوزی مرکزی بر اساسhash SKU — هر محصول چهرهٔ بصری مخصوص خودش را دارد.
     */
    function weave_visual(string $hex = '#8B2E3A', string $seed = '', bool $asDataUri = true): string
    {
        // memoize درون‌درخواستی — فهرست‌های چندکارتی دوباره‌کاری نکنند (نقد §۵٫۳)
        static $cache = [];
        $cacheKey = $hex . '|' . $seed . '|' . ($asDataUri ? '1' : '0');
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }
        $hex = preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) ? $hex : '#8B2E3A';
        $h = abs(crc32($seed !== '' ? $seed : $hex));

        // دو رنگ مکمل ملایم از رنگ اصلی
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        $light = sprintf('#%02X%02X%02X', min(255, $r + 90), min(255, $g + 85), min(255, $b + 70));
        $dark  = sprintf('#%02X%02X%02X', (int) ($r * 0.45), (int) ($g * 0.45), (int) ($b * 0.45));
        $cream = '#F7F1E3';

        // الگوی تار و پود
        $warp = '';
        for ($x = 0; $x < 320; $x += 16) {
            $w = ($x / 16 + $h) % 3 === 0 ? 3 : 2;
            $warp .= "<rect x='{$x}' y='0' width='{$w}' height='240' fill='{$cream}' opacity='0.14'/>";
        }
        $weft = '';
        for ($y = 0; $y < 240; $y += 12) {
            $weft .= "<rect x='0' y='{$y}' width='320' height='2' fill='{$dark}' opacity='0.22'/>";
        }

        // لوزی‌های نقش‌مایه (موقعیت از hash)
        $motifs = '';
        $cx = 60 + ($h % 200);
        $cy = 70 + (($h >> 8) % 100);
        for ($i = 0; $i < 3; $i++) {
            $s = 46 - $i * 13;
            $motifs .= "<path d='M{$cx} " . ($cy - $s) . ' L' . ($cx + $s) . " {$cy} L{$cx} " . ($cy + $s) . ' L' . ($cx - $s) . " {$cy} Z' fill='none' stroke='" . ($i % 2 ? $light : $cream) . "' stroke-width='2.5' opacity='0.85'/>";
        }
        // حاشیهٔ زیگزاگ پایین (پای پارچه)
        $zig = '';
        for ($x = 0; $x < 320; $x += 20) {
            $zig .= "<path d='M{$x} 228 L" . ($x + 10) . " 218 L" . ($x + 20) . " 228' fill='none' stroke='{$cream}' stroke-width='2' opacity='0.55'/>";
        }

        $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='320' height='240' viewBox='0 0 320 240'>"
            . "<defs><linearGradient id='g{$h}' x1='0' y1='0' x2='1' y2='1'>"
            . "<stop offset='0' stop-color='{$hex}'/><stop offset='1' stop-color='{$dark}'/></linearGradient></defs>"
            . "<rect width='320' height='240' fill='url(#g{$h})'/>{$warp}{$weft}{$motifs}{$zig}</svg>";

        $result = $asDataUri ? 'data:image/svg+xml;base64,' . base64_encode($svg) : $svg;
        return $cache[$cacheKey] = $result;
    }
}
