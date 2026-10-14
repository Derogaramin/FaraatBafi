<?php
/**
 * شنونده‌های رویداد ماژول Production (معماری رویدادمحور)
 *
 * order.confirmed      → ساخت خودکار سفارش تولید برای اقلام سفارش
 * workorder.qc_passed  → صدور کدهای اصالت FB + فیش دستمزد PAY
 * workorder.assigned   → اعلان به بافنده
 * workorder.delivered  → اعلان به مدیران برای بازرسی
 */

declare(strict_types=1);

use App\Modules\CRM\Models\Notification;
use App\Modules\Finance\Services\PayrollService;
use App\Modules\Production\Models\WorkOrder;
use App\Modules\Production\Services\ProductionService;
use Core\Database;
use Core\Events;

Events::register([

    // تأیید سفارش مشتری → صدور سفارش تولید متصل به سفارش (اگر WO باز ندارد)
    'order.confirmed' => function (array $payload): void {
        $order = $payload['order'] ?? null;
        if ($order === null) {
            return;
        }
        $items = Database::select('SELECT * FROM order_items WHERE order_id = ?', [$order->id()]);
        foreach ($items as $item) {
            $open = Database::selectOne(
                'SELECT COUNT(*) AS c FROM work_orders
                 WHERE order_id = ? AND variant_id = ? AND status NOT IN ("cancelled","completed")',
                [$order->id(), $item['variant_id']]
            );
            if ((int) ($open['c'] ?? 0) > 0) {
                continue;
            }
            ProductionService::create([
                'variant_id' => (int) $item['variant_id'],
                'order_id'   => (int) $order->id(),
                'quantity'   => (int) $item['quantity'],
                'priority'   => 'high',
                'due_date'   => date('Y-m-d', strtotime('+21 days')),
            ]);
        }
        Database::update(
            'UPDATE orders SET status = "in_production", updated_at = NOW() WHERE id = ? AND status = "confirmed"',
            [$order->id()]
        );
    },

    // تخصیص به بافنده → اعلان در پنل بافنده
    'workorder.assigned' => function (array $payload): void {
        $wo = $payload['work_order'] ?? null;
        if (!$wo instanceof WorkOrder || $wo->weaver_id === null) {
            return;
        }
        $row = Database::selectOne('SELECT user_id FROM weavers WHERE id = ?', [$wo->weaver_id]);
        $userId = (int) ($row['user_id'] ?? 0);
        if ($userId > 0) {
            Notification::push(
                $userId,
                'سفارش بافت جدید تخصیص یافت',
                'سفارش تولید ' . $wo->code . ' به شما اختصاص یافت. برای مشاهده و دریافت مواد به پنل خود مراجعه کنید.',
                url('weaver/orders/' . $wo->id()),
                'info'
            );
        }
    },

    // تحویل به انبار → اعلان به مدیران برای بازرسی کیفیت
    'workorder.delivered' => function (array $payload): void {
        $wo = $payload['work_order'] ?? null;
        if (!$wo instanceof WorkOrder) {
            return;
        }
        foreach (\App\Modules\CRM\Services\Notify::adminIds() as $adminId) {
            Notification::push(
                $adminId,
                'تحویل بافت — نیازمند بازرسی',
                'سفارش تولید ' . $wo->code . ' تحویل انبار شد. بازرسی کیفیت را ثبت کنید.',
                url('admin/production/' . $wo->id()),
                'warning'
            );
        }
    },

    // تأیید کیفیت → صدور کدهای اصالت + فیش دستمزد + اعلان
    'workorder.qc_passed' => function (array $payload): void {
        $wo = $payload['work_order'] ?? null;
        if (!$wo instanceof WorkOrder) {
            return;
        }
        $codes = ProductionService::issueAuthenticityCodes($wo);
        $payment = PayrollService::issueForWorkOrder($wo);

        $row = Database::selectOne('SELECT user_id FROM weavers WHERE id = ?', [$wo->weaver_id ?? 0]);
        $userId = (int) ($row['user_id'] ?? 0);
        if ($userId > 0) {
            Notification::push(
                $userId,
                'بافت شما تأیید کیفیت شد',
                'سفارش ' . $wo->code . ' در بازرسی کیفیت پذیرفته شد'
                . ($payment !== null ? ' و فیش دستمزد ' . $payment->code . ' صادر گردید.' : '.'),
                url('weaver/payments'),
                'success'
            );
        }
        if ($codes !== []) {
            foreach (\App\Modules\CRM\Services\Notify::adminIds() as $adminId) {
                Notification::push(
                    $adminId,
                    'کدهای اصالت صادر شد',
                    fa((string) count($codes)) . ' کد اصالت FB برای سفارش ' . $wo->code . ' صادر شد.',
                    url('admin/production/' . $wo->id()),
                    'success'
                );
            }
        }
    },
]);
