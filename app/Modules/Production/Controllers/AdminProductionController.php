<?php
/**
 * کنترلر مدیریت تولید — سفارش‌های بافت (WO)، تخصیص، مواد، بازرسی کیفیت
 */

declare(strict_types=1);

namespace App\Modules\Production\Controllers;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Members\Models\Weaver;
use App\Modules\Production\Models\DefectType;
use App\Modules\Production\Models\Material;
use App\Modules\Production\Models\WorkOrder;
use App\Modules\Production\Services\ProductionService;
use Core\Controller;
use Core\Database;
use Core\Jalali;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Statuses;

final class AdminProductionController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->str('status');
        $q      = $request->str('q');
        $where  = ['1=1'];
        $bindings = [];
        if ($status !== '') {
            $where[] = 'wo.status = ?';
            $bindings[] = $status;
        }
        if ($q !== '') {
            $where[] = '(wo.code LIKE ? OR w.name LIKE ? OR pv.sku LIKE ?)';
            $like = '%' . $q . '%';
            array_push($bindings, $like, $like, $like);
        }
        $whereSql = implode(' AND ', $where);

        $perPage = 15;
        $page = max(1, $request->int('page', 1));
        $total = (int) (Database::selectOne(
            "SELECT COUNT(*) AS c FROM work_orders wo
             LEFT JOIN weavers w ON w.id = wo.weaver_id
             JOIN product_variants pv ON pv.id = wo.variant_id
             WHERE {$whereSql}",
            $bindings
        )['c'] ?? 0);

        $orders = Database::select(
            "SELECT wo.*, w.name AS weaver_name, w.code AS weaver_code, pv.sku, pm.name_fa AS product_name
             FROM work_orders wo
             LEFT JOIN weavers w ON w.id = wo.weaver_id
             JOIN product_variants pv ON pv.id = wo.variant_id
             JOIN product_models pm ON pm.id = pv.model_id
             WHERE {$whereSql}
             ORDER BY wo.id DESC
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $bindings
        );

        // آمار وضعیت‌ها برای کارت‌های بالای صفحه
        $byStatus = Database::select('SELECT status, COUNT(*) AS c FROM work_orders GROUP BY status');
        $statusCounts = array_column($byStatus, 'c', 'status');

        return $this->view('Production::admin.index', [
            'title'        => 'سفارش‌های تولید',
            'orders'       => $orders,
            'statuses'     => Statuses::map('work_order'),
            'statusCounts' => $statusCounts,
            'filters'      => compact('status', 'q'),
            'paginator'    => new \Core\Paginator($orders, $total, $perPage, $page),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('Production::admin.create', [
            'title'    => 'سفارش تولید جدید',
            'variants' => Database::select(
                'SELECT pv.id, pv.sku, pm.name_fa, c.name_fa AS color_name
                 FROM product_variants pv
                 JOIN product_models pm ON pm.id = pv.model_id
                 JOIN colors c ON c.id = pv.color_id
                 WHERE pv.is_active = 1 ORDER BY pv.sku'
            ),
            'weavers'  => Weaver::builder()->where('status', 'active')->orderBy('code')->get(),
            'priorities' => Statuses::PRIORITY,
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'variant_id' => 'required|integer|exists:product_variants,id',
            'quantity'   => 'required|integer|min:1|max:500',
            'weaver_id'  => 'integer|exists:weavers,id',
            'unit_wage'  => 'numeric|min:0',
            'priority'   => 'in:low,normal,high,urgent',
            'due_date'   => 'jalali_date',
            'note'       => 'max:1000',
        ], [
            'variant_id' => 'محصول',
            'quantity'   => 'تعداد',
            'weaver_id'  => 'بافنده',
            'unit_wage'  => 'دستمزد واحد',
            'due_date'   => 'موعد تحویل',
        ]);

        // تبدیل تاریخ شمسی به میلادی برای ذخیره
        $dueSql = null;
        if (!empty($data['due_date'])) {
            [$jy, $jm, $jd] = array_map('intval', preg_split('#[/\-.]#', en_digits((string) $data['due_date'])));
            $dueSql = Jalali::jalaliDateToSql($jy, $jm, $jd);
        }

        $wo = ProductionService::create([
            'variant_id' => (int) $data['variant_id'],
            'quantity'   => (int) $data['quantity'],
            'weaver_id'  => ($data['weaver_id'] ?? '') !== '' ? (int) $data['weaver_id'] : null,
            'unit_wage'  => (int) en_digits((string) ($data['unit_wage'] ?? 0)),
            'priority'   => $data['priority'] ?? 'normal',
            'due_date'   => $dueSql,
            'note'       => $data['note'] ?? null,
        ]);

        Session::flash('success', 'سفارش تولید «' . $wo->code . '» ثبت شد. (id=' . $wo->id() . ')');
        return $this->redirect(url('admin/production/' . $wo->id()));
    }

    public function show(Request $request): Response
    {
        $wo = WorkOrder::find($request->int('id'));
        if ($wo === null) {
            throw new \Core\HttpException(404, 'سفارش تولید یافت نشد.');
        }
        $id = (int) $wo->id();

        $variant = ProductVariant::detailed((int) $wo->variant_id);
        $weaver = $wo->weaver_id ? Weaver::find((int) $wo->weaver_id) : null;

        return $this->view('Production::admin.show', [
            'title'         => 'سفارش تولید ' . $wo->code,
            'wo'            => $wo->attributes(),
            'variant'       => $variant,
            'weaver'        => $weaver?->attributes(),
            'distributions' => Database::select(
                'SELECT md.*, m.name_fa AS material_name, m.unit, m.code AS material_code, u.name AS by_name
                 FROM material_distributions md
                 JOIN materials m ON m.id = md.material_id
                 LEFT JOIN users u ON u.id = md.distributed_by
                 WHERE md.work_order_id = ? ORDER BY md.id DESC',
                [$id]
            ),
            'materials'     => Material::builder()->where('is_active', 1)->orderBy('name_fa')->get(),
            'inspections'   => Database::select(
                'SELECT qi.*, dt.name_fa AS defect_name, u.name AS inspector_name
                 FROM quality_inspections qi
                 LEFT JOIN defect_types dt ON dt.id = qi.defect_type_id
                 LEFT JOIN users u ON u.id = qi.inspector_id
                 WHERE qi.work_order_id = ? ORDER BY qi.id DESC',
                [$id]
            ),
            'defects'       => DefectType::builder()->orderBy('code')->get(),
            'authenticity'  => Database::select('SELECT * FROM authenticity_codes WHERE work_order_id = ? ORDER BY id', [$id]),
            'payments'      => Database::select('SELECT * FROM payments WHERE work_order_id = ?', [$id]),
            'weavers'       => Weaver::builder()->where('status', 'active')->orderBy('code')->get(),
            'order'         => $wo->order_id ? Database::selectOne('SELECT * FROM orders WHERE id = ?', [$wo->order_id]) : null,
            'statuses'      => Statuses::map('work_order'),
            'transitions'   => $wo->allowedTransitions(),
        ]);
    }

    public function assign(Request $request): Response
    {
        $wo = WorkOrder::find($request->int('id'));
        if ($wo === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'weaver_id' => 'required|integer|exists:weavers,id',
        ], ['weaver_id' => 'بافنده']);

        try {
            ProductionService::assign($wo, (int) $data['weaver_id']);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect(url('admin/production/' . $wo->id()));
        }
        Session::flash('success', 'بافنده به سفارش «' . $wo->code . '» تخصیص یافت و اعلان ارسال شد.');
        return $this->redirect(url('admin/production/' . $wo->id()));
    }

    public function changeStatus(Request $request): Response
    {
        $wo = WorkOrder::find($request->int('id'));
        if ($wo === null) {
            throw new \Core\HttpException(404);
        }
        $newStatus = $request->str('status');
        try {
            ProductionService::changeStatus($wo, $newStatus, 'admin');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect(url('admin/production/' . $wo->id()));
        }
        Session::flash('success', 'وضعیت به «' . Statuses::label('work_order', $newStatus) . '» تغییر کرد.');
        return $this->redirect(url('admin/production/' . $wo->id()));
    }

    /** توزیع مادهٔ اولیه روی سفارش تولید */
    public function distribute(Request $request): Response
    {
        $wo = WorkOrder::find($request->int('id'));
        if ($wo === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'material_id' => 'required|integer|exists:materials,id',
            'quantity'    => 'required|numeric|min:0.001',
            'note'        => 'max:255',
        ], ['material_id' => 'مادهٔ اولیه', 'quantity' => 'مقدار']);

        try {
            ProductionService::distributeMaterial(
                $wo,
                (int) $data['material_id'],
                (float) en_digits((string) $data['quantity']),
                $data['note'] ?? null
            );
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect(url('admin/production/' . $wo->id()));
        }
        Session::flash('success', 'مادهٔ اولیه توزیع و از انبار کسر شد.');
        return $this->redirect(url('admin/production/' . $wo->id()));
    }

    /** ثبت بازرسی کیفیت */
    public function inspect(Request $request): Response
    {
        $wo = WorkOrder::find($request->int('id'));
        if ($wo === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'result'         => 'required|in:passed,rejected,conditional',
            'defect_type_id' => 'integer|exists:defect_types,id',
            'score'          => 'integer|between:0,100',
            'note'           => 'max:2000',
        ], ['result' => 'نتیجهٔ بازرسی', 'score' => 'امتیاز']);

        try {
            $qc = ProductionService::inspect(
                $wo,
                (string) $data['result'],
                ($data['defect_type_id'] ?? '') !== '' ? (int) $data['defect_type_id'] : null,
                ($data['score'] ?? '') !== '' ? (int) $data['score'] : null,
                $data['note'] ?? null
            );
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect(url('admin/production/' . $wo->id()));
        }
        Session::flash('success', 'بازرسی «' . $qc->code . '» ثبت شد — نتیجه: ' . Statuses::label('qc', (string) $data['result']));
        return $this->redirect(url('admin/production/' . $wo->id()));
    }

    /** صدور دستی کدهای اصالت (در صورت نیاز) */
    public function issueCodes(Request $request): Response
    {
        $wo = WorkOrder::find($request->int('id'));
        if ($wo === null) {
            throw new \Core\HttpException(404);
        }
        if (!in_array($wo->status, ['qc_passed', 'completed'], true)) {
            Session::flash('error', 'کد اصالت فقط پس از تأیید کیفیت صادر می‌شود.');
            return $this->redirect(url('admin/production/' . $wo->id()));
        }
        $codes = ProductionService::issueAuthenticityCodes($wo);
        Session::flash(
            $codes === [] ? 'info' : 'success',
            $codes === [] ? 'کدهای اصالت پیش‌تر صادر شده‌اند.' : fa((string) count($codes)) . ' کد اصالت صادر شد.'
        );
        return $this->redirect(url('admin/production/' . $wo->id()));
    }
}
