<?php
/**
 * مدل Customer — ماژول Members
 * جدول: customers
 */

declare(strict_types=1);

namespace App\Modules\Members\Models;

use Core\Model;

final class Customer extends Model
{
    protected static string $table = 'customers';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'user_id',
        'code',
        'name',
        'phone',
        'email',
        'province',
        'city',
        'address',
        'postal_code',
    ];
    protected static array $casts = [];

    /** یافتن یا ساخت مشتری از روی شمارهٔ موبایل (ثبت سفارش مهمان) */
    public static function findOrCreateByPhone(string $phone, string $name = '', ?int $userId = null): self
    {
        $phone = normalize_phone($phone);
        $existing = self::query()->where('phone', $phone)->first();
        if ($existing) {
            return self::hydrate($existing);
        }
        $customer = new self();
        $customer->fill([
            'user_id' => $userId,
            'code'    => \Core\CodeGenerator::customerCode(),
            'name'    => $name !== '' ? $name : 'مشتری ' . \Core\Jalali::digits(substr($phone, -4)),
            'phone'   => $phone,
        ]);
        $customer->save();
        return $customer;
    }
}
