<?php
/**
 * کنترلر جست‌وجوی جهانی (نقشهٔ راه ۱٫۵٫۴)
 *
 * - اگر عبارت یک کد FUCS معتبر باشد → پرش مستقیم به صفحهٔ همان موجودیت
 * - در غیر این صورت جست‌وجوی LIKE در محصولات/نوشته‌ها (+سفارش‌ها/بافندگان برای مدیر)
 */

declare(strict_types=1);

namespace App\Modules\Home\Controllers;

use Core\Auth;
use Core\CodeGenerator;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;

final class SearchController extends Controller
{
    public function search(Request $request): Response
    {
        $q = trim($request->str('q'));
        if ($q === '' || mb_strlen($q) < 2) {
            return $this->view('Home::search', ['title' => 'جست‌وجو', 'q' => $q, 'results' => [], 'jump' => null]);
        }

        // ۱) تشخیص کد FUCS → پرش مستقیم
        $jump = $this->resolveCode($q);
        if ($jump !== null) {
            return $this->redirect($jump);
        }

        // ۲) جست‌وجوی متنی
        $like = '%' . $q . '%';
        $results = [];

        $results['products'] = Database::select(
            'SELECT pv.sku, pv.price, pv.stock, pv.image, pm.name_fa, c.name_fa AS color_name, c.hex
             FROM product_variants pv
             JOIN product_models pm ON pm.id = pv.model_id
             JOIN colors c ON c.id = pv.color_id
             WHERE pv.is_active = 1 AND (pm.name_fa LIKE ? OR pv.sku LIKE ?)
             ORDER BY pv.is_featured DESC, pv.id DESC LIMIT 8',
            [$like, $like]
        );

        $results['posts'] = Database::select(
            "SELECT title, slug, excerpt FROM posts
             WHERE status = 'published' AND (title LIKE ? OR excerpt LIKE ? OR tags LIKE ?)
             ORDER BY published_at DESC LIMIT 5",
            [$like, $like, $like]
        );

        if (Auth::isAdmin()) {
            $results['orders'] = Database::select(
                'SELECT id, code, status, total, placed_at, receiver FROM orders
                 WHERE code LIKE ? OR receiver LIKE ? OR phone LIKE ? ORDER BY id DESC LIMIT 8',
                [$like, $like, '%' . en_digits($q) . '%']
            );
            $results['weavers'] = Database::select(
                'SELECT id, code, name, city, status FROM weavers
                 WHERE name LIKE ? OR code LIKE ? OR phone LIKE ? ORDER BY code LIMIT 8',
                [$like, $like, '%' . en_digits($q) . '%']
            );
            $results['agencies'] = Database::select(
                'SELECT id, code, name, city FROM agencies WHERE name LIKE ? OR code LIKE ? ORDER BY code LIMIT 5',
                [$like, $like]
            );
            $results['work_orders'] = Database::select(
                'SELECT id, code, status, quantity FROM work_orders WHERE code LIKE ? ORDER BY id DESC LIMIT 5',
                [$like]
            );
        }

        return $this->view('Home::search', [
            'title'   => 'جست‌وجو: ' . $q,
            'q'       => $q,
            'results' => $results,
            'jump'    => null,
        ]);
    }

    /** اگر عبارت کد FUCS باشد، آدرس مقصد را برمی‌گرداند */
    private function resolveCode(string $q): ?string
    {
        $q = strtoupper(en_digits($q));
        $type = CodeGenerator::detectType($q);
        if ($type === null) {
            return null;
        }
        return match ($type) {
            'authenticity' => url('authenticity/' . rawurlencode($q)),
            'order'        => url('orders/' . rawurlencode($q)),
            'sku'          => url('marketplace/product/' . rawurlencode($q)),
            'work_order'   => Auth::can('production.view') ? $this->adminUrlByCode('work_orders', $q, 'admin/production') : null,
            'weaver'       => Auth::can('weavers.view') ? $this->adminUrlByCode('weavers', $q, 'admin/weavers') : null,
            'agency'       => Auth::can('agencies.view') ? $this->adminUrlByCode('agencies', $q, 'admin/agencies') : null,
            'customer'     => Auth::can('customers.view') ? $this->adminUrlByCode('customers', $q, 'admin/customers') : null,
            'ticket'       => $this->ticketUrl($q),
            'invoice', 'receipt', 'payment', 'commission', 'quality' => null, // کد مرجع — صفحهٔ مستقل ندارد
            default        => null,
        };
    }

    private function adminUrlByCode(string $table, string $code, string $base): ?string
    {
        $row = Database::selectOne("SELECT id FROM `{$table}` WHERE code = ?", [$code]);
        return $row ? url($base . '/' . $row['id']) : null;
    }

    private function ticketUrl(string $code): ?string
    {
        $row = Database::selectOne('SELECT id, user_id FROM tickets WHERE code = ?', [$code]);
        if ($row === null) {
            return null;
        }
        if (Auth::can('tickets.view') && (int) $row['user_id'] !== Auth::id()) {
            return url('admin/tickets/' . $row['id']);
        }
        return (int) $row['user_id'] === Auth::id() ? url('my/tickets/' . $row['id']) : null;
    }
}
