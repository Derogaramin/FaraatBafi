<?php
/**
 * مدل ReturnRequest — ماژول Marketplace
 * جدول: returns
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Models;

use Core\Model;

final class ReturnRequest extends Model
{
    protected static string $table = 'returns';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'order_id',
        'customer_id',
        'reason',
        'status',
        'refund_amount',
        'requested_at',
        'resolved_at',
        'note',
    ];
    protected static array $casts = [
        'refund_amount' => 'int',
    ];
}
