<?php
/**
 * مدل Notification — ماژول CRM
 * جدول: notifications
 */

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use Core\Model;

final class Notification extends Model
{
    protected static string $table = 'notifications';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'user_id',
        'title',
        'body',
        'link',
        'kind',
        'is_read',
        'created_at',
    ];
    protected static array $casts = [];

    /** اعلان‌های نخواندهٔ یک کاربر */
    public static function unreadFor(int $userId, int $limit = 10): array
    {
        return self::query()->where('user_id', $userId)->where('is_read', 0)
            ->orderBy('id', 'DESC')->limit($limit)->get();
    }

    /** ساخت اعلان (توسط شنونده‌های رویداد) */
    public static function push(?int $userId, string $title, ?string $body = null, ?string $link = null, string $kind = 'info'): void
    {
        if ($userId === null) {
            return;
        }
        self::create([
            'user_id' => $userId, 'title' => $title, 'body' => $body,
            'link' => $link, 'kind' => $kind, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
