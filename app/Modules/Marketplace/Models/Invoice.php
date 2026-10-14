<?php
/**
 * مدل Invoice — ماژول Marketplace
 * جدول: invoices
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Models;

use Core\Model;

final class Invoice extends Model
{
    protected static string $table = 'invoices';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'order_id',
        'amount',
        'paid',
        'method',
        'issued_at',
        'paid_at',
    ];
    protected static array $casts = [
        'amount' => 'int',
    ];
}
