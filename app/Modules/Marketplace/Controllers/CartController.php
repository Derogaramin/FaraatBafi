<?php
/**
 * کنترلر سبد خرید — نمایش، افزودن، به‌روزرسانی، حذف
 * پشتیبانی از درخواست‌های AJAX (پاسخ JSON) و فرم کلاسیک
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Controllers;

use App\Modules\Marketplace\Services\CartService;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

final class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $details = CartService::details();
        return $this->view('Marketplace::cart', [
            'title'    => 'سبد خرید',
            'details'  => $details,
            'shipping' => \App\Modules\Marketplace\Services\CheckoutService::shippingFor($details['subtotal']),
            'freeShipOver' => \App\Modules\Marketplace\Services\CheckoutService::FREE_SHIPPING_OVER,
        ]);
    }

    /** محتوای سبد کشویی (HTML جزئی — بدون لایه) برای بارگذاری AJAX */
    public function drawer(Request $request): Response
    {
        $details = CartService::details();
        return Response::html(\Core\View::capture('Marketplace::drawer', [
            'details'  => $details,
            'shipping' => \App\Modules\Marketplace\Services\CheckoutService::shippingFor($details['subtotal']),
        ]));
    }

    public function add(Request $request): Response
    {
        $result = CartService::add($request->str('sku'), max(1, $request->int('qty', 1)));
        if ($request->wantsJson()) {
            return $this->json([
                'ok'         => $result['ok'],
                'message'    => $result['message'] ?? $result['error'] ?? '',
                'cart_count' => CartService::count(),
            ], $result['ok'] ? 200 : 422);
        }
        Session::flash($result['ok'] ? 'success' : 'error', $result['message'] ?? $result['error'] ?? '');
        return $this->back(url('marketplace'));
    }

    public function update(Request $request): Response
    {
        $result = CartService::update($request->str('sku'), $request->int('qty', 0));
        if ($request->wantsJson()) {
            return $this->json(['ok' => $result['ok'], 'message' => $result['message'] ?? $result['error'] ?? ''], $result['ok'] ? 200 : 422);
        }
        Session::flash($result['ok'] ? 'success' : 'error', $result['message'] ?? $result['error'] ?? '');
        return $this->redirect(url('cart'));
    }

    public function remove(Request $request): Response
    {
        $result = CartService::remove($request->str('sku'));
        if ($request->wantsJson()) {
            return $this->json(['ok' => true, 'cart_count' => CartService::count()]);
        }
        Session::flash('info', $result['message']);
        return $this->redirect(url('cart'));
    }

    public function clear(Request $request): Response
    {
        CartService::clear();
        if ($request->wantsJson()) {
            return $this->json(['ok' => true, 'cart_count' => 0]);
        }
        Session::flash('info', 'سبد خرید خالی شد.');
        return $this->redirect(url('marketplace'));
    }
}
