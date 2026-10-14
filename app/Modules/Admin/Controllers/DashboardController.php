<?php
/**
 * کنترلر داشبورد مدیر — کارت‌های KPI، نمودار فروش، وضعیت تولید
 */

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use Core\Controller;
use Core\Database;
use Core\Jalali;
use Core\Request;
use Core\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // ۴ کارت KPI طبق مستندات: سفارش‌های امروز، درآمد ماه، بافندگان فعال، بازرسی‌های معوق
        $todayStart = date('Y-m-d 00:00:00');
        $monthAgo = date('Y-m-d H:i:s', strtotime('-30 days'));

        $kpi = Database::selectOne(
            'SELECT
                (SELECT COUNT(*) FROM orders WHERE placed_at >= ?) AS orders_today,
                (SELECT COALESCE(SUM(total),0) FROM orders WHERE placed_at >= ? AND status NOT IN ("cancelled","returned")) AS revenue_month,
                (SELECT COUNT(*) FROM weavers WHERE status = "active") AS active_weavers,
                (SELECT COUNT(*) FROM work_orders WHERE status = "delivered") AS pending_qc',
            [$todayStart, $monthAgo]
        ) ?: [];

        // درآمد ۶ ماه اخیر برای نمودار (برچسب شمسی)
        $salesRows = Database::select(
            "SELECT DATE_FORMAT(placed_at, '%Y-%m') AS ym, COALESCE(SUM(total),0) AS revenue, COUNT(*) AS cnt
             FROM orders
             WHERE status NOT IN ('cancelled','returned') AND placed_at >= DATE_SUB(CURDATE(), INTERVAL 7 MONTH)
             GROUP BY ym ORDER BY ym"
        );
        $chart = [];
        foreach ($salesRows as $row) {
            [$gy, $gm] = array_map('intval', explode('-', $row['ym']));
            [$jy, $jm] = Jalali::toJalali($gy, $gm, 15);
            $chart[] = [
                'label'   => Jalali::MONTHS[$jm],
                'revenue' => (int) $row['revenue'],
                'count'   => (int) $row['cnt'],
            ];
        }

        // وضعیت سفارش‌های تولید (نوار وضعیت‌ها)
        $woStatus = Database::select(
            "SELECT status, COUNT(*) AS c FROM work_orders WHERE status != 'cancelled' GROUP BY status"
        );

        // هشدارهای انبار (مواد زیر حد سفارش)
        $lowStock = Database::select(
            'SELECT code, name_fa, stock, min_stock, unit FROM materials WHERE is_active = 1 AND stock <= min_stock ORDER BY stock ASC LIMIT 6'
        );

        // آخرین سفارش‌ها
        $recentOrders = Database::select(
            'SELECT o.*, c.name AS customer_name FROM orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             ORDER BY o.id DESC LIMIT 8'
        );

        // بافت‌های در انتظار بازرسی
        $pendingQc = Database::select(
            'SELECT wo.*, w.name AS weaver_name, pv.sku FROM work_orders wo
             LEFT JOIN weavers w ON w.id = wo.weaver_id
             JOIN product_variants pv ON pv.id = wo.variant_id
             WHERE wo.status = "delivered" ORDER BY wo.delivered_at ASC LIMIT 5'
        );

        // محصولات کم‌موجود
        $lowVariants = Database::select(
            'SELECT pv.sku, pv.stock, pm.name_fa FROM product_variants pv
             JOIN product_models pm ON pm.id = pv.model_id
             WHERE pv.is_active = 1 AND pv.stock <= 2 ORDER BY pv.stock ASC LIMIT 5'
        );

        return $this->view('Admin::dashboard', [
            'title'        => 'داشبورد مدیریت',
            'kpi'          => $kpi,
            'chart'        => $chart,
            'woStatus'     => $woStatus,
            'lowStock'     => $lowStock,
            'recentOrders' => $recentOrders,
            'pendingQc'    => $pendingQc,
            'lowVariants'  => $lowVariants,
            'today'        => Jalali::format('l، j F Y'),
        ]);
    }
}
