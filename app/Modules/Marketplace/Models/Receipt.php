<?php
/**
 * مدل Receipt — ماژول Marketplace
 * جدول: receipts
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Models;

use Core\Model;

final class Receipt extends Model
{
    protected static string $table = 'receipts';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'order_id',
        'invoice_id',
        'amount',
        'method',
        'received_by',
        'received_at',
        'note',
    ];
    protected static array $casts = [
        'amount' => 'int',
    ];
}
