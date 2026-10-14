<?php
/**
 * کلاس Seeder — اجرای دانه‌گذارهای دادهٔ نمونه (database/seeders/*.php)
 *
 * هر فایل دانه‌گذار یک closure برمی‌گرداند: fn() => void
 */

declare(strict_types=1);

namespace Core;

final class Seeder
{
    public static function run(bool $quiet = false): void
    {
        $files = glob(App::basePath('database/seeders/*.php')) ?: [];
        sort($files);
        foreach ($files as $file) {
            $seeder = require $file;
            if (!is_callable($seeder)) {
                throw new \RuntimeException("دانه‌گذار نامعتبر: {$file}");
            }
            $seeder();
            if (!$quiet) {
                echo '✓ دانه‌گذاری: ' . basename($file) . "\n";
            }
        }
    }
}
