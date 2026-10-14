<?php
/**
 * کنترلر پنل نمایندگی — داشبورد، فروش حضوری (POS)، کالای امانی، پورسانت
 */

declare(strict_types=1);

namespace App\Modules\Agency\Controllers;

use App\Modules\Agency\Services\PosService;
use App\Modules\Members\Models\Agency;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Jalali;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Statuses;

final class AgencyController extends Controller
{
    private function agency(): Agency
    {
        $row = Database::selectOne('SELECT id FROM agencies WHERE user_id = ?', [Auth::id()]);
        $agency = $row ? Agency::find((int) $row['id']) : null;
        if ($agency === null) {
            throw new \Core\HttpException(403, 'حساب شما به پروفایل نمایندگی متصل نیست.');
        }
        return $agency;
    }

    public function dashboard(Request $request): Response
    {
        $agency = $this->agency();
        $id = (int) $agency->id();

        $stats = Database::selectOne(
            'SELECT
                (SELECT COUNT(*) FROM orders WHERE agency_id = ? AND placed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS orders_30d,
                (SELECT COALESCE(SUM(total),0) FROM orders WHERE agency_id = ? AND placed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status != "cancelled") AS revenue_30d,
                (SELECT COALESCE(SUM(quantity),0) FROM consignments WHERE agency_id = ? AND quantity > 0) AS consignment_items,
                (SELECT COALESCE(SUM(amount),0) FROM commissions WHERE agency_id = ? AND status IN ("pending","approved")) AS pending_commission,
                (SELECT COALESCE(SUM(amount),0) FROM commissions WHERE agency_id = ? AND status = "paid") AS paid_commission',
            [$id, $id, $id, $id, $id]
        ) ?: [];

        // ارزش کالای امانی
        $consignValue = (int) (Database::selectOne(
            'SELECT COALESCE(SUM(cs.quantity * pv.price),0) AS v
             FROM consignments cs JOIN product_variants pv ON pv.id = cs.variant_id
             WHERE cs.agency_id = ? AND cs.quantity > 0',
            [$id]
        )['v'] ?? 0);

        $recentOrders = Database::select(
            'SELECT * FROM orders WHERE agency_id = ? ORDER BY id DESC LIMIT 8',
            [$id]
        );

        return $this->view('Agency::dashboard', [
            'title'        => 'داشبورد نمایندگی',
            'agency'       => $agency->attributes(),
            'stats'        => $stats,
            'consignValue' => $consignValue,
            'recentOrders' => $recentOrders,
            'today'        => Jalali::format('l، j F Y'),
        ]);
    }

    /** صفحهٔ فروش حضوری با اسکنر بارکد/SKU */
    public function pos(Request $request): Response
    {
        $agency = $this->agency();
        $cart = \App\Modules\Marketplace\Services\CartService::details();
        return $this->view('Agency::pos', [
            'title'       => 'فروش حضوری (POS)',
            'agency'      => $agency->attributes(),
            'cart'        => $cart,
            'consignment' => PosService::consignmentOf($agency),
        ]);
    }

    /** افزودن قلم به سبد POS با SKU (اسکن بارکد همان ورود SKU است) */
    public function posAdd(Request $request): Response
    {
        $sku = strtoupper(trim($request->str('sku')));
        $result = \App\Modules\Marketplace\Services\CartService::add($sku, max(1, $request->int('qty', 1)));
        return $this->json([
            'ok'      => $result['ok'],
            'message' => $result['message'] ?? $result['error'] ?? '',
            'cart'    => $result['ok'] ? \App\Modules\Marketplace\Services\CartService::details() : null,
        ], $result['ok'] ? 200 : 422);
    }

    /** ثبت نهایی فروش حضوری */
    public function posSell(Request $request): Response
    {
        $agency = $this->agency();
        $data = $this->validate($request->all(), [
            'receiver' => 'required|min:3|max:120',
            'phone'    => 'required|phone',
            'city'     => 'max:80',
        ], ['receiver' => 'نام خریدار', 'phone' => 'موبایل خریدار']);

        $result = PosService::sell($agency, $data + ['city' => $data['city'] ?? $agency->city, 'address' => 'فروش حضوری — ' . $agency->name]);

        if (!$result['ok']) {
            Session::flash('error', (string) $result['error']);
            return $this->redirect(url('agency/pos'));
        }
        Session::flash('success', 'فروش حضوری ثبت شد — کد سفارش: «' . $result['code'] . '» فاکتور و رسید صادر و پورسانت محاسبه گردید.');
        return $this->redirect(url('agency/pos'));
    }

    public function posClear(Request $request): Response
    {
        \App\Modules\Marketplace\Services\CartService::clear();
        return $this->redirect(url('agency/pos'));
    }

    public function consignment(Request $request): Response
    {
        $agency = $this->agency();
        return $this->view('Agency::consignment', [
            'title'       => 'کالای امانی',
            'agency'      => $agency->attributes(),
            'consignment' => PosService::consignmentOf($agency),
        ]);
    }

    public function commissions(Request $request): Response
    {
        $agency = $this->agency();
        $commissions = Database::select(
            'SELECT cm.*, o.code AS order_code FROM commissions cm
             LEFT JOIN orders o ON o.id = cm.order_id
             WHERE cm.agency_id = ? ORDER BY cm.id DESC',
            [$agency->id()]
        );
        return $this->view('Agency::commissions', [
            'title'       => 'پورسانت‌های من',
            'agency'      => $agency->attributes(),
            'commissions' => $commissions,
            'statuses'    => Statuses::COMMISSION,
            'summary'     => Database::selectOne(
                'SELECT COALESCE(SUM(CASE WHEN status="paid" THEN amount END),0) AS paid,
                        COALESCE(SUM(CASE WHEN status!="paid" AND status!="rejected" THEN amount END),0) AS pending
                 FROM commissions WHERE agency_id = ?',
                [$agency->id()]
            ) ?: [],
        ]);
    }

    public function orders(Request $request): Response
    {
        $agency = $this->agency();
        return $this->view('Agency::orders', [
            'title'    => 'سفارش‌های نمایندگی',
            'agency'   => $agency->attributes(),
            'orders'   => Database::select(
                'SELECT o.*, c.name AS customer_name FROM orders o
                 LEFT JOIN customers c ON c.id = o.customer_id
                 WHERE o.agency_id = ? ORDER BY o.id DESC LIMIT 100',
                [$agency->id()]
            ),
            'statuses' => Statuses::map('order'),
        ]);
    }
}
