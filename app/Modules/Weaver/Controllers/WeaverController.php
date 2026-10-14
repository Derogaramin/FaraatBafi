<?php
/**
 * کنترلر پنل بافنده — Mobile-first
 *
 * دکمه‌های بزرگ انگشتی برای تغییر وضعیت بافت:
 *   دریافت مواد ← شروع بافت ← تحویل به انبار
 */

declare(strict_types=1);

namespace App\Modules\Weaver\Controllers;

use App\Modules\Members\Models\Weaver;
use App\Modules\Production\Models\WorkOrder;
use App\Modules\Production\Services\ProductionService;
use App\Modules\Catalog\Models\ProductVariant;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Jalali;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Statuses;

final class WeaverController extends Controller
{
    /** پروفایل بافندهٔ کاربر جاری */
    private function weaver(): Weaver
    {
        $row = Database::selectOne('SELECT id FROM weavers WHERE user_id = ?', [Auth::id()]);
        $weaver = $row ? Weaver::find((int) $row['id']) : null;
        if ($weaver === null) {
            throw new \Core\HttpException(403, 'حساب کاربری شما به پروفایل بافنده متصل نیست. با دفتر تعاونی تماس بگیرید.');
        }
        return $weaver;
    }

    public function dashboard(Request $request): Response
    {
        $weaver = $this->weaver();
        $id = (int) $weaver->id();

        $active = Database::select(
            'SELECT wo.*, pv.sku, pv.image, pm.name_fa AS product_name, pm.size_cm, c.name_fa AS color_name
             FROM work_orders wo
             JOIN product_variants pv ON pv.id = wo.variant_id
             JOIN product_models pm ON pm.id = pv.model_id
             JOIN colors c ON c.id = pv.color_id
             WHERE wo.weaver_id = ? AND wo.status IN ("assigned","materials_received","weaving","delivered")
             ORDER BY FIELD(wo.status,"weaving","materials_received","assigned","delivered"), wo.due_date ASC',
            [$id]
        );

        $stats = Database::selectOne(
            'SELECT
                (SELECT COUNT(*) FROM work_orders WHERE weaver_id = ? AND status = "completed") AS done_total,
                (SELECT COUNT(*) FROM work_orders WHERE weaver_id = ? AND YEARWEEK(delivered_at) = YEARWEEK(NOW())) AS delivered_this_week,
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE weaver_id = ? AND status = "pending") AS pending_wage,
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE weaver_id = ? AND status = "paid" AND paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS paid_30d',
            [$id, $id, $id, $id]
        ) ?: [];

        return $this->view('Weaver::dashboard', [
            'title'   => 'داشبورد بافنده',
            'layout'  => 'weaver',
            'weaver'  => $weaver->attributes(),
            'active'  => $active,
            'stats'   => $stats,
            'today'   => Jalali::format('l، j F Y'),
        ]);
    }

    public function orders(Request $request): Response
    {
        $weaver = $this->weaver();
        $status = $request->str('status');
        $where = ['wo.weaver_id = ?'];
        $bindings = [(int) $weaver->id()];
        if ($status !== '' && $status !== 'all') {
            $where[] = 'wo.status = ?';
            $bindings[] = $status;
        }
        $whereSql = implode(' AND ', $where);

        $orders = Database::select(
            "SELECT wo.*, pv.sku, pv.image, pm.name_fa AS product_name
             FROM work_orders wo
             JOIN product_variants pv ON pv.id = wo.variant_id
             JOIN product_models pm ON pm.id = pv.model_id
             WHERE {$whereSql}
             ORDER BY wo.id DESC LIMIT 100",
            $bindings
        );

        return $this->view('Weaver::orders', [
            'title'    => 'سفارش‌های بافت من',
            'layout'   => 'weaver',
            'orders'   => $orders,
            'statuses' => Statuses::map('work_order'),
            'filter'   => $status,
            'weaver'   => $weaver->attributes(),
        ]);
    }

    public function orderShow(Request $request): Response
    {
        $weaver = $this->weaver();
        $wo = WorkOrder::find($request->int('id'));
        if ($wo === null || (int) $wo->weaver_id !== (int) $weaver->id()) {
            throw new \Core\HttpException(404, 'سفارش یافت نشد.');
        }

        $variant = \App\Modules\Catalog\Models\ProductVariant::detailed((int) $wo->variant_id);
        $materials = Database::select(
            'SELECT md.*, m.name_fa AS material_name, m.unit, m.code AS material_code
             FROM material_distributions md JOIN materials m ON m.id = md.material_id
             WHERE md.work_order_id = ? ORDER BY md.id DESC',
            [$wo->id()]
        );
        $inspections = Database::select(
            'SELECT qi.*, dt.name_fa AS defect_name FROM quality_inspections qi
             LEFT JOIN defect_types dt ON dt.id = qi.defect_type_id
             WHERE qi.work_order_id = ? ORDER BY qi.id DESC',
            [$wo->id()]
        );
        $payments = Database::select('SELECT * FROM payments WHERE work_order_id = ?', [$wo->id()]);

        return $this->view('Weaver::order-detail', [
            'title'       => 'سفارش ' . $wo->code,
            'layout'      => 'weaver',
            'wo'          => $wo->attributes(),
            'variant'     => $variant,
            'materials'   => $materials,
            'inspections' => $inspections,
            'payments'    => $payments,
            'weaver'      => $weaver->attributes(),
            'nextActions' => $this->weaverActions($wo),
        ]);
    }

    /** تغییر وضعیت توسط بافنده (فقط گذارهای مجاز پنل بافنده) */
    public function changeStatus(Request $request): Response
    {
        $weaver = $this->weaver();
        $wo = WorkOrder::find($request->int('id'));
        if ($wo === null || (int) $wo->weaver_id !== (int) $weaver->id()) {
            throw new \Core\HttpException(404);
        }
        $action = $request->str('action');
        $allowed = $this->weaverActions($wo);
        $newStatus = $allowed[$action] ?? null;
        if ($newStatus === null) {
            Session::flash('error', 'این عمل در وضعیت فعلی سفارش مجاز نیست.');
            return $this->redirect(url('weaver/orders/' . $wo->id()));
        }

        // پیش‌شرط دریافت مواد: حداقل یک توزیع مواد ثبت شده باشد
        if ($newStatus === 'materials_received') {
            $hasMaterials = Database::selectOne('SELECT COUNT(*) AS c FROM material_distributions WHERE work_order_id = ?', [$wo->id()]);
            if ((int) ($hasMaterials['c'] ?? 0) === 0) {
                Session::flash('warning', 'هنوز موادی برای این سفارش ارسال نشده است. پس از تحویل مواد توسط انبار، این دکمه فعال می‌شود.');
                return $this->redirect(url('weaver/orders/' . $wo->id()));
            }
        }

        try {
            ProductionService::changeStatus($wo, $newStatus, 'weaver');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect(url('weaver/orders/' . $wo->id()));
        }

        $messages = [
            'materials_received' => 'دریافت مواد ثبت شد. خدا قوت! 🧶',
            'weaving'            => 'شروع بافت ثبت شد. منتظر شاهکار شما هستیم!',
            'delivered'          => 'تحویل به انبار ثبت شد. نتیجهٔ بازرسی کیفیت به‌زودی اعلام می‌شود.',
        ];
        Session::flash('success', $messages[$newStatus] ?? 'وضعیت به‌روزرسانی شد.');
        return $this->redirect(url('weaver/orders/' . $wo->id()));
    }

    public function materials(Request $request): Response
    {
        $weaver = $this->weaver();
        $materials = Database::select(
            'SELECT md.*, m.name_fa AS material_name, m.unit, m.code AS material_code, wo.code AS wo_code
             FROM material_distributions md
             JOIN materials m ON m.id = md.material_id
             JOIN work_orders wo ON wo.id = md.work_order_id
             WHERE wo.weaver_id = ? ORDER BY md.id DESC LIMIT 100',
            [$weaver->id()]
        );
        $totals = Database::select(
            'SELECT m.name_fa, m.unit, SUM(md.quantity) AS total
             FROM material_distributions md
             JOIN materials m ON m.id = md.material_id
             JOIN work_orders wo ON wo.id = md.work_order_id
             WHERE wo.weaver_id = ? GROUP BY m.id ORDER BY total DESC',
            [$weaver->id()]
        );
        return $this->view('Weaver::materials', [
            'title'     => 'مواد اولیهٔ دریافتی',
            'layout'    => 'weaver',
            'materials' => $materials,
            'totals'    => $totals,
            'weaver'    => $weaver->attributes(),
        ]);
    }

    public function payments(Request $request): Response
    {
        $weaver = $this->weaver();
        $payments = Database::select(
            'SELECT p.*, wo.code AS wo_code FROM payments p
             LEFT JOIN work_orders wo ON wo.id = p.work_order_id
             WHERE p.weaver_id = ? ORDER BY p.id DESC',
            [$weaver->id()]
        );
        return $this->view('Weaver::payments', [
            'title'    => 'دستمزدهای من',
            'layout'   => 'weaver',
            'payments' => $payments,
            'weaver'   => $weaver->attributes(),
            'summary'  => Database::selectOne(
                'SELECT
                    COALESCE(SUM(CASE WHEN status="paid" THEN amount END),0) AS paid_total,
                    COALESCE(SUM(CASE WHEN status="pending" THEN amount END),0) AS pending_total
                 FROM payments WHERE weaver_id = ?',
                [$weaver->id()]
            ) ?: [],
        ]);
    }

    public function profile(Request $request): Response
    {
        $weaver = $this->weaver();
        $stats = Database::selectOne(
            'SELECT
                (SELECT COUNT(*) FROM work_orders WHERE weaver_id = ?) AS total_wos,
                (SELECT COUNT(*) FROM work_orders WHERE weaver_id = ? AND status="completed") AS done_wos,
                (SELECT COUNT(*) FROM authenticity_codes WHERE weaver_id = ?) AS authenticity_count,
                (SELECT ROUND(AVG(qi.score),1) FROM quality_inspections qi JOIN work_orders wo ON wo.id=qi.work_order_id WHERE wo.weaver_id=? AND qi.score IS NOT NULL) AS avg_score',
            [$weaver->id(), $weaver->id(), $weaver->id(), $weaver->id()]
        ) ?: [];
        return $this->view('Weaver::profile', [
            'title'   => 'پروفایل من',
            'layout'  => 'weaver',
            'weaver'  => $weaver->attributes(),
            'user'    => Auth::user(),
            'stats'   => $stats,
        ]);
    }

    /**
     * عمل‌های مجاز بافنده در هر وضعیت.
     *
     * @return array<string, string> اکشن → وضعیت مقصد
     */
    private function weaverActions(WorkOrder $wo): array
    {
        return match ($wo->status) {
            'assigned'           => ['receive_materials' => 'materials_received'],
            'materials_received' => ['start_weaving' => 'weaving'],
            'weaving'            => ['deliver' => 'delivered'],
            default              => [],
        };
    }
}
