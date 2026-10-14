<?php
/**
 * کنترلر امور مالی — دستمزدها، پورسانت‌ها، گزارش‌ها، اسناد
 */

declare(strict_types=1);

namespace App\Modules\Finance\Controllers;

use App\Modules\Finance\Models\Commission;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\PayrollService;
use App\Modules\Members\Models\Weaver;
use Core\Controller;
use Core\Database;
use Core\Jalali;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Statuses;

final class AdminFinanceController extends Controller
{
    /** فیش‌های دستمزد بافندگان */
    public function payments(Request $request): Response
    {
        $status = $request->str('status');
        $weaverId = $request->int('weaver_id');
        $where = ['1=1'];
        $bindings = [];
        if ($status !== '') {
            $where[] = 'p.status = ?';
            $bindings[] = $status;
        }
        if ($weaverId > 0) {
            $where[] = 'p.weaver_id = ?';
            $bindings[] = $weaverId;
        }
        $whereSql = implode(' AND ', $where);

        $perPage = 20;
        $page = max(1, $request->int('page', 1));
        $total = (int) (Database::selectOne("SELECT COUNT(*) AS c FROM payments p WHERE {$whereSql}", $bindings)['c'] ?? 0);
        $payments = Database::select(
            "SELECT p.*, w.name AS weaver_name, w.code AS weaver_code, wo.code AS wo_code
             FROM payments p
             JOIN weavers w ON w.id = p.weaver_id
             LEFT JOIN work_orders wo ON wo.id = p.work_order_id
             WHERE {$whereSql}
             ORDER BY p.id DESC
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $bindings
        );

        $summary = Database::selectOne(
            'SELECT
                COALESCE(SUM(CASE WHEN status="paid" THEN amount END),0) AS paid_total,
                COALESCE(SUM(CASE WHEN status="pending" THEN amount END),0) AS pending_total
             FROM payments'
        ) ?: [];

        return $this->view('Finance::admin.payments', [
            'title'    => 'دستمزد بافندگان',
            'payments' => $payments,
            'summary'  => $summary,
            'weavers'  => Weaver::builder()->orderBy('code')->get(),
            'filters'  => ['status' => $status, 'weaver_id' => $weaverId],
            'statuses' => Statuses::PAYMENT,
            'paginator'=> new \Core\Paginator($payments, $total, $perPage, $page),
        ]);
    }

    /** صدور فیش دستی */
    public function storePayment(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'weaver_id' => 'required|integer|exists:weavers,id',
            'amount'    => 'required|numeric|min:1000',
            'period'    => 'jalali_date',
            'note'      => 'max:255',
        ], ['weaver_id' => 'بافنده', 'amount' => 'مبلغ', 'period' => 'دوره']);

        $period = null;
        if (!empty($data['period'])) {
            $parts = preg_split('#[/\-.]#', en_digits((string) $data['period']));
            $period = ((int) $parts[0]) . '-' . str_pad($parts[1] ?? '1', 2, '0', STR_PAD_LEFT);
        }
        try {
            $payment = PayrollService::issueManual(
                (int) $data['weaver_id'],
                (int) en_digits((string) $data['amount']),
                $period,
                $data['note'] ?? null
            );
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect(url('admin/finance/payments'));
        }
        Session::flash('success', 'فیش دستمزد «' . $payment->code . '» به مبلغ ' . toman((int) $payment->amount) . ' صادر شد. (id=' . $payment->id() . ')');
        return $this->redirect(url('admin/finance/payments'));
    }

    /** پرداخت فیش */
    public function markPaid(Request $request): Response
    {
        $payment = Payment::find($request->int('id'));
        if ($payment === null) {
            throw new \Core\HttpException(404);
        }
        try {
            PayrollService::markPaid($payment);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect(url('admin/finance/payments'));
        }
        Session::flash('success', 'فیش «' . $payment->code . '» پرداخت و ماندهٔ بافنده تسویه شد.');
        return $this->redirect(url('admin/finance/payments'));
    }

    /** نسخهٔ چاپی فیش دستمزد (نقشهٔ راه ۱٫۵٫۳) */
    public function printPayment(Request $request): Response
    {
        $payment = Payment::find($request->int('id'));
        if ($payment === null) {
            throw new \Core\HttpException(404);
        }
        $weaver = Weaver::find((int) $payment->weaver_id);
        $wo = $payment->work_order_id ? \App\Modules\Production\Models\WorkOrder::find((int) $payment->work_order_id) : null;
        return $this->view('Finance::admin.print-payment', [
            'title'    => 'فیش دستمزد ' . $payment->code,
            'payment'  => $payment->attributes(),
            'weaver'   => $weaver?->attributes(),
            'wo'       => $wo?->attributes(),
        ]);
    }

    /** پورسانت نمایندگی‌ها — فقط عضو اصلی */
    public function commissions(Request $request): Response
    {
        $this->authorizeCan('finance.view');
        return $this->view('Finance::admin.commissions', [
            'title'       => 'پورسانت نمایندگی‌ها',
            'commissions' => Database::select(
                'SELECT cm.*, a.name AS agency_name, a.code AS agency_code, o.code AS order_code
                 FROM commissions cm
                 JOIN agencies a ON a.id = cm.agency_id
                 LEFT JOIN orders o ON o.id = cm.order_id
                 ORDER BY cm.id DESC LIMIT 200'
            ),
            'statuses'    => Statuses::COMMISSION,
            'summary'     => Database::selectOne(
                'SELECT COALESCE(SUM(CASE WHEN status="paid" THEN amount END),0) AS paid,
                        COALESCE(SUM(CASE WHEN status IN ("pending","approved") THEN amount END),0) AS pending
                 FROM commissions'
            ) ?: [],
        ]);
    }

    public function commissionStatus(Request $request): Response
    {
        $commission = Commission::find($request->int('id'));
        if ($commission === null) {
            throw new \Core\HttpException(404);
        }
        $status = $request->str('status');
        if (!in_array($status, ['approved', 'paid', 'rejected'], true)) {
            Session::flash('error', 'وضعیت نامعتبر است.');
            return $this->redirect(url('admin/finance/commissions'));
        }
        $update = ['status' => $status];
        if ($status === 'paid') {
            $update['paid_at'] = date('Y-m-d H:i:s');
        }
        $commission->update($update);
        Session::flash('success', 'وضعیت پورسانت «' . $commission->code . '» به «' . Statuses::label('commission', $status) . '» تغییر کرد.');
        return $this->redirect(url('admin/finance/commissions'));
    }

    /** گزارش‌های مالی: فروش ماهانه (شمسی)، دستمزد، پورسانت */
    public function reports(Request $request): Response
    {
        // فروش ۱۲ ماه اخیر بر اساس تاریخ میلادی ذخیره‌شده → برچسب شمسی در PHP
        $salesRows = Database::select(
            "SELECT DATE_FORMAT(placed_at, '%Y-%m') AS ym,
                    COUNT(*) AS cnt, COALESCE(SUM(total),0) AS revenue
             FROM orders
             WHERE status NOT IN ('cancelled','returned') AND placed_at >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH)
             GROUP BY ym ORDER BY ym"
        );
        $sales = [];
        foreach ($salesRows as $row) {
            [$gy, $gm] = array_map('intval', explode('-', $row['ym']));
            [$jy, $jm] = Jalali::toJalali($gy, $gm, 15);
            $key = sprintf('%d-%02d', $jy, $jm);
            $sales[$key]['label'] = Jalali::MONTHS[$jm] . ' ' . Jalali::digits((string) $jy);
            $sales[$key]['cnt'] = ($sales[$key]['cnt'] ?? 0) + (int) $row['cnt'];
            $sales[$key]['revenue'] = ($sales[$key]['revenue'] ?? 0) + (int) $row['revenue'];
        }

        $wageRows = Database::select(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(amount),0) AS wages
             FROM payments WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY ym"
        );
        $wages = [];
        foreach ($wageRows as $row) {
            [$gy, $gm] = array_map('intval', explode('-', $row['ym']));
            [$jy, $jm] = Jalali::toJalali($gy, $gm, 15);
            $wages[sprintf('%d-%02d', $jy, $jm)] = (int) $row['wages'];
        }

        return $this->view('Finance::admin.reports', [
            'title'   => 'گزارش‌های مالی',
            'sales'   => $sales,
            'wages'   => $wages,
            'totals'  => Database::selectOne(
                "SELECT
                    (SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('cancelled','returned')) AS revenue_total,
                    (SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid') AS wages_paid,
                    (SELECT COALESCE(SUM(amount),0) FROM commissions WHERE status='paid') AS commissions_paid,
                    (SELECT COALESCE(SUM(balance),0) FROM weavers) AS weavers_balance"
            ) ?: [],
        ]);
    }

    /** اسناد حسابداری (ACC) — فقط عضو اصلی */
    public function documents(Request $request): Response
    {
        $this->authorizeCan('finance.view');
        $perPage = 20;
        $page = max(1, $request->int('page', 1));
        $total = (int) (Database::selectOne('SELECT COUNT(*) AS c FROM accounting_documents')['c'] ?? 0);
        return $this->view('Finance::admin.documents', [
            'title' => 'اسناد حسابداری',
            'documents' => Database::select(
                'SELECT d.*, u.name AS creator FROM accounting_documents d
                 LEFT JOIN users u ON u.id = d.created_by
                 ORDER BY d.id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage)
            ),
            'paginator' => new \Core\Paginator([], $total, $perPage, $page),
        ]);
    }

    public function storeDocument(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'title'    => 'required|min:3|max:190',
            'kind'     => 'required|in:income,expense,transfer',
            'amount'   => 'required|numeric|min:1',
            'doc_date' => 'jalali_date',
            'note'     => 'max:255',
        ], ['title' => 'شرح سند', 'amount' => 'مبلغ', 'doc_date' => 'تاریخ سند']);

        [$jy, $jm, $jd] = array_map('intval', preg_split('#[/\-.]#', en_digits((string) $data['doc_date'])));
        $doc = \App\Modules\Finance\Models\AccountingDocument::create([
            'code'       => \Core\CodeGenerator::accountingCode($jy, $jm),
            'title'      => $data['title'],
            'kind'       => $data['kind'],
            'amount'     => (int) en_digits((string) $data['amount']),
            'doc_date'   => Jalali::jalaliDateToSql($jy, $jm, $jd),
            'created_by' => \Core\Auth::id(),
            'note'       => $data['note'] ?? null,
        ]);
        Session::flash('success', 'سند «' . $doc->code . '» ثبت شد. (id=' . $doc->id() . ')');
        return $this->redirect(url('admin/finance/documents'));
    }
}
