<?php
/**
 * سرویس ثبت سفارش (Checkout) — قلب جریان فروش بازارگاه
 *
 * جریان: اعتبارسنجی سبد → یافتن/ساخت مشتری (کد C) → ساخت سفارش (کد ORD)
 *       → اقلام با اسکنپشوت قیمت → کسر انبار → فاکتور (INV) → رویداد order.placed
 * طبق مستندات: پاسخ API/سرویس باید «id + کد تولیدشده» را برگرداند.
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Services;

use App\Modules\Marketplace\Models\Coupon;
use App\Modules\Marketplace\Models\Invoice;
use App\Modules\Marketplace\Models\Order;
use App\Modules\Marketplace\Models\OrderItem;
use App\Modules\Members\Models\Customer;
use Core\CodeGenerator;
use Core\Database;
use Core\Events;
use Core\Jalali;
use RuntimeException;

final class CheckoutService
{
    public const SHIPPING_FLAT = 85_000;   // هزینهٔ پست — تومان
    public const FREE_SHIPPING_OVER = 3_000_000;

    /**
     * ثبت سفارش.
     *
     * @param array $info  receiver, phone, province, city, address, postal_code, note
     * @return array{ok:bool, order?:Order, code?:string, id?:int, error?:string}
     */
    public static function place(array $info, ?int $userId = null, ?int $agencyId = null, string $channel = 'web', string $couponCode = ''): array
    {
        $details = CartService::details();
        if ($details['items'] === []) {
            return ['ok' => false, 'error' => 'سبد خرید خالی است.'];
        }

        try {
            $result = Database::transaction(function () use ($details, $info, $userId, $agencyId, $channel, $couponCode): array {
                // ۱) مشتری (کد FUCS با پیشوند C)
                $customer = Customer::findOrCreateByPhone(
                    normalize_phone((string) $info['phone']),
                    (string) ($info['receiver'] ?? ''),
                    $userId
                );

                // ۲) کوپن تخفیف
                $discount = 0;
                $coupon = null;
                if ($couponCode !== '') {
                    $coupon = Coupon::findBy('code', strtoupper(trim($couponCode)));
                    if ($coupon === null) {
                        throw new RuntimeException('کوپن واردشده معتبر نیست.');
                    }
                    $couponError = $coupon->validateFor($details['subtotal']);
                    if ($couponError !== null) {
                        throw new RuntimeException($couponError);
                    }
                    $discount = $coupon->discountFor($details['subtotal']);
                }

                // ۳) هزینهٔ ارسال
                $net = max(0, $details['subtotal'] - $discount);
                $shipping = $net >= self::FREE_SHIPPING_OVER ? 0 : self::SHIPPING_FLAT;

                // ۴) سفارش با کد ORD سال شمسی
                $order = Order::create([
                    'code'          => CodeGenerator::orderCode(Jalali::currentYear()),
                    'customer_id'   => $customer->id(),
                    'user_id'       => $userId,
                    'agency_id'     => $agencyId,
                    'channel'       => $channel,
                    'status'        => $channel === 'agency' ? 'delivered' : 'pending',
                    'subtotal'      => $details['subtotal'],
                    'discount'      => $discount,
                    'shipping_cost' => $shipping,
                    'total'         => $net + $shipping,
                    'receiver'      => (string) ($info['receiver'] ?? $customer->name),
                    'phone'         => normalize_phone((string) $info['phone']),
                    'province'      => $info['province'] ?? null,
                    'city'          => $info['city'] ?? null,
                    'address'       => $info['address'] ?? null,
                    'postal_code'   => $info['postal_code'] ?? null,
                    'note'          => $info['note'] ?? null,
                    'placed_at'     => date('Y-m-d H:i:s'),
                    'delivered_at'  => $channel === 'agency' ? date('Y-m-d H:i:s') : null,
                ]);

                // ۵) اقلام + کسر انبار در همان تراکنش
                foreach ($details['items'] as $item) {
                    $affected = Database::update(
                        'UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?',
                        [$item['qty'], $item['id'], $item['qty']]
                    );
                    if ($affected === 0) {
                        throw new RuntimeException("موجودی «{$item['product_name']}» در لحظهٔ ثبت سفارش تغییر کرده است. لطفاً سبد را بازبینی کنید.");
                    }
                    OrderItem::create([
                        'order_id'   => $order->id(),
                        'variant_id' => (int) $item['id'],
                        'sku'        => $item['sku'],
                        'title'      => $item['product_name'],
                        'unit_price' => (int) $item['price'],
                        'quantity'   => (int) $item['qty'],
                        'total'      => (int) $item['line_total'],
                    ]);
                    Database::insert(
                        'INSERT INTO inventory_movements (variant_id, direction, quantity, ref_type, ref_id, user_id, note, created_at)
                         VALUES (?, "out", ?, "sale", ?, ?, ?, NOW())',
                        [$item['id'], $item['qty'], $order->id(), $userId, 'فروش — ' . $order->code]
                    );
                }

                // ۶) فاکتور (کد INV)
                Invoice::create([
                    'code'      => CodeGenerator::invoiceCode(Jalali::currentYear()),
                    'order_id'  => $order->id(),
                    'amount'    => $net + $shipping,
                    'paid'      => $channel === 'agency' ? 1 : 0,
                    'method'    => $channel === 'agency' ? 'pos' : 'card',
                    'issued_at' => date('Y-m-d H:i:s'),
                    'paid_at'   => $channel === 'agency' ? date('Y-m-d H:i:s') : null,
                ]);

                if ($coupon !== null) {
                    $coupon->update(['used_count' => (int) $coupon->used_count + 1]);
                }

                return ['order' => $order];
            });
        } catch (RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        /** @var Order $order */
        $order = $result['order'];
        CartService::clear();

        // ۷) رویداد — معماری رویدادمحور (ED)
        Events::dispatch('order.placed', [
            'order'    => $order,
            '_entity'  => 'order',
            '_entity_id' => $order->id(),
        ]);

        return ['ok' => true, 'order' => $order, 'code' => $order->code, 'id' => $order->id()];
    }

    /** هزینهٔ ارسال برای یک مبلغ */
    public static function shippingFor(int $subtotal): int
    {
        return $subtotal >= self::FREE_SHIPPING_OVER ? 0 : self::SHIPPING_FLAT;
    }
}
