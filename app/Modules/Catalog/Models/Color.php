<?php
/**
 * مدل Color — ماژول Catalog
 * جدول: colors
 */

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Core\Model;

final class Color extends Model
{
    protected static string $table = 'colors';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'name_fa',
        'hex',
        'family',
        'is_natural',
        'is_active',
    ];
    protected static array $casts = [];
}
