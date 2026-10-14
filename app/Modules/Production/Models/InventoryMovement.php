<?php
/**
 * مدل InventoryMovement — ماژول Production
 * جدول: inventory_movements
 */

declare(strict_types=1);

namespace App\Modules\Production\Models;

use Core\Model;

final class InventoryMovement extends Model
{
    protected static string $table = 'inventory_movements';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'material_id',
        'variant_id',
        'direction',
        'quantity',
        'ref_type',
        'ref_id',
        'user_id',
        'note',
        'created_at',
    ];
    protected static array $casts = [];
}
