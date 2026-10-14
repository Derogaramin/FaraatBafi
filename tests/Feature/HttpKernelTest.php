<?php
/**
 * تست ویژگی — کرنل HTTP درون‌فرآیندی (بدون سرور)
 * مسیریابی، میان‌افزارها، رندر صفحات و کدهای خطا
 */

declare(strict_types=1);

namespace Tests\Feature;

use Core\App;
use Core\Auth;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use PHPUnit\Framework\TestCase;

final class HttpKernelTest extends TestCase
{
    protected function tearDown(): void
    {
        Auth::logout();
        Auth::resetResolution();
        Session::destroy();
    }

    private function handle(string $method, string $uri, array $post = []): Response
    {
        return App::handle(Request::create($method, $uri, $post));
    }

    private function login(string $code): void
    {
        $user = Database::selectOne('SELECT * FROM users WHERE code = ?', [$code]);
        Auth::login($user);
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/marketplace', '/blog', '/about', '/contact', '/authenticity', '/orders/track', '/login', '/register'] as $uri) {
            $response = $this->handle('GET', $uri);
            $this->assertSame(200, $response->status(), "صفحهٔ {$uri} باید ۲۰۰ باشد");
            $this->assertStringContainsString('<html', $response->body());
        }
    }

    public function test_home_page_has_brand_content(): void
    {
        $body = $this->handle('GET', '/')->body();
        $this->assertStringContainsString('فرت', $body);
        $this->assertStringContainsString('نقشینه بافت', $body);
        $this->assertStringContainsString('data-theme', $body); // تم روز/شب
        $this->assertStringContainsString('fonts.css', $body);      // فونت وزیرمتن (لوکال)
    }

    public function test_marketplace_lists_products_with_sku(): void
    {
        $sku = (string) Database::selectOne('SELECT sku FROM product_variants WHERE is_active = 1 LIMIT 1')['sku'];
        $body = $this->handle('GET', '/marketplace?stock=')->body();
        // حداقل یک SKU در صفحه هست (ممکن است در صفحهٔ ۲ باشد — فیلتر جست‌وجو دقیق‌تر است)
        $body = $this->handle('GET', '/marketplace?q=' . rawurlencode($sku))->body();
        $this->assertStringContainsString($sku, $body);
    }

    public function test_product_page_shows_technical_specs(): void
    {
        $sku = (string) Database::selectOne('SELECT sku FROM product_variants WHERE is_active = 1 LIMIT 1')['sku'];
        $body = $this->handle('GET', '/marketplace/product/' . rawurlencode($sku))->body();
        $this->assertSame(200, $this->handle('GET', '/marketplace/product/' . rawurlencode($sku))->status());
        $this->assertStringContainsString('مشخصات فنی بافت', $body);
        $this->assertStringContainsString($sku, $body);
    }

    public function test_unknown_route_404(): void
    {
        $this->assertSame(404, $this->handle('GET', '/definitely-not-a-page')->status());
    }

    public function test_guest_redirected_from_panels(): void
    {
        foreach (['/admin', '/weaver', '/agency', '/my'] as $uri) {
            $response = $this->handle('GET', $uri);
            $this->assertSame(302, $response->status(), "مهمان در {$uri} باید ریدایرکت شود");
            $this->assertStringContainsString('login', $response->headers()['Location']);
        }
    }

    public function test_rbac_forbidden_for_wrong_role(): void
    {
        $this->login('C000001'); // مشتری
        $this->assertSame(403, $this->handle('GET', '/admin')->status());
        $this->assertSame(403, $this->handle('GET', '/weaver')->status());
        $this->assertSame(403, $this->handle('GET', '/agency')->status());
        $this->assertSame(200, $this->handle('GET', '/my')->status());

        Auth::logout();
        $this->login('W001'); // بافنده
        $this->assertSame(200, $this->handle('GET', '/weaver')->status());
        $this->assertSame(403, $this->handle('GET', '/admin')->status());

        Auth::logout();
        $this->login('S001'); // مدیر اجرایی
        $this->assertSame(200, $this->handle('GET', '/admin')->status());
        $this->assertSame(403, $this->handle('GET', '/admin/governance/votes')->status()); // حاکمیت فقط M
        $this->assertSame(403, $this->handle('GET', '/admin/finance/commissions')->status()); // مالی فقط M
    }

    public function test_admin_dashboard_renders_kpis(): void
    {
        $this->login('M001');
        $response = $this->handle('GET', '/admin');
        $this->assertSame(200, $response->status());
        $body = $response->body();
        $this->assertStringContainsString('بافندگان فعال', $body);
        $this->assertStringContainsString('بازرسی‌های معوق', $body);
        $this->assertStringContainsString('panel-sidebar', $body);
    }

    public function test_weaver_panel_mobile_layout(): void
    {
        $this->login('W001');
        $body = $this->handle('GET', '/weaver')->body();
        $this->assertStringContainsString('bottom-nav', $body);
        $this->assertStringContainsString('weaver-hello', $body);
    }

    public function test_csrf_rejects_post_without_token(): void
    {
        $response = $this->handle('POST', '/cart/add', ['sku' => 'X', 'qty' => '1']);
        $this->assertSame(419, $response->status());
    }

    public function test_csrf_accepts_valid_token(): void
    {
        $token = Session::csrfToken();
        $sku = (string) Database::selectOne('SELECT sku FROM product_variants WHERE is_active=1 AND stock>0 LIMIT 1')['sku'];
        $response = $this->handle('POST', '/cart/add', ['_token' => $token, 'sku' => $sku, 'qty' => '1']);
        $this->assertSame(302, $response->status());
    }

    public function test_authenticity_page_for_seeded_code(): void
    {
        $code = (string) Database::selectOne('SELECT code FROM authenticity_codes LIMIT 1')['code'];
        $response = $this->handle('GET', '/authenticity/' . rawurlencode($code));
        $this->assertSame(200, $response->status());
        $this->assertStringContainsString($code, $response->body());
        $this->assertStringContainsString('<svg', $response->body()); // QR

        $fake = $this->handle('GET', '/authenticity/FB-9999-9999-W999');
        $this->assertSame(404, $fake->status());
    }

    public function test_blog_post_page(): void
    {
        $slug = (string) Database::selectOne("SELECT slug FROM posts WHERE status='published' LIMIT 1")['slug'];
        $response = $this->handle('GET', '/blog/' . rawurlencode($slug));
        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('وبلاگ', $response->body());
    }

    public function test_json_response_for_ajax_cart(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $token = Session::csrfToken();
        $sku = (string) Database::selectOne('SELECT sku FROM product_variants WHERE is_active=1 AND stock>0 LIMIT 1')['sku'];
        $response = $this->handle('POST', '/cart/add', ['_token' => $token, 'sku' => $sku, 'qty' => '1']);
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $this->assertSame(200, $response->status());
        $data = json_decode($response->body(), true);
        $this->assertTrue($data['ok']);
        $this->assertArrayHasKey('cart_count', $data);
    }

    public function test_security_headers_present(): void
    {
        $headers = $this->handle('GET', '/')->headers();
        $this->assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
    }

    public function test_405_for_wrong_method(): void
    {
        $this->assertSame(405, $this->handle('POST', '/marketplace')->status());
    }
}
