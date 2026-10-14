<?php
/**
 * کنترلر مدیریت سفارش‌ها در پنل مدیر
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Controllers;

use App\Modules\Marketplace\Models\Order;
use App\Modules\Marketplace\Models\Shipment;
use Core\Business\OrderFlow;
use Core\CodeGenerator;
use Core\Controller;
use Core\Database;
use Core\Events;
use Core\Jalali;
use Core\Request;
use Core\Response;
use Core\Statuses;
use RuntimeException;

final class AdminOrderController extends Controller
{
    /** فهرست سفارش‌ها با فیلتر وضعیت/کانال/جست‌وجو */
    public function index(Request $request): Response
    {
        $status  = $request->str('status');
        $channel = $request->str('channel');
        $q       = $request->str('q');

        // پرس‌وجوی مستقیم (به‌دلیل join با alias)
        $where = ['1=1'];
        $bindings = [];
        if ($status !== '') {
            $where[] = 'o.status = ?';
            $bindings[] = $status;
        }
        if ($channel !== '') {
            $where[] = 'o.channel = ?';
            $bindings[] = $channel;
        }
        if ($q !== '') {
            $where[] = '(o.code LIKE ? OR o.receiver LIKE ? OR o.phone LIKE ?)';
            $like = '%' . en_digits($q) . '%';
            array_push($bindings, $like, $like, $like);
        }
        $whereSql = implode(' AND ', $where);

        // مرتب‌سازی با whitelist (نقشهٔ راه ۱٫۵٫۲ — جلوگیری از تزریق در ORDER BY)
        $sortWhitelist = ['id' => 'o.id', 'total' => 'o.total', 'placed_at' => 'o.placed_at', 'status' => 'o.status'];
        $sort = $sortWhitelist[$request->str('sort', 'id')] ?? 'o.id';
        $dir = strtolower($request->str('dir', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $perPage = 15;
        $page = max(1, $request->int('page', 1));
        $total = (int) (Database::selectOne("SELECT COUNT(*) AS c FROM orders o WHERE {$whereSql}", $bindings)['c'] ?? 0);
        $orders = Database::select(
            "SELECT o.*, c.name AS customer_name, c.code AS customer_code, a.name AS agency_name
             FROM orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             LEFT JOIN agencies a ON a.id = o.agency_id
             WHERE {$whereSql}
             ORDER BY {$sort} {$dir}, o.id DESC
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $bindings
        );

        return $this->view('Marketplace::admin.orders', [
            'title'      => 'سفارش‌های فروش',
            'orders'     => $orders,
            'filters'    => compact('status', 'channel', 'q') + ['sort' => $request->str('sort', 'id'), 'dir' => strtolower($request->str('dir', 'desc'))],
            'statuses'   => Statuses::map('order'),
            'channels'   => Statuses::CHANNEL,
            'paginator'  => new \Core\Paginator($orders, $total, $perPage, $page),
        ]);
    }

    /** خروجی CSV سفارش‌ها با همان فیلترهای جاری (نقشهٔ راه ۱٫۵٫۲) */
    public function exportCsv(Request $request): Response
    {
        $where = ['1=1'];
        $bindings = [];
        if (($status = $request->str('status')) !== '') { $where[] = 'o.status = ?'; $bindings[] = $status; }
        if (($channel = $request->str('channel')) !== '') { $where[] = 'o.channel = ?'; $bindings[] = $channel; }
        if (($q = $request->str('q')) !== '') {
            $where[] = '(o.code LIKE ? OR o.receiver LIKE ? OR o.phone LIKE ?)';
            $like = '%' . en_digits($q) . '%';
            array_push($bindings, $like, $like, $like);
        }
        $rows = Database::select(
            'SELECT o.*, c.name AS customer_name FROM orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY o.id DESC LIMIT 5000',
            $bindings
        );
        $out = "\xEF\xBB\xBF" . "کد سفارش;مشتری;گیرنده;موبایل;کانال;وضعیت;جمع اقلام;تخفیف;ارسال;مبلغ کل;تاریخ ثبت\n";
        foreach ($rows as $r) {
            $out .= implode(';', [
                $r['code'],
                str_replace(';', '،', (string) ($r['customer_name'] ?? '')),
                str_replace(';', '،', (string) $r['receiver']),
                $r['phone'],
                Statuses::CHANNEL[$r['channel']] ?? $r['channel'],
                Statuses::label('order', $r['status']),
                $r['subtotal'], $r['discount'], $r['shipping_cost'], $r['total'],
                Jalali::formatSql($r['placed_at'], 'Y/m/d', true),
            ]) . "\n";
        }
        return Response::download($out, 'faraat-orders-' . Jalali::format('Y-m-d', null, false) . '.csv', 'text/csv; charset=UTF-8');
    }

    /** نسخهٔ چاپی فاکتور سفارش (نقشهٔ راه ۱٫۵٫۳) */
    public function printInvoice(Request $request): Response
    {
        $order = Order::find($request->int('id'));
        if ($order === null) {
            throw new \Core\HttpException(404);
        }
        $id = (int) $order->id();
        return $this->view('Marketplace::admin.print-invoice', [
            'title'    => 'فاکتور ' . ($order->invoice_code ?? $order->code),
            'order'    => $order->attributes(),
            'items'    => Database::select('SELECT * FROM order_items WHERE order_id = ?', [$id]),
            'invoice'  => Database::selectOne('SELECT * FROM invoices WHERE order_id = ?', [$id]),
            'customer' => $order->customer_id ? Database::selectOne('SELECT * FROM customers WHERE id = ?', [$order->customer_id]) : null,
        ]);
    }

    /** جزئیات سفارش + اقلام + فاکتور + محموله + WOهای متصل */
    public function show(Request $request): Response
    {
        $order = Order::find($request->int('id'));
        if ($order === null) {
            throw new \Core\HttpException(404, 'سفارش یافت نشد.');
        }
        $id = (int) $order->id();

        return $this->view('Marketplace::admin.order-detail', [
            'title'    => 'سفارش ' . $order->code,
            'order'    => $order->attributes(),
            'items'    => Database::select('SELECT oi.*, pv.image FROM order_items oi LEFT JOIN product_variants pv ON pv.id = oi.variant_id WHERE oi.order_id = ?', [$id]),
            'invoice'  => Database::selectOne('SELECT * FROM invoices WHERE order_id = ?', [$id]),
            'receipts' => Database::select('SELECT * FROM receipts WHERE order_id = ? ORDER BY id DESC', [$id]),
            'shipment' => Database::selectOne('SELECT * FROM shipments WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$id]),
            'workOrders' => Database::select(
                'SELECT wo.*, w.name AS weaver_name, w.code AS weaver_code, pv.sku
                 FROM work_orders wo
                 LEFT JOIN weavers w ON w.id = wo.weaver_id
                 JOIN product_variants pv ON pv.id = wo.variant_id
                 WHERE wo.order_id = ?',
                [$id]
            ),
            'statuses' => Statuses::map('order'),
            'allowed'  => OrderFlow::allowedFor((string) $order->status),
            'audits'   => \Core\Audit::forEntity('order', $id, 8),
            'customer' => $order->customer_id
                ? Database::selectOne('SELECT * FROM customers WHERE id = ?', [$order->customer_id])
                : null,
            'agency'   => $order->agency_id
                ? Database::selectOne('SELECT * FROM agencies WHERE id = ?', [$order->agency_id])
                : null,
        ]);
    }

    /** تغییر وضعیت سفارش — با رویداد ED */
    public function changeStatus(Request $request): Response
    {
        $order = Order::find($request->int('id'));
        if ($order === null) {
            throw new \Core\HttpException(404);
        }
        $newStatus = $request->str('status');
        try {
            // ماشین حالت متمرکز (نقد §۳٫۵): گذار نامعتبر → استثنا
            OrderFlow::change($order, $newStatus);
        } catch (RuntimeException $e) {
            return $this->backWith(url('admin/orders/' . $order->id()), 'error', $e->getMessage());
        }
        return $this->backWith(url('admin/orders/' . $order->id()), 'success', 'وضعیت سفارش به «' . Statuses::label('order', $newStatus) . '» تغییر کرد.');
    }

    /** ثبت/به‌روزرسانی محمولهٔ ارسال */
    public function ship(Request $request): Response
    {
        $order = Order::find($request->int('id'));
        if ($order === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'carrier'     => 'required|max:120',
            'tracking_no' => 'max:60',
            'cost'        => 'numeric|min:0',
        ], ['carrier' => 'شرکت حمل', 'tracking_no' => 'کد رهگیری', 'cost' => 'هزینه']);

        Shipment::create([
            'code'       => CodeGenerator::shipmentCode(Jalali::currentYear()),
            'order_id'   => (int) $order->id(),
            'carrier'    => (string) $data['carrier'],
            'tracking_no'=> $data['tracking_no'] ?? null,
            'cost'       => (int) ($data['cost'] ?? 0),
            'status'     => 'sent',
            'shipped_at' => date('Y-m-d H:i:s'),
        ]);
        $order->changeStatus('shipped');
        Events::dispatch('order.status_changed', ['order' => $order, 'status' => 'shipped']);

        return $this->backWith(url('admin/orders/' . $order->id()), 'success', 'محموله ثبت و سفارش ارسال‌شده شد.');
    }

    /** ثبت دریافت وجه و صدور رسید */
    public function markPaid(Request $request): Response
    {
        $order = Order::find($request->int('id'));
        if ($order === null) {
            throw new \Core\HttpException(404);
        }
        Database::transaction(function () use ($order, $request): void {
            Database::insert(
                'INSERT INTO receipts (code, order_id, amount, method, received_by, received_at, note, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW())',
                [
                    CodeGenerator::receiptCode(Jalali::currentYear()),
                    $order->id(),
                    (int) $order->total,
                    in_array($request->str('method'), ['cash', 'pos', 'card', 'wallet'], true) ? $request->str('method') : 'card',
                    \Core\Auth::id(),
                    'دریافت وجه سفارش ' . $order->code,
                ]
            );
            Database::update('UPDATE invoices SET paid = 1, paid_at = NOW() WHERE order_id = ?', [$order->id()]);
        });
        return $this->backWith(url('admin/orders/' . $order->id()), 'success', 'پرداخت ثبت و فاکتور تسویه شد.');
    }

    private function backWith(?string $fallback, string $type, string $message): Response
    {
        \Core\Session::flash($type, $message);
        return $this->redirect($fallback ?? ($_SERVER['HTTP_REFERER'] ?? url('admin/orders')));
    }
}
