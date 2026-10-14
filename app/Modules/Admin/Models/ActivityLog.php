<?php
/**
 * مدل ActivityLog — ماژول Admin
 * جدول: activity_logs
 */

declare(strict_types=1);

namespace App\Modules\Admin\Models;

use Core\Model;

final class ActivityLog extends Model
{
    protected static string $table = 'activity_logs';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'user_id',
        'action',
        'entity',
        'entity_id',
        'ip',
        'created_at',
    ];
    protected static array $casts = [];
}
