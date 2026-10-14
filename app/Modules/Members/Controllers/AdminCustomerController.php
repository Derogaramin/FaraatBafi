<?php
/**
 * کنترلر مدیریت مشتریان
 */

declare(strict_types=1);

namespace App\Modules\Members\Controllers;

use App\Modules\Members\Models\Customer;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;

final class AdminCustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->str('q');
        $where = '1=1';
        $bindings = [];
        if ($q !== '') {
            $where = '(c.name LIKE ? OR c.code LIKE ? OR c.phone LIKE ?)';
            $like = '%' . en_digits($q) . '%';
            array_push($bindings, $like, $like, $like);
        }
        $perPage = 20;
        $page = max(1, $request->int('page', 1));
        $total = (int) (Database::selectOne("SELECT COUNT(*) AS c FROM customers c WHERE {$where}", $bindings)['c'] ?? 0);
        $customers = Database::select(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS orders_count,
                    (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.customer_id = c.id AND o.status NOT IN ('cancelled','returned')) AS total_purchase
             FROM customers c
             WHERE {$where}
             ORDER BY c.id DESC
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $bindings
        );
        return $this->view('Members::admin.customers', [
            'title'     => 'مشتریان',
            'customers' => $customers,
            'q'         => $q,
            'paginator' => new \Core\Paginator($customers, $total, $perPage, $page),
        ]);
    }

    public function show(Request $request): Response
    {
        $customer = Customer::find($request->int('id'));
        if ($customer === null) {
            throw new \Core\HttpException(404);
        }
        return $this->view('Members::admin.customer-profile', [
            'title'    => 'مشتری — ' . $customer->name,
            'customer' => $customer->attributes(),
            'orders'   => Database::select('SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 30', [$customer->id()]),
            'tickets'  => Database::select(
                'SELECT t.* FROM tickets t JOIN users u ON u.id = t.user_id WHERE u.id = ? ORDER BY t.id DESC LIMIT 10',
                [$customer->user_id ?? 0]
            ),
        ]);
    }
}
