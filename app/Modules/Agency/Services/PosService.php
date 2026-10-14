<?php
/**
 * سرویس فروش حضوری نمایندگی (POS)
 *
 * جریان: اسکن/ورود SKU → افزودن به سبد نمایندگی → ثبت فروش
 *  → سفارش با کانال agency (تحویل‌شده) + فاکتور پرداخت‌شده + رسید
 *  → کسر از کالای امانی + ثبت پورسانت (COM-A##-سال-سری)
 */

declare(strict_types=1);

namespace App\Modules\Agency\Services;

use App\Modules\Finance\Models\Commission;
use App\Modules\Members\Models\Agency;
use App\Modules\Marketplace\Services\CartService;
use App\Modules\Marketplace\Services\CheckoutService;
use App\Modules\Production\Models\Consignment;
use Core\CodeGenerator;
use Core\Database;
use Core\Events;
use Core\Jalali;
use Core\Session;
use RuntimeException;

final class PosService
{
    /**
     * ثبت فروش حضوری از سبد جاری نشست.
     *
     * @return array{ok:bool, code?:string, id?:int, error?:string}
     */
    public static function sell(Agency $agency, array $buyerInfo): array
    {
        $details = CartService::details();
        if ($details['items'] === []) {
            return ['ok' => false, 'error' => 'سبد فروش خالی است.'];
        }
        if ((int) $agency->status !== 1 && $agency->status !== 'active') {
            return ['ok' => false, 'error' => 'نمایندگی غیرفعال است.'];
        }

        // اعمال سقف اعتبار (نقد §۹): ماندهٔ بدهی + ارزش کالای امانی + این فروش
        $creditLimit = (int) $agency->credit_limit;
        if ($creditLimit > 0) {
            $exposure = self::creditExposure($agency);
            if ($exposure + $details['subtotal'] > $creditLimit) {
                return [
                    'ok' => false,
                    'error' => 'سقف اعتبار نمایندگی کافی نیست. تعرض فعلی: ' . toman($exposure)
                        . '، سقف: ' . toman($creditLimit) . '. برای ادامه، تسویه یا افزایش سقف لازم است.',
                ];
            }
        }

        $result = CheckoutService::place(
            $buyerInfo + ['receiver' => $buyerInfo['receiver'] ?? $agency->name],
            null,
            (int) $agency->id(),
            'agency'
        );
        if (!$result['ok']) {
            return $result;
        }

        $order = $result['order'];

        Database::transaction(function () use ($order, $agency, $details): void {
            // رسید نقدی (RCP)
            Database::insert(
                'INSERT INTO receipts (code, order_id, amount, method, received_by, received_at, note, created_at)
                 VALUES (?, ?, ?, "pos", ?, NOW(), ?, NOW())',
                [
                    CodeGenerator::receiptCode(Jalali::currentYear()),
                    $order->id(),
                    (int) $order->total,
                    $agency->user_id,
                    'فروش حضوری — ' . $order->code,
                ]
            );

            // کسر از کالای امانی نمایندگی
            foreach ($details['items'] as $item) {
                Database::update(
                    'UPDATE consignments SET quantity = quantity - ?, updated_at = NOW() WHERE agency_id = ? AND variant_id = ?',
                    [$item['qty'], $agency->id(), $item['id']]
                );
            }

            // پورسانت نمایندگی (COM)
            $base = (int) $order->subtotal;
            $rate = (float) $agency->commission_rate;
            $amount = (int) round($base * $rate / 100);
            Commission::create([
                'code'        => CodeGenerator::commissionCode((string) $agency->code, Jalali::currentYear()),
                'agency_id'   => (int) $agency->id(),
                'order_id'    => $order->id(),
                'base_amount' => $base,
                'rate'        => $rate,
                'amount'      => $amount,
                'period'      => Jalali::format('Y-m'),
                'status'      => 'pending',
            ]);
        });

        Events::dispatch('pos.sale', ['order' => $order, 'agency' => $agency]);
        Session::forget('cart');

        return ['ok' => true, 'code' => $order->code, 'id' => $order->id()];
    }

    /** تعرض اعتبار نمایندگی: بدهی نقدی + ارزش کالای امانی نزد او */
    public static function creditExposure(Agency $agency): int
    {
        $debt = (int) $agency->balance;
        $consignment = (int) (Database::selectOne(
            'SELECT COALESCE(SUM(GREATEST(cs.quantity, 0) * pv.price), 0) AS v
             FROM consignments cs JOIN product_variants pv ON pv.id = cs.variant_id
             WHERE cs.agency_id = ?',
            [$agency->id()]
        )['v'] ?? 0);
        return $debt + $consignment;
    }

    /** ارسال کالای امانی به نمایندگی */
    public static function consign(Agency $agency, int $variantId, int $qty): void
    {
        if ($qty <= 0) {
            throw new RuntimeException('تعداد باید مثبت باشد.');
        }
        $affected = Database::update(
            'UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?',
            [$qty, $variantId, $qty]
        );
        if ($affected === 0) {
            throw new RuntimeException('موجودی انبار مرکزی کافی نیست.');
        }
        Database::statement(
            'INSERT INTO consignments (agency_id, variant_id, quantity, sent_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at = NOW()',
            [$agency->id(), $variantId, $qty]
        );
        Database::insert(
            'INSERT INTO inventory_movements (variant_id, direction, quantity, ref_type, ref_id, note, created_at)
             VALUES (?, "out", ?, "consignment", ?, ?, NOW())',
            [$variantId, $qty, $agency->id(), 'کالای امانی → ' . $agency->name]
        );
    }

    /** موجودی امانی یک نمایندگی با جزئیات ویو */
    public static function consignmentOf(Agency $agency): array
    {
        return Database::select(
            'SELECT cs.*, pv.sku, pv.price, pv.image, pm.name_fa AS product_name, c.name_fa AS color_name
             FROM consignments cs
             JOIN product_variants pv ON pv.id = cs.variant_id
             JOIN product_models pm ON pm.id = pv.model_id
             JOIN colors c ON c.id = pv.color_id
             WHERE cs.agency_id = ?
             ORDER BY cs.updated_at DESC',
            [$agency->id()]
        );
    }
}
