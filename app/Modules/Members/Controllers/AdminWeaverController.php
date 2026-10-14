<?php
/**
 * کنترلر مدیریت بافندگان
 */

declare(strict_types=1);

namespace App\Modules\Members\Controllers;

use App\Modules\Members\Models\Weaver;
use App\Modules\Members\Services\UserService;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Statuses;

final class AdminWeaverController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->str('q');
        $status = $request->str('status');
        $where = ['1=1'];
        $bindings = [];
        if ($q !== '') {
            $where[] = '(w.name LIKE ? OR w.code LIKE ? OR w.phone LIKE ? OR w.city LIKE ?)';
            $like = '%' . en_digits($q) . '%';
            array_push($bindings, $like, $like, $like, $like);
        }
        if ($status !== '') {
            $where[] = 'w.status = ?';
            $bindings[] = $status;
        }
        $whereSql = implode(' AND ', $where);

        $perPage = 15;
        $page = max(1, $request->int('page', 1));
        $total = (int) (Database::selectOne("SELECT COUNT(*) AS c FROM weavers w WHERE {$whereSql}", $bindings)['c'] ?? 0);
        $weavers = Database::select(
            "SELECT w.*,
                    (SELECT COUNT(*) FROM work_orders wo WHERE wo.weaver_id = w.id AND wo.status IN ('assigned','materials_received','weaving')) AS active_wos,
                    (SELECT COUNT(*) FROM work_orders wo WHERE wo.weaver_id = w.id AND wo.status = 'completed') AS done_wos
             FROM weavers w
             WHERE {$whereSql}
             ORDER BY w.code
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $bindings
        );

        return $this->view('Members::admin.weavers', [
            'title'     => 'بافندگان',
            'weavers'   => $weavers,
            'filters'   => compact('q', 'status'),
            'paginator' => new \Core\Paginator($weavers, $total, $perPage, $page),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('Members::admin.weaver-form', [
            'title'  => 'بافندهٔ جدید',
            'weaver' => null,
            'skills' => Statuses::SKILL,
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->weaverRules($request);
        try {
            $weaver = UserService::createWeaver($data, $request->bool('with_login'));
        } catch (\Throwable $e) {
            Session::flash('error', 'خطا در ثبت بافنده: ' . $e->getMessage());
            Session::flashInput($request->all());
            return $this->redirect(url('admin/weavers/create'));
        }
        Session::flash('success', 'بافندهٔ «' . $weaver->name . '» با کد «' . $weaver->code . '» ثبت شد. (id=' . $weaver->id() . ')');
        return $this->redirect(url('admin/weavers/' . $weaver->id()));
    }

    /** پروفایل کامل بافنده: WOها، مواد، دستمزدها، بازرسی‌ها */
    public function show(Request $request): Response
    {
        $weaver = Weaver::find($request->int('id'));
        if ($weaver === null) {
            throw new \Core\HttpException(404, 'بافنده یافت نشد.');
        }
        $id = (int) $weaver->id();

        $stats = Database::selectOne(
            'SELECT
                (SELECT COUNT(*) FROM work_orders WHERE weaver_id = ?) AS total_wos,
                (SELECT COUNT(*) FROM work_orders WHERE weaver_id = ? AND status = "completed") AS done_wos,
                (SELECT COUNT(*) FROM work_orders WHERE weaver_id = ? AND status IN ("assigned","materials_received","weaving")) AS active_wos,
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE weaver_id = ? AND status = "paid") AS paid_total,
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE weaver_id = ? AND status = "pending") AS pending_total,
                (SELECT ROUND(AVG(score),1) FROM quality_inspections qi JOIN work_orders wo ON wo.id = qi.work_order_id WHERE wo.weaver_id = ? AND qi.score IS NOT NULL) AS avg_score',
            [$id, $id, $id, $id, $id, $id]
        ) ?: [];

        return $this->view('Members::admin.weaver-profile', [
            'title'         => 'پروفایل بافنده — ' . $weaver->name,
            'weaver'        => $weaver->attributes(),
            'stats'         => $stats,
            'workOrders'    => Database::select(
                'SELECT wo.*, pv.sku, pm.name_fa AS product_name
                 FROM work_orders wo
                 JOIN product_variants pv ON pv.id = wo.variant_id
                 JOIN product_models pm ON pm.id = pv.model_id
                 WHERE wo.weaver_id = ? ORDER BY wo.id DESC LIMIT 20',
                [$id]
            ),
            'materials'     => Database::select(
                'SELECT md.*, m.name_fa AS material_name, m.unit, m.code AS material_code
                 FROM material_distributions md
                 JOIN materials m ON m.id = md.material_id
                 JOIN work_orders wo ON wo.id = md.work_order_id
                 WHERE wo.weaver_id = ? ORDER BY md.id DESC LIMIT 20',
                [$id]
            ),
            'payments'      => Database::select('SELECT * FROM payments WHERE weaver_id = ? ORDER BY id DESC LIMIT 20', [$id]),
            'inspections'   => Database::select(
                'SELECT qi.*, wo.code AS wo_code FROM quality_inspections qi
                 JOIN work_orders wo ON wo.id = qi.work_order_id
                 WHERE wo.weaver_id = ? ORDER BY qi.id DESC LIMIT 10',
                [$id]
            ),
            'authenticityCount' => (int) (Database::selectOne('SELECT COUNT(*) AS c FROM authenticity_codes WHERE weaver_id = ?', [$id])['c'] ?? 0),
        ]);
    }

    public function edit(Request $request): Response
    {
        $weaver = Weaver::find($request->int('id'));
        if ($weaver === null) {
            throw new \Core\HttpException(404);
        }
        return $this->view('Members::admin.weaver-form', [
            'title'  => 'ویرایش بافندهٔ ' . $weaver->code,
            'weaver' => $weaver->attributes(),
            'skills' => Statuses::SKILL,
        ]);
    }

    public function update(Request $request): Response
    {
        $weaver = Weaver::find($request->int('id'));
        if ($weaver === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'name'        => 'required|min:3|max:120',
            'phone'       => 'phone',
            'national_id' => 'melli',
            'city'        => 'max:80',
            'village'     => 'max:80',
            'address'     => 'max:255',
            'specialty'   => 'max:120',
            'skill_level' => 'in:apprentice,junior,mid,senior,master',
            'loom_count'  => 'integer|between:1,20',
            'wage_rate'   => 'numeric|min:0',
            'note'        => 'max:2000',
        ], ['name' => 'نام', 'phone' => 'موبایل', 'national_id' => 'کد ملی', 'wage_rate' => 'نرخ دستمزد']);

        $before = $weaver->attributes();
        $weaver->update([
            'name'        => $data['name'],
            'phone'       => ($data['phone'] ?? '') !== '' ? normalize_phone((string) $data['phone']) : null,
            'national_id' => ($data['national_id'] ?? '') !== '' ? en_digits((string) $data['national_id']) : null,
            'city'        => $data['city'] ?? null,
            'village'     => $data['village'] ?? null,
            'address'     => $data['address'] ?? null,
            'specialty'   => $data['specialty'] ?? null,
            'skill_level' => $data['skill_level'] ?? 'mid',
            'loom_count'  => (int) ($data['loom_count'] ?? 1),
            'wage_rate'   => (int) en_digits((string) ($data['wage_rate'] ?? 0)),
            'note'        => $data['note'] ?? null,
        ]);

        \Core\Audit::diff('weaver', (int) $weaver->id(), (string) $weaver->code, $before, $weaver->attributes(), ['wage_rate', 'status', 'skill_level', 'specialty']);

        // اگر کاربر پنل دارد، نام/موبایل کاربر را هم همگام کن
        if ($weaver->user_id) {
            Database::update('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$data['name'], $weaver->phone, $weaver->user_id]);
        }

        Session::flash('success', 'اطلاعات بافنده به‌روزرسانی شد.');
        return $this->redirect(url('admin/weavers/' . $weaver->id()));
    }

    public function toggleStatus(Request $request): Response
    {
        $weaver = Weaver::find($request->int('id'));
        if ($weaver === null) {
            throw new \Core\HttpException(404);
        }
        $new = $weaver->status === 'active' ? 'inactive' : 'active';
        $weaver->update(['status' => $new]);
        if ($weaver->user_id) {
            Database::update('UPDATE users SET status = ? WHERE id = ?', [$new === 'active' ? 'active' : 'suspended', $weaver->user_id]);
        }
        Session::flash('info', 'وضعیت بافنده به «' . ($new === 'active' ? 'فعال' : 'غیرفعال') . '» تغییر کرد.');
        return $this->redirect(url('admin/weavers/' . $weaver->id()));
    }

    private function weaverRules(Request $request): array
    {
        $data = $this->validate($request->all(), [
            'name'        => 'required|min:3|max:120',
            'phone'       => 'required|phone|unique:users,phone',
            'national_id' => 'melli',
            'city'        => 'max:80',
            'village'     => 'max:80',
            'address'     => 'max:255',
            'specialty'   => 'max:120',
            'skill_level' => 'in:apprentice,junior,mid,senior,master',
            'loom_count'  => 'integer|between:1,20',
            'wage_rate'   => 'required|numeric|min:0',
            'note'        => 'max:2000',
        ], [
            'name'        => 'نام بافنده',
            'phone'       => 'شمارهٔ موبایل',
            'national_id' => 'کد ملی',
            'wage_rate'   => 'نرخ دستمزد هر واحد',
        ]);
        $data['wage_rate'] = en_digits((string) $data['wage_rate']);
        return $data;
    }
}
