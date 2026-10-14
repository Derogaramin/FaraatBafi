<?php
/**
 * شنونده‌های رویداد ماژول CRM — اعلان‌های کاربری
 */

declare(strict_types=1);

use App\Modules\CRM\Models\Notification;
use App\Modules\CRM\Services\Notify;
use App\Modules\Marketplace\Models\Order;
use Core\Database;
use Core\Events;
use Core\Jalali;
use Core\Sms;

return Events::register([

    'order.placed' => function (array $payload): void {
        $order = $payload['order'] ?? null;
        if (!$order instanceof Order) {
            return;
        }
        // اعلان به خود مشتری (اگر کاربر دارد)
        if ($order->user_id) {
            Notification::push(
                (int) $order->user_id,
                'سفارش شما ثبت شد',
                'سفارش ' . $order->code . ' با موفقیت ثبت و در انتظار تأیید است.',
                url('orders/' . $order->code),
                'success'
            );
        }
        // اعلان به مدیران
        foreach (Notify::adminIds() as $adminId) {
            Notification::push(
                $adminId,
                'سفارش جدید — ' . $order->code,
                'سفارشی به مبلغ ' . toman((int) $order->total) . ' از کانال ' . (\Core\Statuses::CHANNEL[$order->channel] ?? $order->channel) . ' ثبت شد.',
                url('admin/orders/' . $order->id()),
                'info'
            );
        }

        // پیامک تأیید ثبت سفارش به مشتری (نقشهٔ راه ۱٫۵٫۸)
        $customerPhone = Database::selectOne('SELECT phone FROM customers WHERE id = ?', [$order->customer_id ?? 0]);
        if ($customerPhone && !empty($customerPhone['phone'])) {
            Sms::send(
                (string) $customerPhone['phone'],
                "فرت | سفارش {$order->code} ثبت شد. مبلغ: " . number_format((int) $order->total)
                . " تومان. پیگیری: " . rtrim((string) \Core\Config::get('app.public_url'), '/') . "/orders/{$order->code}"
            );
        }
    },

    'order.status_changed' => function (array $payload): void {
        $order = $payload['order'] ?? null;
        if (!$order instanceof Order || !$order->user_id) {
            return;
        }
        $label = \Core\Statuses::label('order', (string) $order->status);
        Notification::push(
            (int) $order->user_id,
            'وضعیت سفارش ' . $order->code . ' تغییر کرد',
            'وضعیت جدید: ' . $label,
            url('orders/' . $order->code),
            'info'
        );
    },

    'order.ready' => function (array $payload): void {
        $orderId = (int) ($payload['order_id'] ?? 0);
        $row = Database::selectOne('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if (!$row || empty($row['user_id'])) {
            return;
        }
        Notification::push(
            (int) $row['user_id'],
            'سفارش ' . $row['code'] . ' آمادهٔ ارسال شد',
            'بافت سفارش شما تکمیل و آمادهٔ ارسال است.',
            url('orders/' . $row['code']),
            'success'
        );
    },

    'payment.paid' => function (array $payload): void {
        $payment = $payload['payment'] ?? null;
        if ($payment === null) {
            return;
        }
        $row = Database::selectOne('SELECT user_id FROM weavers WHERE id = ?', [$payment->weaver_id]);
        if ($row && $row['user_id']) {
            Notification::push(
                (int) $row['user_id'],
                'دستمزد شما پرداخت شد',
                'فیش ' . $payment->code . ' به مبلغ ' . toman((int) $payment->amount) . ' پرداخت گردید. خدا قوت! 🌿',
                url('weaver/payments'),
                'success'
            );
        }
    },

    'payment.paid.sms' => function (array $payload): void {
        // پیامک تسویهٔ دستمزد به بافنده
        $payment = $payload['payment'] ?? null;
        if ($payment === null) {
            return;
        }
        $phone = Notify::weaverPhone((int) $payment->weaver_id);
        if ($phone !== null) {
            Sms::send($phone, 'فرت | دستمزد شما پرداخت شد. فیش: ' . $payment->code . ' — مبلغ: ' . number_format((int) $payment->amount) . ' تومان. خدا قوت.');
        }
    },

    'ticket.replied' => function (array $payload): void {
        $ticket = $payload['ticket'] ?? null;
        $byStaff = (bool) ($payload['by_staff'] ?? false);
        if ($ticket === null) {
            return;
        }
        $row = Database::selectOne('SELECT user_id FROM tickets WHERE id = ?', [$ticket->id()]);
        if ($row && $byStaff && (int) $row['user_id'] !== \Core\Auth::id()) {
            Notification::push(
                (int) $row['user_id'],
                'پاسخ تیکت ' . $ticket->code,
                'تیکت «' . $ticket->subject . '» پاسخ داده شد.',
                url('my/tickets/' . $ticket->id()),
                'info'
            );
        }
    },
]);
