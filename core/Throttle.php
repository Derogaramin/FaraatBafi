<?php
/**
 * کلاس Throttle — محدودسازی نرخ تلاش ورود (نقد S2)
 *
 * سیاست: حداکثر {max} تلاش ناموفق برای هر شناسه+IP در بازهٔ {window} دقیقه؛
 * پس از آن {lockout} دقیقه قفل. تلاش موفق شمارش نمی‌شود.
 */

declare(strict_types=1);

namespace Core;

final class Throttle
{
    private const MAX_ATTEMPTS = 5;      // تعداد تلاش ناموفق مجاز
    private const WINDOW_MINUTES = 15;   // بازهٔ شمارش
    private const LOCKOUT_MINUTES = 15;  // مدت قفل

    /** آیا شناسه هم‌اکنون قفل است؟ — ثانیه‌های باقی‌مانده یا 0 */
    public static function lockedSeconds(string $identifier, string $ip): int
    {
        $since = date('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);
        $row = Database::selectOne(
            'SELECT COUNT(*) AS c, MAX(attempted_at) AS last_at
             FROM login_attempts
             WHERE success = 0 AND identifier = ? AND attempted_at >= ?',
            [self::normalize($identifier), $since]
        );
        $failures = (int) ($row['c'] ?? 0);

        // آستانهٔ IP جداگانه و بالاتر — تا کاربران پشت یک NAT دسته‌جمعی قفل نشوند
        $ipRow = Database::selectOne(
            'SELECT COUNT(*) AS c, MAX(attempted_at) AS last_at
             FROM login_attempts
             WHERE success = 0 AND ip = ? AND attempted_at >= ?',
            [$ip, $since]
        );
        $ipFailures = (int) ($ipRow['c'] ?? 0);

        $hitLimit = null;
        if ($failures >= self::MAX_ATTEMPTS) {
            $hitLimit = $row['last_at'] ?? null;
        } elseif ($ipFailures >= self::MAX_ATTEMPTS * 4) {
            $hitLimit = $ipRow['last_at'] ?? null;
        }
        if ($hitLimit === null) {
            return 0;
        }
        $last = strtotime((string) $hitLimit) ?: time();
        return max(0, self::LOCKOUT_MINUTES * 60 - (time() - $last));
    }

    /** ثبت یک تلاش ورود */
    public static function hit(string $identifier, string $ip, bool $success): void
    {
        Database::insert(
            'INSERT INTO login_attempts (identifier, ip, success, attempted_at) VALUES (?, ?, ?, NOW())',
            [self::normalize($identifier), $ip, $success ? 1 : 0]
        );
        if ($success) {
            // پاک‌سازی تاریخچهٔ شکست همان شناسه پس از ورود موفق
            Database::delete(
                'DELETE FROM login_attempts WHERE identifier = ? AND success = 0',
                [self::normalize($identifier)]
            );
        }
    }

    /** پیام فارسی قفل */
    public static function lockMessage(int $seconds): string
    {
        $minutes = (int) ceil($seconds / 60);
        return 'به دلیل تلاش‌های ناموفق متعدد، ورود موقتاً مسدود است. حدود '
            . Jalali::digits((string) $minutes) . ' دقیقه دیگر دوباره تلاش کنید.';
    }

    public static function attemptsLeft(string $identifier, string $ip): int
    {
        $since = date('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);
        $row = Database::selectOne(
            'SELECT COUNT(*) AS c FROM login_attempts
             WHERE success = 0 AND identifier = ? AND attempted_at >= ?',
            [self::normalize($identifier), $since]
        );
        return max(0, self::MAX_ATTEMPTS - (int) ($row['c'] ?? 0));
    }

    private static function normalize(string $identifier): string
    {
        return mb_strtolower(trim($identifier));
    }
}
