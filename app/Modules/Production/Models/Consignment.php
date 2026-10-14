<?php
/**
 * مدل Consignment — ماژول Production
 * جدول: consignments
 */

declare(strict_types=1);

namespace App\Modules\Production\Models;

use Core\Model;

final class Consignment extends Model
{
    protected static string $table = 'consignments';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'agency_id',
        'variant_id',
        'quantity',
        'sent_at',
    ];
    protected static array $casts = [
        'quantity' => 'int',
    ];
}
