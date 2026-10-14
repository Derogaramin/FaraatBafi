<?php
/**
 * کلاس OrderFlow — ماشین حالت متمرکز سفارش فروش
 *
 * هر تغییر وضعیت باید از اینجا عبور کند (نقد §۳٫۵ و §۱۰):
 *  - اعتبارسنجی گذار با OrderStatus enum
 *  - ثبت زمان‌ها (delivered_at)
 *  - بازگشت موجودی در صورت لغو/مرجوعی
 *  - انتشار رویدادهای ED (order.status_changed / order.confirmed)
 *  - ممیزی تغییر وضعیت (field_audits)
 */

declare(strict_types=1);

namespace Core\Business;

use App\Modules\Marketplace\Models\Order;
use Core\Audit;
use Core\Auth;
use Core\Database;
use Core\Enums\OrderStatus;
use Core\Events;
use RuntimeException;

final class OrderFlow
{
    /** گذارهای مجاز از وضعیت فعلی (برای ویوها) */
    public static function allowedFor(string $status): array
    {
        $current = OrderStatus::tryFrom($status);
        if ($current === null) {
            return [];
        }
        return array_map(static fn (OrderStatus $c) => $c->value, $current->allowedTransitions());
    }

    public static function canChange(Order $order, string $to): bool
    {
        $from = OrderStatus::tryFrom((string) $order->status);
        $target = OrderStatus::tryFrom($to);
        return $from !== null && $target !== null && $from->canTransitionTo($target);
    }

    /**
     * تغییر وضعیت سفارش با همهٔ اثرات جانبی.
     *
     * @throws RuntimeException اگر گذار مجاز نباشد
     */
    public static function change(Order $order, string $to, ?string $note = null): void
    {
        $from = (string) $order->status;
        if (!self::canChange($order, $to)) {
            throw new RuntimeException(
                'گذار سفارش از «' . \Core\Statuses::label('order', $from) . '» به «'
                . \Core\Statuses::label('order', $to) . '» مجاز نیست.'
            );
        }

        Database::transaction(static function () use ($order, $from, $to): void {
            // لغو/مرجوعی → بازگشت موجودی اقلام
            if (in_array($to, [OrderStatus::Cancelled->value, OrderStatus::Returned->value], true)
                && !in_array($from, [OrderStatus::Cancelled->value, OrderStatus::Returned->value], true)) {
                foreach ($order->items() as $item) {
                    if ($item['variant_id'] === null) {
                        continue;
                    }
                    Database::update(
                        'UPDATE product_variants SET stock = stock + ? WHERE id = ?',
                        [$item['quantity'], $item['variant_id']]
                    );
                    Database::insert(
                        'INSERT INTO inventory_movements (variant_id, direction, quantity, ref_type, ref_id, user_id, note, created_at)
                         VALUES (?, "in", ?, "return", ?, ?, ?, NOW())',
                        [$item['variant_id'], $item['quantity'], $order->id(), Auth::id(), 'بازگشت موجودی — ' . $order->code]
                    );
                }
            }

            $order->changeStatus($to);
            if ($to === OrderStatus::Delivered->value) {
                Database::update('UPDATE invoices SET paid = 1, paid_at = COALESCE(paid_at, NOW()) WHERE order_id = ?', [$order->id()]);
            }
        });

        Audit::log('order', (int) $order->id(), (string) $order->code, 'status', $from, $to, $note);

        Events::dispatch('order.status_changed', ['order' => $order, 'status' => $to, 'from' => $from]);
        if ($to === OrderStatus::Confirmed->value) {
            Events::dispatch('order.confirmed', ['order' => $order]);
        }
        if ($to === OrderStatus::Cancelled->value) {
            Events::dispatch('order.cancelled', ['order' => $order]);
        }
    }
}
