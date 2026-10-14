<?php
/**
 * مدل DefectType — ماژول Production
 * جدول: defect_types
 */

declare(strict_types=1);

namespace App\Modules\Production\Models;

use Core\Model;

final class DefectType extends Model
{
    protected static string $table = 'defect_types';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'name_fa',
        'category',
        'severity',
    ];
    protected static array $casts = [];
}
