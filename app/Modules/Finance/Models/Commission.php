<?php
/**
 * مدل Commission — ماژول Finance
 * جدول: commissions
 */

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use Core\Model;

final class Commission extends Model
{
    protected static string $table = 'commissions';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'agency_id',
        'order_id',
        'base_amount',
        'rate',
        'amount',
        'period',
        'status',
        'paid_at',
    ];
    protected static array $casts = [
        'base_amount' => 'int',
        'amount' => 'int',
        'rate' => 'float',
    ];

    public function statusLabel(): string
    {
        return \Core\Statuses::label('commission', (string) ($this->attributes['status'] ?? ''));
    }
}
