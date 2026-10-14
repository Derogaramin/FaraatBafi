<?php
/**
 * کنترلر پنل مشتری — داشبورد، سفارش‌ها، پروفایل
 */

declare(strict_types=1);

namespace App\Modules\Account\Controllers;

use App\Modules\Members\Models\User;
use App\Modules\Members\Services\UserService;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Statuses;

final class AccountController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = Auth::user();
        $orders = Database::select(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5',
            [$user['id']]
        );
        $openTickets = Database::selectOne(
            'SELECT COUNT(*) AS c FROM tickets WHERE user_id = ? AND status != "closed"',
            [$user['id']]
        );

        return $this->view('Account::dashboard', [
            'title'       => 'میز کار من',
            'orders'      => $orders,
            'openTickets' => (int) ($openTickets['c'] ?? 0),
            'statuses'    => Statuses::map('order'),
            'customer'    => Auth::role() === 'customer' ? Auth::profile() : null,
        ]);
    }

    public function orders(Request $request): Response
    {
        $orders = Database::select(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC',
            [Auth::id()]
        );
        return $this->view('Account::orders', [
            'title'    => 'سفارش‌های من',
            'orders'   => $orders,
            'statuses' => Statuses::map('order'),
        ]);
    }

    public function orderShow(Request $request): Response
    {
        $order = Database::selectOne('SELECT * FROM orders WHERE id = ? AND user_id = ?', [$request->int('id'), Auth::id()]);
        if ($order === null) {
            throw new \Core\HttpException(404, 'سفارش یافت نشد.');
        }
        return $this->view('Account::order-detail', [
            'title'    => 'سفارش ' . $order['code'],
            'order'    => $order,
            'items'    => Database::select(
                'SELECT oi.*, pv.image FROM order_items oi
                 LEFT JOIN product_variants pv ON pv.id = oi.variant_id WHERE oi.order_id = ?',
                [$order['id']]
            ),
            'shipment' => Database::selectOne('SELECT * FROM shipments WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$order['id']]),
            'invoice'  => Database::selectOne('SELECT * FROM invoices WHERE order_id = ?', [$order['id']]),
            'statuses' => Statuses::map('order'),
        ]);
    }

    public function profile(Request $request): Response
    {
        return $this->view('Account::profile', [
            'title'    => 'پروفایل من',
            'user'     => Auth::user(),
            'customer' => Auth::role() === 'customer' ? Auth::profile() : null,
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name'     => 'required|min:3|max:120',
            'email'    => 'email|unique:users,email,' . Auth::id(),
            'province' => 'max:80',
            'city'     => 'max:80',
            'address'  => 'max:255',
        ], ['name' => 'نام', 'email' => 'رایانامه']);

        Database::update('UPDATE users SET name = ?, email = ? WHERE id = ?', [
            $data['name'],
            ($data['email'] ?? '') !== '' ? $data['email'] : null,
            Auth::id(),
        ]);
        if (Auth::role() === 'customer') {
            $profile = Auth::profile();
            if ($profile) {
                Database::update(
                    'UPDATE customers SET name = ?, province = ?, city = ?, address = ?, updated_at = NOW() WHERE id = ?',
                    [$data['name'], $data['province'] ?? null, $data['city'] ?? null, $data['address'] ?? null, $profile['id']]
                );
            }
        }
        Auth::resetResolution();
        Session::flash('success', 'پروفایل به‌روزرسانی شد.');
        return $this->redirect(url('my/profile'));
    }

    public function changePassword(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ], ['current_password' => 'گذرواژهٔ فعلی', 'password' => 'گذرواژهٔ جدید']);

        try {
            $user = User::find((int) Auth::id());
            if ($user === null) {
                throw new \RuntimeException('کاربر یافت نشد.');
            }
            UserService::changePassword(
                $user,
                (string) $data['current_password'],
                (string) $data['password']
            );
            // پرچم «تغییر اجباری» برداشته می‌شود (نقد S8)
            Database::update('UPDATE users SET must_change_password = 0 WHERE id = ?', [$user->id()]);
            Auth::resetResolution(); // تازه‌سازی وضعیت کاربر جاری
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect(url('my/profile'));
        }
        Session::flash('success', 'گذرواژه با موفقیت تغییر کرد.');
        return $this->redirect(url('my/profile'));
    }
}
