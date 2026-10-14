<?php
/**
 * کنترلر پیگیری سفارش — با کد ORD + شمارهٔ موبایل
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Controllers;

use App\Modules\Marketplace\Models\Order;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

final class TrackingController extends Controller
{
    public function form(Request $request): Response
    {
        return $this->view('Marketplace::track', ['title' => 'پیگیری سفارش']);
    }

    public function lookup(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'code'  => 'required|max:20',
            'phone' => 'required|phone',
        ], ['code' => 'کد سفارش', 'phone' => 'شمارهٔ موبایل']);

        $order = Order::findBy('code', strtoupper(en_digits(trim((string) $data['code']))));
        if ($order === null) {
            Session::flash('error', 'سفارشی با این کد یافت نشد.');
            Session::flashInput($data);
            return $this->redirect(url('orders/track'));
        }
        // دسترسی: مالک (کاربر واردشده) یا تطابق شمارهٔ تلفن
        $isOwner = \Core\Auth::check() && (int) $order->user_id === \Core\Auth::id();
        if (!$isOwner && !$order->matchesPhone((string) $data['phone'])) {
            Session::flash('error', 'شمارهٔ تماس با این سفارش مطابقت ندارد.');
            return $this->redirect(url('orders/track'));
        }
        return $this->redirect(url('orders/' . $order->code . '?phone=' . urlencode((string) $data['phone'])));
    }

    /** نمایش وضعیت سفارش (عمومی با تلفن، یا برای مالک/مدیر) */
    public function show(Request $request): Response
    {
        $code = strtoupper($request->params['code'] ?? '');
        $order = Database::selectOne('SELECT * FROM orders WHERE code = ?', [$code]);
        if ($order === null) {
            throw new \Core\HttpException(404, 'سفارش یافت نشد.');
        }

        $isOwner = \Core\Auth::check() && (int) $order['user_id'] === \Core\Auth::id();
        $isAdmin = \Core\Auth::isAdmin();
        if (!$isOwner && !$isAdmin && !$this->phoneMatches($order, $request->str('phone'))) {
            // کاربر باید با کد + موبایل پیگیری کند
            Session::flash('warning', 'برای مشاهدهٔ این سفارش، کد سفارش و شمارهٔ موبایل را وارد کنید.');
            Session::flashInput(['code' => $code]);
            return $this->redirect(url('orders/track'));
        }

        $items = Database::select(
            'SELECT oi.*, pv.image FROM order_items oi
             LEFT JOIN product_variants pv ON pv.id = oi.variant_id
             WHERE oi.order_id = ?',
            [$order['id']]
        );
        $shipment = Database::selectOne('SELECT * FROM shipments WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$order['id']]);
        $invoice = Database::selectOne('SELECT * FROM invoices WHERE order_id = ?', [$order['id']]);

        return $this->view('Marketplace::order-status', [
            'title'    => 'پیگیری سفارش ' . $order['code'],
            'order'    => $order,
            'items'    => $items,
            'shipment' => $shipment,
            'invoice'  => $invoice,
        ]);
    }

    private function phoneMatches(array $order, string $phone): bool
    {
        return $phone !== '' && normalize_phone((string) $order['phone']) === normalize_phone($phone);
    }
}
