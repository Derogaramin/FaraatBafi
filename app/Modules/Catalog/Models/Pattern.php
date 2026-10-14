<?php
/**
 * مدل Pattern — ماژول Catalog
 * جدول: patterns
 */

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Core\Model;

final class Pattern extends Model
{
    protected static string $table = 'patterns';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'name_fa',
        'designer',
        'image',
        'motif',
        'description',
        'is_active',
    ];
    protected static array $casts = [];
}
