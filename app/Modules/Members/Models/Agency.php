<?php
/**
 * مدل Agency — ماژول Members
 * جدول: agencies
 */

declare(strict_types=1);

namespace App\Modules\Members\Models;

use Core\Model;

final class Agency extends Model
{
    protected static string $table = 'agencies';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'user_id',
        'code',
        'name',
        'manager_name',
        'province',
        'city',
        'address',
        'phone',
        'commission_rate',
        'credit_limit',
        'balance',
        'status',
        'started_at',
    ];
    protected static array $casts = [
        'commission_rate' => 'float',
        'credit_limit' => 'int',
        'balance' => 'int',
    ];
}
