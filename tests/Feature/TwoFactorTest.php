<?php
/**
 * تست ویژگی — احراز هویت دو مرحله‌ای (TOTP) و تغییر گذرواژهٔ اجباری
 */

declare(strict_types=1);

namespace Tests\Feature;

use Core\App;
use Core\Auth;
use Core\Database;
use Core\Request;
use Core\Session;
use Core\Totp;
use PHPUnit\Framework\TestCase;

final class TwoFactorTest extends TestCase
{
    private array $user;

    protected function setUp(): void
    {
        Auth::logout();
        Auth::resetResolution();
        Session::destroy();
        // کاربر آزمایشگاهی اختصاصی (M9xx) تا دموهای seed دست‌نخورده بمانند
        $row = Database::selectOne("SELECT * FROM users WHERE code = 'M990'");
        if ($row === null) {
            Database::insert(
                'INSERT INTO users (code, name, phone, password_hash, status, totp_secret, must_change_password, created_at)
                 VALUES ("M990", "کاربر آزمایشگاهی 2FA", "09990001122", ?, "active", NULL, 0, NOW())',
                [Auth::hash('test-pass-123')]
            );
            $row = Database::selectOne("SELECT * FROM users WHERE code = 'M990'");
        }
        // بازنشانی به حالت شناخته‌شده (استقلال از ترتیب اجرا)
        Database::update(
            'UPDATE users SET totp_secret = NULL, must_change_password = 0, password_hash = ?, status = "active" WHERE id = ?',
            [Auth::hash('test-pass-123'), $row['id']]
        );
        $this->user = Database::selectOne('SELECT * FROM users WHERE id = ?', [$row['id']]);
    }

    protected function tearDown(): void
    {
        Auth::logout();
        Auth::resetResolution();
        Session::destroy();
    }

    private function post(string $uri, array $data): \Core\Response
    {
        $data['_token'] = Session::csrfToken();
        return App::handle(Request::create('POST', $uri, $data));
    }

    public function test_login_without_2fa_is_direct(): void
    {
        $response = $this->post('/login', ['identifier' => 'M990', 'password' => 'test-pass-123']);
        $this->assertSame(302, $response->status());
        $this->assertStringNotContainsString('verify-2fa', $response->headers()['Location']);
        $this->assertTrue(Auth::check());
    }

    public function test_login_with_2fa_requires_totp_then_completes(): void
    {
        $secret = Totp::generateSecret();
        Database::update('UPDATE users SET totp_secret = ? WHERE id = ?', [$secret, $this->user['id']]);

        // گام ۱: گذرواژه → ریدایرکت به verify-2fa (هنوز وارد نشده)
        $response = $this->post('/login', ['identifier' => 'M990', 'password' => 'test-pass-123']);
        $this->assertSame(302, $response->status());
        $this->assertStringContainsString('verify-2fa', $response->headers()['Location']);
        $this->assertFalse(Auth::check());
        $this->assertSame((int) $this->user['id'], (int) Session::get('2fa_user_id'));

        // گام ۲: کد غلط → رد
        $response = $this->post('/verify-2fa', ['code' => '000000']);
        $this->assertSame(302, $response->status());
        $this->assertFalse(Auth::check());

        // گام ۳: کد درست → ورود کامل
        $response = $this->post('/verify-2fa', ['code' => Totp::code($secret)]);
        $this->assertSame(302, $response->status());
        $this->assertTrue(Auth::check());
        $this->assertSame('member', Auth::role());
        $this->assertTrue(Auth::twoFactorCleared());
    }

    public function test_sensitive_post_blocked_until_2fa_enrolled(): void
    {
        Database::update('UPDATE users SET totp_secret = NULL WHERE id = ?', [$this->user['id']]);
        $this->post('/login', ['identifier' => 'M990', 'password' => 'test-pass-123']);
        $this->assertTrue(Auth::check());

        // POST حساس (ثبت سند مالی) با میان‌افزار 2fa → کاربر بدون ثبت‌نام باید هدایت شود
        $response = $this->post('/admin/finance/documents', [
            'title' => 'سند تست 2FA', 'kind' => 'income', 'amount' => '1000', 'doc_date' => '1405/07/22',
        ]);
        $this->assertSame(302, $response->status());
        $this->assertStringContainsString('admin/security', $response->headers()['Location']);

        // پس از فعال‌سازی و تأیید در نشست، همان درخواست موفق می‌شود
        $secret = Totp::generateSecret();
        Database::update('UPDATE users SET totp_secret = ? WHERE id = ?', [$secret, $this->user['id']]);
        Session::put('2fa_ok', true);
        Auth::resetResolution();

        $response = $this->post('/admin/finance/documents', [
            'title' => 'سند تست 2FA', 'kind' => 'income', 'amount' => '1000', 'doc_date' => '1405/07/22',
        ]);
        $this->assertSame(302, $response->status());
        $this->assertStringNotContainsString('admin/security', $response->headers()['Location'] ?? '');
        $doc = Database::selectOne('SELECT * FROM accounting_documents WHERE title = "سند تست 2FA"');
        $this->assertNotNull($doc);
        $this->assertMatchesRegularExpression('/^ACC-\d{4}-\d{2}-\d{3}$/', (string) $doc['code']);
    }

    public function test_force_password_change_redirects(): void
    {
        Database::update('UPDATE users SET must_change_password = 1 WHERE id = ?', [$this->user['id']]);
        $this->post('/login', ['identifier' => 'M990', 'password' => 'test-pass-123']);
        $this->assertTrue(Auth::check());

        // هر صفحهٔ دیگر → ریدایرکت به پروفایل برای تغییر گذرواژه
        $response = App::handle(Request::create('GET', '/admin'));
        $this->assertSame(302, $response->status());
        $this->assertStringContainsString('my/profile', $response->headers()['Location']);

        // صفحهٔ پروفایل خودش در دسترس است
        $response = App::handle(Request::create('GET', '/my/profile'));
        $this->assertSame(200, $response->status());

        // تغییر گذرواژه → پرچم برداشته می‌شود
        $response = $this->post('/my/password', [
            'current_password' => 'test-pass-123',
            'password' => 'new-secret-456',
            'password_confirmation' => 'new-secret-456',
        ]);
        $this->assertSame(302, $response->status());
        $flag = Database::selectOne('SELECT must_change_password FROM users WHERE id = ?', [$this->user['id']]);
        $this->assertSame(0, (int) $flag['must_change_password']);

        // دسترسی آزاد شد
        $response = App::handle(Request::create('GET', '/admin'));
        $this->assertSame(200, $response->status());

        // بازگردانی گذرواژه برای اجراهای بعدی تست
        Database::update('UPDATE users SET password_hash = ? WHERE id = ?', [Auth::hash('test-pass-123'), $this->user['id']]);
    }

    public function test_security_page_renders_for_admin(): void
    {
        $this->post('/login', ['identifier' => 'M990', 'password' => 'test-pass-123']);
        $response = App::handle(Request::create('GET', '/admin/security'));
        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('احراز هویت دو مرحله‌ای', $response->body());
        $this->assertStringContainsString('qr-box', $response->body());   // QR ثبت‌نام
        $this->assertStringContainsString('فعال‌سازی', $response->body());
    }
}
