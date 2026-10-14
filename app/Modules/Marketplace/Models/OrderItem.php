<?php
/**
 * مدل OrderItem — ماژول Marketplace
 * جدول: order_items
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Models;

use Core\Model;

final class OrderItem extends Model
{
    protected static string $table = 'order_items';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'order_id',
        'variant_id',
        'sku',
        'title',
        'unit_price',
        'quantity',
        'total',
    ];
    protected static array $casts = [
        'unit_price' => 'int',
        'total' => 'int',
        'quantity' => 'int',
    ];
}
