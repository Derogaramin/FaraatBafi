<?php
/**
 * دانه‌گذار ۰۴ — فروش: کوپن، سفارش‌ها در کانال‌ها و وضعیت‌های گوناگون،
 * فاکتور، رسید، بارنامه و پورسانت نمایندگی
 */

declare(strict_types=1);

use App\Modules\Finance\Models\Commission;
use App\Modules\Marketplace\Models\Coupon;
use App\Modules\Marketplace\Models\Invoice;
use App\Modules\Marketplace\Models\Order;
use App\Modules\Marketplace\Models\OrderItem;
use App\Modules\Marketplace\Models\Receipt;
use App\Modules\Marketplace\Models\Shipment;
use Core\CodeGenerator;
use Core\Database;
use Core\Jalali;

return static function (): void {
    if ((int) (Database::selectOne('SELECT COUNT(*) AS c FROM orders')['c'] ?? 0) > 0) {
        echo "  (فروش پیش‌تر دانه‌گذاری شده — رد شد)\n";
        return;
    }

    $year = Jalali::currentYear();

    /* ── کوپن‌ها ── */
    Coupon::create([
        'code' => CodeGenerator::couponCode('NOWRUZ', $year), 'title' => 'تخفیف نوروزی فرت',
        'type' => 'percent', 'value' => 15, 'max_amount' => 500_000, 'min_order' => 500_000,
        'starts_at' => date('Y-m-d', strtotime('-200 days')), 'expires_at' => date('Y-m-d', strtotime('+60 days')),
        'usage_limit' => 200, 'used_count' => 0, 'is_active' => 1,
    ]);
    Coupon::create([
        'code' => CodeGenerator::couponCode('YALDA', $year), 'title' => 'شب یلدا',
        'type' => 'amount', 'value' => 200_000, 'min_order' => 1_000_000,
        'starts_at' => date('Y-m-d', strtotime('-30 days')), 'expires_at' => date('Y-m-d', strtotime('+90 days')),
        'usage_limit' => 50, 'used_count' => 0, 'is_active' => 1,
    ]);

    $customers = array_map(fn ($r) => $r, Database::select('SELECT * FROM customers ORDER BY id LIMIT 8'));
    $agencies = array_map(fn ($r) => $r, Database::select('SELECT * FROM agencies ORDER BY id LIMIT 5'));
    $variants = Database::select('SELECT pv.*, pm.name_fa FROM product_variants pv JOIN product_models pm ON pm.id = pv.model_id ORDER BY pv.id');

    /**
     * ساخت یک سفارش کامل نمونه.
     *
     * @param array<int, array{variant: array, qty: int}> $lines
     */
    $makeOrder = static function (array $cfg, array $lines) use (&$orderSeq): Order {
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += (int) $line['variant']['price'] * $line['qty'];
        }
        $discount = (int) ($cfg['discount'] ?? 0);
        $shipping = (int) ($cfg['shipping'] ?? ($subtotal - $discount >= 3_000_000 ? 0 : 85_000));

        $order = Order::create([
            'code'          => $cfg['code'],
            'customer_id'   => $cfg['customer_id'] ?? null,
            'user_id'       => $cfg['user_id'] ?? null,
            'agency_id'     => $cfg['agency_id'] ?? null,
            'channel'       => $cfg['channel'],
            'status'        => $cfg['status'],
            'subtotal'      => $subtotal,
            'discount'      => $discount,
            'shipping_cost' => $shipping,
            'total'         => $subtotal - $discount + $shipping,
            'receiver'      => $cfg['receiver'],
            'phone'         => $cfg['phone'],
            'province'      => $cfg['province'] ?? 'خراسان رضوی',
            'city'          => $cfg['city'] ?? 'مشهد',
            'address'       => 'نشانی نمونه — خیابان امام رضا، کوچهٔ گل، پلاک ' . (10 + $orderSeq),
            'postal_code'   => '91' . str_pad((string) (37610 + $orderSeq), 8, '0', STR_PAD_LEFT),
            'placed_at'     => $cfg['placed_at'],
            'delivered_at'  => in_array($cfg['status'], ['delivered'], true) ? $cfg['placed_at'] : null,
        ]);
        $orderSeq++;

        foreach ($lines as $line) {
            OrderItem::create([
                'order_id'   => $order->id(),
                'variant_id' => (int) $line['variant']['id'],
                'sku'        => $line['variant']['sku'],
                'title'      => $line['variant']['name_fa'],
                'unit_price' => (int) $line['variant']['price'],
                'quantity'   => $line['qty'],
                'total'      => (int) $line['variant']['price'] * $line['qty'],
            ]);
        }

        $invoice = Invoice::create([
            'code'      => CodeGenerator::invoiceCode((int) substr($cfg['code'], 4, 4)),
            'order_id'  => $order->id(),
            'amount'    => (int) $order->total,
            'paid'      => in_array($cfg['status'], ['ready', 'shipped', 'delivered'], true) ? 1 : 0,
            'method'    => $cfg['channel'] === 'agency' ? 'pos' : 'card',
            'issued_at' => $cfg['placed_at'],
            'paid_at'   => in_array($cfg['status'], ['ready', 'shipped', 'delivered'], true) ? $cfg['placed_at'] : null,
        ]);

        if ($invoice->paid) {
            Receipt::create([
                'code'        => CodeGenerator::receiptCode((int) substr($cfg['code'], 4, 4)),
                'order_id'    => $order->id(),
                'invoice_id'  => $invoice->id(),
                'amount'      => (int) $order->total,
                'method'      => $cfg['channel'] === 'agency' ? 'pos' : 'card',
                'received_at' => $cfg['placed_at'],
                'note'        => 'دریافت وجه سفارش ' . $order->code,
            ]);
        }

        if (in_array($cfg['status'], ['shipped', 'delivered'], true)) {
            Shipment::create([
                'code'       => CodeGenerator::shipmentCode((int) substr($cfg['code'], 4, 4)),
                'order_id'   => $order->id(),
                'carrier'    => 'پست پیشتاز',
                'tracking_no'=> 'PST' . random_int(100000000, 999999999),
                'cost'       => $shipping,
                'status'     => $cfg['status'] === 'delivered' ? 'delivered' : 'in_transit',
                'shipped_at' => $cfg['placed_at'],
                'delivered_at' => $cfg['status'] === 'delivered' ? $cfg['placed_at'] : null,
            ]);
        }

        return $order;
    };

    $orderSeq = 0;
    $sqlToTs = static fn (int $jy, int $jm, int $jd, string $time = '10:30:00'): string =>
        Jalali::jalaliDateToSql($jy, $jm, $jd) . ' ' . $time;

    // سال مالی جاری و گذشته برای نمودارهای چندماهه
    $orders = [
        // سفارش‌های تحویل‌شده در ماه‌های گذشته (برای نمودار درآمد)
        ['status' => 'delivered', 'channel' => 'web', 'jm' => 2, 'jd' => 12, 'lines' => [[0, 2], [7, 1]], 'c' => 0],
        ['status' => 'delivered', 'channel' => 'web', 'jm' => 3, 'jd' => 5,  'lines' => [[8, 1]], 'c' => 1],
        ['status' => 'delivered', 'channel' => 'agency', 'jm' => 3, 'jd' => 22, 'lines' => [[5, 6], [6, 4]], 'c' => 2, 'a' => 1],
        ['status' => 'delivered', 'channel' => 'web', 'jm' => 4, 'jd' => 9,  'lines' => [[1, 1], [17, 2]], 'c' => 3],
        ['status' => 'delivered', 'channel' => 'agency', 'jm' => 5, 'jd' => 18, 'lines' => [[3, 2], [15, 1]], 'c' => 4, 'a' => 2],
        ['status' => 'delivered', 'channel' => 'web', 'jm' => 6, 'jd' => 2,  'lines' => [[13, 1]], 'c' => 5, 'coupon' => 'NOWRUZ'],
        ['status' => 'shipped',   'channel' => 'web', 'jm' => 6, 'jd' => 25, 'lines' => [[0, 1], [7, 2]], 'c' => 6],
        ['status' => 'ready',     'channel' => 'web', 'jm' => 7, 'jd' => 3,  'lines' => [[4, 1]], 'c' => 7],
        ['status' => 'in_production', 'channel' => 'web', 'jm' => 7, 'jd' => 12, 'lines' => [[9, 1], [11, 1]], 'c' => 1],
        ['status' => 'confirmed', 'channel' => 'phone', 'jm' => 7, 'jd' => 18, 'lines' => [[2, 1], [16, 1]], 'c' => 3],
        ['status' => 'pending',   'channel' => 'web', 'jm' => 7, 'jd' => 21, 'lines' => [[8, 2]], 'c' => 0],
        ['status' => 'pending',   'channel' => 'web', 'jm' => 7, 'jd' => 22, 'lines' => [[5, 3]], 'c' => 4],
        ['status' => 'delivered', 'channel' => 'agency', 'jm' => 7, 'jd' => 8, 'lines' => [[6, 5]], 'c' => 6, 'a' => 0],
        ['status' => 'cancelled', 'channel' => 'web', 'jm' => 6, 'jd' => 14, 'lines' => [[12, 1]], 'c' => 2],
    ];

    $created = [];
    foreach ($orders as $i => $o) {
        $customer = $customers[$o['c'] % count($customers)];
        $lines = [];
        foreach ($o['lines'] as [$vi, $qty]) {
            $lines[] = ['variant' => $variants[$vi % count($variants)], 'qty' => $qty];
        }
        $discount = 0;
        if (!empty($o['coupon'])) {
            $subtotal = array_sum(array_map(fn ($l) => (int) $l['variant']['price'] * $l['qty'], $lines));
            $discount = min(500_000, (int) floor($subtotal * 0.15));
            Database::update('UPDATE coupons SET used_count = used_count + 1 WHERE code LIKE ?', ['CPN-' . $o['coupon'] . '-%']);
        }
        $agencyId = isset($o['a']) ? (int) $agencies[$o['a']]['id'] : null;
        $order = $makeOrder([
            'code'        => CodeGenerator::orderCode(Jalali::currentYear()),
            'customer_id' => (int) $customer['id'],
            'user_id'     => $customer['user_id'] ? (int) $customer['user_id'] : null,
            'agency_id'   => $agencyId,
            'channel'     => $o['channel'],
            'status'      => $o['status'],
            'discount'    => $discount,
            'receiver'    => $customer['name'],
            'phone'       => $customer['phone'],
            'city'        => $customer['city'] ?: 'مشهد',
            'placed_at'   => $sqlToTs(Jalali::currentYear(), $o['jm'], $o['jd'], sprintf('%02d:%02d:00', 9 + ($i % 8), ($i * 7) % 60)),
        ], $lines);
        $created[] = $order;

        // پورسانت نمایندگی
        if ($agencyId !== null && in_array($o['status'], ['delivered', 'shipped'], true)) {
            $agency = $agencies[$o['a']];
            $amount = (int) round((int) $order->subtotal * ((float) $agency['commission_rate'] / 100));
            Commission::create([
                'code'        => CodeGenerator::commissionCode((string) $agency['code'], Jalali::currentYear()),
                'agency_id'   => $agencyId,
                'order_id'    => $order->id(),
                'base_amount' => (int) $order->subtotal,
                'rate'        => (float) $agency['commission_rate'],
                'amount'      => $amount,
                'period'      => Jalali::format('Y-m', strtotime((string) $order->placed_at)),
                'status'      => $i % 3 === 0 ? 'paid' : 'pending',
                'paid_at'     => $i % 3 === 0 ? $order->placed_at : null,
            ]);
        }
    }

    // کالای امانی نزد نمایندگی‌ها
    foreach ([0, 1, 2] as $ai) {
        foreach ([0, 5, 7, 15] as $vi) {
            $variant = $variants[$vi % count($variants)];
            Database::statement(
                'INSERT INTO consignments (agency_id, variant_id, quantity, sent_at, updated_at)
                 VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL 20 DAY), NOW())',
                [(int) $agencies[$ai]['id'], (int) $variant['id'], random_int(3, 14)]
            );
        }
    }

    echo '  → ' . fa((string) count($created)) . " سفارش فروش با فاکتور/رسید/بارنامه، کوپن‌ها، پورسانت و کالای امانی\n";
};
