<?php
/**
 * مدل Resolution — ماژول Admin
 * جدول: resolutions
 */

declare(strict_types=1);

namespace App\Modules\Admin\Models;

use Core\Model;

final class Resolution extends Model
{
    protected static string $table = 'resolutions';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'meeting_id',
        'sequence',
        'title',
        'body',
        'status',
    ];
    protected static array $casts = [];
}
