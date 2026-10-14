<?php
/**
 * کلاس Config — بارگذاری پیکربندی و فایل .env
 */

declare(strict_types=1);

namespace Core;

final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    /** @var array<string, string> */
    private static array $env = [];

    private static bool $loaded = false;

    /** بارگذاری .env و همهٔ فایل‌های config/ */
    public static function boot(string $basePath): void
    {
        if (self::$loaded) {
            return;
        }
        self::loadEnv($basePath . '/.env');

        foreach (glob($basePath . '/config/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            self::$items[$key] = require $file;
        }
        self::$loaded = true;
    }

    /** خواندن مقدار پیکربندی با نقطه‌notation — مثال: config('database.host') */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $default === null ? $value : $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &self::$items;
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;
                return;
            }
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }

    /** مقدار متغیر محیطی */
    public static function env(string $key, ?string $default = null): ?string
    {
        $value = self::$env[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true', '(true)'   => '1',
            'false', '(false)' => '',
            'null', '(null)'   => null,
            default            => (string) $value,
        };
    }

    private static function loadEnv(string $path): void
    {
        if (!is_file($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            if (strlen($value) > 1 && ($value[0] === '"' || $value[0] === "'") && $value[0] === $value[strlen($value) - 1]) {
                $value = substr($value, 1, -1);
            }
            self::$env[$key] = $value;
        }
    }
}
