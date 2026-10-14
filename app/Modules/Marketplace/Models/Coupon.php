<?php
/**
 * مدل Coupon — ماژول Marketplace
 * جدول: coupons
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Models;

use Core\Model;

final class Coupon extends Model
{
    protected static string $table = 'coupons';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'title',
        'type',
        'value',
        'max_amount',
        'min_order',
        'starts_at',
        'expires_at',
        'usage_limit',
        'used_count',
        'is_active',
    ];
    protected static array $casts = [
        'value' => 'int',
    ];

    /** اعتبارسنجی کوپن برای یک مبلغ — خطا یا null */
    public function validateFor(int $subtotal): ?string
    {
        $a = $this->attributes;
        if ((int) $a['is_active'] !== 1) {
            return 'این کوپن فعال نیست.';
        }
        $today = date('Y-m-d');
        if (!empty($a['starts_at']) && $a['starts_at'] > $today) {
            return 'این کوپن هنوز فعال نشده است.';
        }
        if (!empty($a['expires_at']) && $a['expires_at'] < $today) {
            return 'این کوپن منقضی شده است.';
        }
        if ($a['usage_limit'] !== null && (int) $a['used_count'] >= (int) $a['usage_limit']) {
            return 'ظرفیت این کوپن تکمیل شده است.';
        }
        if ($subtotal < (int) $a['min_order']) {
            return 'حداقل مبلغ خرید برای این کوپن ' . toman((int) $a['min_order']) . ' است.';
        }
        return null;
    }

    public function discountFor(int $subtotal): int
    {
        $a = $this->attributes;
        if ($a['type'] === 'percent') {
            $amount = (int) floor($subtotal * ((int) $a['value'] / 100));
            return $a['max_amount'] ? min($amount, (int) $a['max_amount']) : $amount;
        }
        return min((int) $a['value'], $subtotal);
    }
}
