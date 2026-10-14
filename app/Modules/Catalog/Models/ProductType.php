<?php
/**
 * مدل ProductType — ماژول Catalog
 * جدول: product_types
 */

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Core\Model;

final class ProductType extends Model
{
    protected static string $table = 'product_types';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'name_fa',
        'name_en',
        'description',
        'sort_order',
        'is_active',
    ];
    protected static array $casts = [
        'sort_order' => 'int',
    ];
}
