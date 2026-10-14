<?php
/**
 * مدل Staff — ماژول Members
 * جدول: staff
 */

declare(strict_types=1);

namespace App\Modules\Members\Models;

use Core\Model;

final class Staff extends Model
{
    protected static string $table = 'staff';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'user_id',
        'code',
        'position',
        'hire_date',
        'base_salary',
        'national_id',
    ];
    protected static array $casts = [
        'base_salary' => 'int',
    ];
}
