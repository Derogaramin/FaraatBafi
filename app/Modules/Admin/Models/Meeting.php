<?php
/**
 * مدل Meeting — ماژول Admin
 * جدول: meetings
 */

declare(strict_types=1);

namespace App\Modules\Admin\Models;

use Core\Model;

final class Meeting extends Model
{
    protected static string $table = 'meetings';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'title',
        'meeting_date',
        'location',
        'agenda',
        'minutes',
        'status',
        'created_by',
    ];
    protected static array $casts = [];
}
