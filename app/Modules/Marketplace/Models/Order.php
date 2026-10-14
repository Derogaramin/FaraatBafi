<?php
/**
 * مدل Order — ماژول Marketplace
 * جدول: orders
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Models;

use Core\Model;

final class Order extends Model
{
    protected static string $table = 'orders';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'customer_id',
        'user_id',
        'agency_id',
        'channel',
        'status',
        'subtotal',
        'discount',
        'shipping_cost',
        'total',
        'coupon_id',
        'receiver',
        'phone',
        'province',
        'city',
        'address',
        'postal_code',
        'note',
        'placed_at',
        'delivered_at',
    ];
    protected static array $casts = [
        'subtotal' => 'int',
        'discount' => 'int',
        'shipping_cost' => 'int',
        'total' => 'int',
        'customer_id' => 'int',
    ];

    /** اقلام سفارش */
    public function items(): array
    {
        return OrderItem::query()->where('order_id', $this->id())->get();
    }

    public function statusLabel(): string
    {
        return \Core\Statuses::label('order', (string) ($this->attributes['status'] ?? ''));
    }

    public function statusColor(): string
    {
        return \Core\Statuses::color('order', (string) ($this->attributes['status'] ?? ''));
    }

    /** تغییر وضعیت با ثبت زمان‌های مربوطه */
    public function changeStatus(string $status): void
    {
        $this->attributes['status'] = $status;
        if ($status === 'delivered') {
            $this->attributes['delivered_at'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        $this->save();
    }

    /** آیا سفارش قابل پیگیری عمومی با این شمارهٔ تلفن است؟ */
    public function matchesPhone(string $phone): bool
    {
        return normalize_phone((string) ($this->attributes['phone'] ?? '')) === normalize_phone($phone);
    }
}
