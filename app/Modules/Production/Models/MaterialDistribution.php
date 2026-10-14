<?php
/**
 * مدل MaterialDistribution — ماژول Production
 * جدول: material_distributions
 */

declare(strict_types=1);

namespace App\Modules\Production\Models;

use Core\Model;

final class MaterialDistribution extends Model
{
    protected static string $table = 'material_distributions';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'work_order_id',
        'material_id',
        'quantity',
        'distributed_by',
        'distributed_at',
        'note',
    ];
    protected static array $casts = [];
}
