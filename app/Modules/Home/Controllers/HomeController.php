<?php
/**
 * کنترلر صفحهٔ فرود و صفحات عمومی
 */

declare(strict_types=1);

namespace App\Modules\Home\Controllers;

use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;

final class HomeController extends Controller
{
    /** صفحهٔ اصلی: معرفی + محصولات ویژه + آمار + آخرین نوشته‌ها + جست‌وجوی اصالت */
    public function index(Request $request): Response
    {
        $featured = Database::select(
            'SELECT pv.sku, pv.price, pv.image, pv.stock, pm.name_fa, pm.size_cm, pt.name_fa AS type_name,
                    c.name_fa AS color_name, c.hex, p.name_fa AS pattern_name
             FROM product_variants pv
             JOIN product_models pm ON pm.id = pv.model_id
             JOIN product_types pt ON pt.id = pm.type_id
             JOIN colors c ON c.id = pv.color_id
             JOIN patterns p ON p.id = pv.pattern_id
             WHERE pv.is_active = 1
             ORDER BY pv.is_featured DESC, pv.id DESC
             LIMIT 8'
        );

        $latestPosts = Database::select(
            "SELECT title, slug, excerpt, image, published_at
             FROM posts WHERE status = 'published'
             ORDER BY published_at DESC LIMIT 3"
        );

        $stats = Database::selectOne(
            'SELECT
                (SELECT COUNT(*) FROM weavers WHERE status = "active") AS weavers,
                (SELECT COUNT(*) FROM agencies WHERE status = "active") AS agencies,
                (SELECT COUNT(*) FROM customers) AS customers,
                (SELECT COUNT(*) FROM product_variants WHERE is_active = 1) AS products'
        ) ?: [];

        return $this->view('Home::home', [
            'featured'    => $featured,
            'latestPosts' => $latestPosts,
            'stats'       => $stats,
            'title'       => 'تعاونی صنایع دستی نقشینه بافت — برند فرت',
        ]);
    }

    public function about(Request $request): Response
    {
        return $this->view('Home::about', ['title' => 'دربارهٔ تعاونی نقشینه بافت']);
    }

    public function contact(Request $request): Response
    {
        return $this->view('Home::contact', ['title' => 'تماس با ما']);
    }

    public function contactStore(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name'    => 'required|min:3|max:120',
            'phone'   => 'required|phone',
            'message' => 'required|min:10|max:2000',
        ], ['name' => 'نام', 'phone' => 'شمارهٔ تماس', 'message' => 'پیام']);

        // پیام به‌صورت نامهٔ داخلی برای مدیران ثبت می‌شود
        $admins = Database::select("SELECT id FROM users WHERE code LIKE 'M%' LIMIT 3");
        foreach ($admins as $admin) {
            Database::insert(
                'INSERT INTO internal_letters (code, from_user_id, to_user_id, subject, body, kind, sent_at, created_at)
                 VALUES (?, NULL, ?, ?, ?, "notice", NOW(), NOW())',
                [
                    'IR-WEB-' . strtoupper(bin2hex(random_bytes(3))),
                    $admin['id'],
                    'پیام از فرم تماس وب‌سایت — ' . $data['name'],
                    "تماس: {$data['phone']}\n\n{$data['message']}",
                ]
            );
        }

        Session::flash('success', 'پیام شما ثبت شد. همکاران ما به‌زودی با شما تماس می‌گیرند.');
        return $this->redirect(url('contact'));
    }
}
