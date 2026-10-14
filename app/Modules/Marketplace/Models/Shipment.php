<?php
/**
 * مدل Shipment — ماژول Marketplace
 * جدول: shipments
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Models;

use Core\Model;

final class Shipment extends Model
{
    protected static string $table = 'shipments';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'order_id',
        'carrier',
        'tracking_no',
        'cost',
        'status',
        'shipped_at',
        'delivered_at',
    ];
    protected static array $casts = [
        'cost' => 'int',
    ];
}
