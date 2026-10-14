<?php
/**
 * کنترلر هوش تجاری — عملکرد بافندگان و فروش
 */

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;

final class BiController extends Controller
{
    /** نمای کلی BI */
    public function index(Request $request): Response
    {
        // پرفروش‌ترین محصولات
        $topProducts = Database::select(
            'SELECT oi.sku, oi.title, SUM(oi.quantity) AS sold, SUM(oi.total) AS revenue
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id AND o.status NOT IN ("cancelled","returned")
             GROUP BY oi.sku, oi.title ORDER BY sold DESC LIMIT 8'
        );

        // بهترین بافندگان (تعداد تحویل + میانگین امتیاز QC)
        $topWeavers = Database::select(
            'SELECT w.code, w.name, w.rating,
                (SELECT COUNT(*) FROM work_orders wo WHERE wo.weaver_id = w.id AND wo.status = "completed") AS done,
                (SELECT ROUND(AVG(qi.score),1) FROM quality_inspections qi JOIN work_orders wo2 ON wo2.id = qi.work_order_id WHERE wo2.weaver_id = w.id AND qi.score IS NOT NULL) AS avg_score
             FROM weavers w WHERE w.status = "active"
             ORDER BY done DESC, avg_score DESC LIMIT 8'
        );

        // نرخ رد کیفیت بر اساس نوع عیب
        $defects = Database::select(
            'SELECT dt.name_fa, COUNT(qi.id) AS c FROM quality_inspections qi
             JOIN defect_types dt ON dt.id = qi.defect_type_id
             WHERE qi.result IN ("rejected","conditional")
             GROUP BY dt.id ORDER BY c DESC LIMIT 6'
        );

        // فروش به تفکیک کانال
        $channels = Database::select(
            'SELECT channel, COUNT(*) AS cnt, COALESCE(SUM(total),0) AS revenue
             FROM orders WHERE status NOT IN ("cancelled","returned") GROUP BY channel'
        );

        return $this->view('Admin::bi', [
            'title'       => 'هوش تجاری',
            'topProducts' => $topProducts,
            'topWeavers'  => $topWeavers,
            'defects'     => $defects,
            'channels'    => $channels,
        ]);
    }

    /** جدول عملکرد بافندگان */
    public function weavers(Request $request): Response
    {
        $rows = Database::select(
            'SELECT w.id, w.code, w.name, w.specialty, w.skill_level, w.rating, w.balance,
                (SELECT COUNT(*) FROM work_orders wo WHERE wo.weaver_id = w.id) AS total_wos,
                (SELECT COUNT(*) FROM work_orders wo WHERE wo.weaver_id = w.id AND wo.status = "completed") AS completed,
                (SELECT COUNT(*) FROM work_orders wo WHERE wo.weaver_id = w.id AND wo.status IN ("assigned","materials_received","weaving")) AS active,
                (SELECT ROUND(AVG(qi.score),1) FROM quality_inspections qi JOIN work_orders wo2 ON wo2.id = qi.work_order_id WHERE wo2.weaver_id = w.id AND qi.score IS NOT NULL) AS avg_score,
                (SELECT COALESCE(SUM(amount),0) FROM payments p WHERE p.weaver_id = w.id AND p.status = "paid") AS total_paid
             FROM weavers w ORDER BY w.code'
        );
        return $this->view('Admin::bi-weavers', [
            'title' => 'عملکرد بافندگان',
            'rows'  => $rows,
        ]);
    }
}
