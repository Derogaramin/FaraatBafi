<?php
/**
 * کنترلر ممیزی تغییرات — فهرست field_audits (نقد §۶٫۱)
 */

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;

final class AuditsController extends Controller
{
    public function index(Request $request): Response
    {
        $entity = $request->str('entity');
        $where = '1=1';
        $bindings = [];
        if ($entity !== '') {
            $where = 'fa.entity = ?';
            $bindings[] = $entity;
        }
        $perPage = 25;
        $page = max(1, $request->int('page', 1));
        $total = (int) (Database::selectOne("SELECT COUNT(*) AS c FROM field_audits fa WHERE {$where}", $bindings)['c'] ?? 0);
        $rows = Database::select(
            "SELECT fa.*, u.name AS user_name
             FROM field_audits fa LEFT JOIN users u ON u.id = fa.user_id
             WHERE {$where}
             ORDER BY fa.id DESC
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $bindings
        );
        $entities = Database::select('SELECT DISTINCT entity FROM field_audits ORDER BY entity');

        return $this->view('Admin::audits', [
            'title'     => 'ممیزی تغییرات',
            'rows'      => $rows,
            'entities'  => array_column($entities, 'entity'),
            'entity'    => $entity,
            'paginator' => new \Core\Paginator($rows, $total, $perPage, $page),
            'labels'    => [
                'variant'  => 'واریانت محصول',
                'material' => 'مادهٔ اولیه',
                'weaver'   => 'بافنده',
                'agency'   => 'نمایندگی',
                'order'    => 'سفارش فروش',
            ],
            'fields'    => [
                'price' => 'قیمت', 'cost' => 'بهای تمام‌شده', 'stock' => 'موجودی',
                'wage_rate' => 'نرخ دستمزد', 'commission_rate' => 'نرخ پورسانت',
                'credit_limit' => 'سقف اعتبار', 'status' => 'وضعیت', 'is_active' => 'فعال بودن',
            ],
        ]);
    }
}
