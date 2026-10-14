<?php
/**
 * مدل Setting — ماژول Admin
 * جدول: settings
 */

declare(strict_types=1);

namespace App\Modules\Admin\Models;

use Core\Model;

final class Setting extends Model
{
    protected static string $table = 'settings';
    protected static string $primaryKey = 'key';
    protected static bool $timestamps = false;
    protected static array $fillable = [
        'key',
        'value',
    ];
    protected static array $casts = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        $row = \Core\Database::selectOne('SELECT `value` FROM settings WHERE `key` = ?', [$key]);
        return $row['value'] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        \Core\Database::statement(
            'INSERT INTO settings (`key`, `value`, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()',
            [$key, $value]
        );
    }
}
