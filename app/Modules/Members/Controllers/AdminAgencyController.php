<?php
/**
 * کنترلر مدیریت نمایندگی‌ها + کالای امانی
 */

declare(strict_types=1);

namespace App\Modules\Members\Controllers;

use App\Modules\Agency\Services\PosService;
use App\Modules\Members\Models\Agency;
use App\Modules\Members\Services\UserService;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

final class AdminAgencyController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('Members::admin.agencies', [
            'title'    => 'نمایندگی‌های فروش',
            'agencies' => Database::select(
                'SELECT a.*,
                    (SELECT COUNT(*) FROM orders o WHERE o.agency_id = a.id) AS orders_count,
                    (SELECT COALESCE(SUM(amount),0) FROM commissions cm WHERE cm.agency_id = a.id AND cm.status = "pending") AS pending_commission
                 FROM agencies a ORDER BY a.code'
            ),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('Members::admin.agency-form', ['title' => 'نمایندگی جدید', 'agency' => null]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name'            => 'required|min:3|max:160',
            'manager_name'    => 'max:120',
            'province'        => 'max:80',
            'city'            => 'required|max:80',
            'address'         => 'max:255',
            'phone'           => 'required|phone|unique:users,phone',
            'commission_rate' => 'required|numeric|between:0,50',
            'credit_limit'    => 'numeric|min:0',
            'password'        => 'min:8',
        ], [
            'name'            => 'نام نمایندگی',
            'phone'           => 'شمارهٔ تماس',
            'commission_rate' => 'نرخ پورسانت',
        ]);
        $agency = UserService::createAgency($data);
        Session::flash('success', 'نمایندگی «' . $agency->name . '» با کد «' . $agency->code . '» ثبت شد. (id=' . $agency->id() . ')');
        return $this->redirect(url('admin/agencies/' . $agency->id()));
    }

    public function show(Request $request): Response
    {
        $agency = Agency::find($request->int('id'));
        if ($agency === null) {
            throw new \Core\HttpException(404);
        }
        $id = (int) $agency->id();
        return $this->view('Members::admin.agency-profile', [
            'title'        => 'نمایندگی — ' . $agency->name,
            'agency'       => $agency->attributes(),
            'orders'       => Database::select('SELECT * FROM orders WHERE agency_id = ? ORDER BY id DESC LIMIT 20', [$id]),
            'commissions'  => Database::select('SELECT * FROM commissions WHERE agency_id = ? ORDER BY id DESC LIMIT 20', [$id]),
            'consignment'  => PosService::consignmentOf($agency),
            'variants'     => Database::select(
                'SELECT pv.id, pv.sku, pv.stock, pm.name_fa FROM product_variants pv
                 JOIN product_models pm ON pm.id = pv.model_id WHERE pv.is_active = 1 ORDER BY pv.sku'
            ),
            'salesStats'   => Database::selectOne(
                'SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS revenue FROM orders WHERE agency_id = ?',
                [$id]
            ),
        ]);
    }

    public function edit(Request $request): Response
    {
        $agency = Agency::find($request->int('id'));
        if ($agency === null) {
            throw new \Core\HttpException(404);
        }
        return $this->view('Members::admin.agency-form', [
            'title'  => 'ویرایش نمایندگی ' . $agency->code,
            'agency' => $agency->attributes(),
        ]);
    }

    public function update(Request $request): Response
    {
        $agency = Agency::find($request->int('id'));
        if ($agency === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'name'            => 'required|min:3|max:160',
            'manager_name'    => 'max:120',
            'province'        => 'max:80',
            'city'            => 'required|max:80',
            'address'         => 'max:255',
            'phone'           => 'phone',
            'commission_rate' => 'required|numeric|between:0,50',
            'credit_limit'    => 'numeric|min:0',
        ], ['name' => 'نام نمایندگی', 'commission_rate' => 'نرخ پورسانت']);

        $before = $agency->attributes();
        $agency->update([
            'name'            => $data['name'],
            'manager_name'    => $data['manager_name'] ?? null,
            'province'        => $data['province'] ?? null,
            'city'            => $data['city'],
            'address'         => $data['address'] ?? null,
            'phone'           => ($data['phone'] ?? '') !== '' ? normalize_phone((string) $data['phone']) : null,
            'commission_rate' => (float) en_digits((string) $data['commission_rate']),
            'credit_limit'    => (int) en_digits((string) ($data['credit_limit'] ?? 0)),
        ]);
        \Core\Audit::diff('agency', (int) $agency->id(), (string) $agency->code, $before, $agency->attributes(), ['commission_rate', 'credit_limit', 'status']);
        Session::flash('success', 'اطلاعات نمایندگی به‌روزرسانی شد.');
        return $this->redirect(url('admin/agencies/' . $agency->id()));
    }

    /** ارسال کالای امانی به نمایندگی */
    public function consign(Request $request): Response
    {
        $agency = Agency::find($request->int('id'));
        if ($agency === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'variant_id' => 'required|integer|exists:product_variants,id',
            'quantity'   => 'required|integer|min:1|max:500',
        ], ['variant_id' => 'محصول', 'quantity' => 'تعداد']);

        try {
            PosService::consign($agency, (int) $data['variant_id'], (int) $data['quantity']);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect(url('admin/agencies/' . $agency->id()));
        }
        Session::flash('success', fa((string) $data['quantity']) . ' قلم کالای امانی به نمایندگی ارسال و از انبار مرکزی کسر شد.');
        return $this->redirect(url('admin/agencies/' . $agency->id()));
    }
}
