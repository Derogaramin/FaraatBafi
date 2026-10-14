<?php
/**
 * کلاس Session — مدیریت نشست، فلش، ورودی‌های قدیمی و توکن CSRF
 */

declare(strict_types=1);

namespace Core;

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || PHP_SAPI === 'cli') {
            self::$started = true;
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        session_name('faraat_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (Config::get('app.env') === 'production'),
        ]);
        session_start();
        self::$started = true;
        self::enforceIdleTimeout();
    }

    /**
     * انقضای نشست پس از دورهٔ بی‌فعالی (نقد S5).
     * عمر هر نشست حداکثر session.absolute_lifetime است.
     */
    private static function enforceIdleTimeout(): void
    {
        $idle = (int) Config::get('session.idle_timeout', 1800);
        $absolute = (int) Config::get('session.absolute_lifetime', 28800);
        $now = time();
        $last = (int) ($_SESSION['_last_activity'] ?? $now);
        $born = (int) ($_SESSION['_born_at'] ?? $now);

        if ($now - $last > $idle || $now - $born > $absolute) {
            $flashNote = 'به دلیل عدم فعالیت، نشست شما بسته شد.';
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['_flash']['warning'] = $flashNote;
        }
        // به‌روزرسانی مهر فعالیت (حداکثر هر ۳۰ ثانیه یک‌بار برای کاهش نوشتن)
        if ($now - $last > 30 || !isset($_SESSION['_born_at'])) {
            $_SESSION['_last_activity'] = $now;
            $_SESSION['_born_at'] = $_SESSION['_born_at'] ?? $now;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function all(): array
    {
        self::start();
        return $_SESSION ?? [];
    }

    /** پیام یک‌بارمصرف: نوع (success/error/warning/info) و متن */
    public static function flash(string $type, string $message): void
    {
        self::start();
        $_SESSION['_flash'][$type] = $message;
    }

    /** @return array<string, string> خواندن و پاک‌کردن پیام‌های فلش */
    public static function pullFlashes(): array
    {
        self::start();
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    /** نگهداری موقت ورودی‌های فرم (برای old()) */
    public static function flashInput(array $input): void
    {
        self::start();
        unset($input['_token'], $input['password'], $input['password_confirmation']);
        $_SESSION['_old'] = $input;
    }

    public static function getOld(string $key, mixed $default = ''): mixed
    {
        self::start();
        return $_SESSION['_old'][$key] ?? $default;
    }

    /** @return array<string, mixed> خواندن و پاک‌کردن ورودی‌های قدیمی */
    public static function pullOld(): array
    {
        self::start();
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
        return $old;
    }

    /** خطاهای اعتبارسنجی برای نمایش در فرم */
    public static function flashErrors(array $errors): void
    {
        self::start();
        $_SESSION['_errors'] = $errors;
    }

    /** @return array<string, string> */
    public static function pullErrors(): array
    {
        self::start();
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);
        return $errors;
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::start();
        return is_string($token) && $token !== '' && hash_equals($_SESSION['_token'] ?? '', $token);
    }

    public static function regenerate(): void
    {
        self::start();
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        self::$started = false;
    }
}
