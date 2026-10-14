<?php
/**
 * سرویس دستمزد بافندگان (Payroll)
 *
 * پس از تأیید کیفیت هر سفارش تولید، فیش دستمزد (PAY-W###-سال-سری) صادر می‌شود.
 * با پرداخت فیش، ماندهٔ حساب بافنده تسویه و وضعیت WO «تکمیل» می‌شود.
 */

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Payment;
use App\Modules\Members\Models\Weaver;
use App\Modules\Production\Models\WorkOrder;
use Core\Auth;
use Core\CodeGenerator;
use Core\Database;
use Core\Events;
use Core\Jalali;
use RuntimeException;

final class PayrollService
{
    /** صدور فیش دستمزد برای یک سفارش تولید تأییدشده (idempotent) */
    public static function issueForWorkOrder(WorkOrder $wo): ?Payment
    {
        if ($wo->weaver_id === null) {
            return null;
        }
        $weaver = Weaver::find((int) $wo->weaver_id);
        if ($weaver === null) {
            return null;
        }
        // قبلاً صادر شده؟
        $exists = Database::selectOne(
            'SELECT id FROM payments WHERE work_order_id = ? LIMIT 1',
            [$wo->id()]
        );
        if ($exists) {
            return Payment::find((int) $exists['id']);
        }

        $amount = $wo->totalWage();
        if ($amount <= 0) {
            return null;
        }

        $payment = Payment::create([
            'code'          => CodeGenerator::paymentCode((string) $weaver->code, Jalali::currentYear()),
            'weaver_id'     => (int) $wo->weaver_id,
            'work_order_id' => $wo->id(),
            'amount'        => $amount,
            'period'        => Jalali::format('Y-m'),
            'method'        => 'bank',
            'status'        => 'pending',
        ]);

        // بدهی تعاونی به بافنده افزایش می‌یابد
        Database::update('UPDATE weavers SET balance = balance + ? WHERE id = ?', [$amount, $weaver->id()]);

        Events::dispatch('payment.issued', ['payment' => $payment, 'weaver' => $weaver]);
        return $payment;
    }

    /** صدور فیش دستی (پیش‌پرداخت/تسویهٔ دوره‌ای) */
    public static function issueManual(int $weaverId, int $amount, ?string $period = null, ?string $note = null): Payment
    {
        $weaver = Weaver::find($weaverId);
        if ($weaver === null) {
            throw new RuntimeException('بافنده یافت نشد.');
        }
        if ($amount <= 0) {
            throw new RuntimeException('مبلغ فیش باید مثبت باشد.');
        }
        $payment = Payment::create([
            'code'      => CodeGenerator::paymentCode((string) $weaver->code, Jalali::currentYear()),
            'weaver_id' => $weaverId,
            'amount'    => $amount,
            'period'    => $period ?: Jalali::format('Y-m'),
            'method'    => 'bank',
            'status'    => 'pending',
            'note'      => $note,
        ]);
        Database::update('UPDATE weavers SET balance = balance + ? WHERE id = ?', [$amount, $weaverId]);
        Events::dispatch('payment.issued', ['payment' => $payment, 'weaver' => $weaver]);
        return $payment;
    }

    /** پرداخت فیش → تسویهٔ مانده و تکمیل سفارش تولید مرتبط */
    public static function markPaid(Payment $payment): void
    {
        if ($payment->status === 'paid') {
            throw new RuntimeException('این فیش پیش‌تر پرداخت شده است.');
        }
        Database::transaction(function () use ($payment): void {
            $payment->update([
                'status'  => 'paid',
                'paid_at' => date('Y-m-d H:i:s'),
                'paid_by' => Auth::id(),
            ]);
            Database::update('UPDATE weavers SET balance = balance - ? WHERE id = ?', [$payment->amount, $payment->weaver_id]);

            // اگر WO مرتبط در وضعیت qc_passed است → completed
            if ($payment->work_order_id) {
                $wo = WorkOrder::find((int) $payment->work_order_id);
                if ($wo !== null && $wo->status === 'qc_passed') {
                    $wo->transitionTo('completed');
                    Events::dispatch('workorder.completed', ['work_order' => $wo]);
                }
            }
        });
        Events::dispatch('payment.paid', ['payment' => $payment]);
        Events::dispatch('payment.paid.sms', ['payment' => $payment, 'weaver_id' => $payment->weaver_id]);
    }

    /** ماندهٔ حساب یک بافنده (جمع فیش‌های پرداخت‌نشده) */
    public static function pendingTotal(int $weaverId): int
    {
        $row = Database::selectOne(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE weaver_id = ? AND status = "pending"',
            [$weaverId]
        );
        return (int) ($row['total'] ?? 0);
    }

    /** گزارش دستمزد یک دورهٔ شمسی (Y-m) */
    public static function periodReport(string $period): array
    {
        return Database::select(
            'SELECT p.*, w.name AS weaver_name, w.code AS weaver_code
             FROM payments p JOIN weavers w ON w.id = p.weaver_id
             WHERE p.period = ?
             ORDER BY p.id DESC',
            [$period]
        );
    }
}
