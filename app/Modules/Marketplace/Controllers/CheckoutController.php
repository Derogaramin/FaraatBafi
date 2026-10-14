<?php
/**
 * کنترلر پرداخت — فرم اطلاعات ارسال و ثبت نهایی سفارش
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Controllers;

use App\Modules\Marketplace\Models\Coupon;
use App\Modules\Marketplace\Services\CartService;
use App\Modules\Marketplace\Services\CheckoutService;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

final class CheckoutController extends Controller
{
    public function show(Request $request): Response
    {
        $details = CartService::details();
        if ($details['items'] === []) {
            Session::flash('warning', 'سبد خرید شما خالی است.');
            return $this->redirect(url('marketplace'));
        }

        // پیش‌پُرکردن فرم از پروفایل کاربر واردشده
        $prefill = ['receiver' => '', 'phone' => '', 'province' => '', 'city' => '', 'address' => '', 'postal_code' => ''];
        if (Auth::check()) {
            $user = Auth::user();
            $prefill['receiver'] = (string) $user['name'];
            $prefill['phone'] = (string) $user['phone'];
            $customer = Auth::role() === 'customer' ? Auth::profile() : null;
            if ($customer) {
                $prefill['province'] = (string) ($customer['province'] ?? '');
                $prefill['city'] = (string) ($customer['city'] ?? '');
                $prefill['address'] = (string) ($customer['address'] ?? '');
                $prefill['postal_code'] = (string) ($customer['postal_code'] ?? '');
            }
        }

        return $this->view('Marketplace::checkout', [
            'title'    => 'ادامهٔ فرآیند خرید',
            'details'  => $details,
            'prefill'  => $prefill,
            'shipping' => CheckoutService::shippingFor($details['subtotal']),
            'freeShipOver' => CheckoutService::FREE_SHIPPING_OVER,
        ]);
    }

    public function place(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'receiver'    => 'required|min:3|max:120',
            'phone'       => 'required|phone',
            'province'    => 'max:80',
            'city'        => 'required|max:80',
            'address'     => 'required|min:10|max:255',
            'postal_code' => 'max:12',
            'note'        => 'max:1000',
            'coupon_code' => 'max:40',
        ], [
            'receiver' => 'نام گیرنده',
            'phone'    => 'شمارهٔ تماس',
            'city'     => 'شهر',
            'address'  => 'نشانی',
        ]);

        $isAgency = Auth::check() && Auth::role() === 'agency';
        $agencyId = $isAgency ? (int) (Auth::profile()['id'] ?? 0) : null;

        $result = CheckoutService::place(
            $data,
            Auth::id(),
            $agencyId !== 0 ? $agencyId : null,
            $isAgency ? 'agency' : 'web',
            (string) ($data['coupon_code'] ?? '')
        );

        if (!$result['ok']) {
            Session::flash('error', (string) $result['error']);
            Session::flashInput($request->all());
            return $this->redirect(url('checkout'));
        }

        Session::flash('success', 'سفارش شما با کد «' . $result['code'] . '» ثبت شد. این کد را برای پیگیری نگه دارید.');
        return $this->redirect(url('orders/' . $result['code']));
    }
}
