<?php
/**
 * مدل Vote — ماژول Admin
 * جدول: votes
 */

declare(strict_types=1);

namespace App\Modules\Admin\Models;

use Core\Model;

final class Vote extends Model
{
    protected static string $table = 'votes';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'title',
        'description',
        'options',
        'starts_at',
        'ends_at',
        'status',
    ];
    protected static array $casts = [
        'options' => 'json',
    ];
}
