<?php
/**
 * کلاس Sms — انتزاع ارسال پیامک (نقشهٔ راه ۱٫۵٫۸)
 *
 * درایور پیش‌فرض «log» است (بدون وابستگی به پنل پیامکی)؛
 * برای اتصال واقعی، درایور را در config/app.php عوض کنید:
 *   'sms' => ['driver' => 'kavenegar', 'key' => …]
 */

declare(strict_types=1);

namespace Core;

final class Sms
{
    /** @var array<int, array{to:string,text:string}> صف در حافظه (برای تست) */
    private static array $sent = [];

    /** ارسال پیامک — در صورت عدم موفقیت استثنا نمی‌دهد (جریان اصلی نباید بشکند) */
    public static function send(string $to, string $text): bool
    {
        $to = normalize_phone($to);
        if (!preg_match('/^09\d{9}$/', $to)) {
            return false;
        }
        self::$sent[] = ['to' => $to, 'text' => $text];

        $driver = (string) Config::get('app.sms.driver', 'log');
        try {
            return match ($driver) {
                'log'     => self::logDriver($to, $text),
                'null'    => true,
                default   => self::logDriver($to, $text), // درایور ناشناخته → لاگ
            };
        } catch (\Throwable $e) {
            App::log('sms', 'send failed to ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }

    /** پیام‌های ارسال‌شده در این درخواست (برای تست) */
    public static function sent(): array
    {
        return self::$sent;
    }

    public static function flush(): void
    {
        self::$sent = [];
    }

    private static function logDriver(string $to, string $text): bool
    {
        $line = sprintf('[%s] → %s : %s%s', date('Y-m-d H:i:s'), $to, $text, PHP_EOL);
        $dir = App::basePath('storage/logs');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return file_put_contents($dir . '/sms.log', $line, FILE_APPEND | LOCK_EX) !== false;
    }
}
