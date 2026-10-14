<?php
/**
 * مدل Payment — ماژول Finance
 * جدول: payments
 */

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use Core\Model;

final class Payment extends Model
{
    protected static string $table = 'payments';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'weaver_id',
        'work_order_id',
        'amount',
        'period',
        'method',
        'status',
        'paid_at',
        'paid_by',
        'note',
    ];
    protected static array $casts = [
        'amount' => 'int',
    ];

    public function statusLabel(): string
    {
        return \Core\Statuses::label('payment', (string) ($this->attributes['status'] ?? ''));
    }
}
