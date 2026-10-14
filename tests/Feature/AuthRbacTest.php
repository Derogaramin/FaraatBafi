<?php
/**
 * تست ویژگی — احراز هویت و RBAC
 */

declare(strict_types=1);

namespace Tests\Feature;

use Core\Auth;
use Core\Config;
use Core\Database;
use Core\Session;
use PHPUnit\Framework\TestCase;

final class AuthRbacTest extends TestCase
{
    protected function tearDown(): void
    {
        Auth::logout();
        Auth::resetResolution();
        Session::destroy();
    }

    public function test_login_with_code_and_phone(): void
    {
        $this->assertTrue(Auth::attempt('M001', 'faraat1403'));
        $this->assertSame('member', Auth::role());
        $this->assertTrue(Auth::isSuper());
        Auth::logout();

        $phone = (string) Database::selectOne("SELECT phone FROM users WHERE code = 'W001'")['phone'];
        $this->assertTrue(Auth::attempt($phone, 'faraat1403'));
        $this->assertSame('weaver', Auth::role());
    }

    public function test_wrong_password_fails(): void
    {
        $this->assertFalse(Auth::attempt('M001', 'wrong-password'));
        $this->assertTrue(Auth::guest());
    }

    public function test_unknown_identifier_fails(): void
    {
        $this->assertFalse(Auth::attempt('Z999', 'faraat1403'));
    }

    public function test_role_prefixes_map_correctly(): void
    {
        $cases = ['M001' => 'member', 'S001' => 'staff', 'W001' => 'weaver', 'A01' => 'agency', 'C000001' => 'customer'];
        foreach ($cases as $code => $expectedRole) {
            Auth::logout();
            $this->assertTrue(Auth::attempt($code, 'faraat1403'), "ورود {$code} باید موفق باشد");
            $this->assertSame($expectedRole, Auth::role());
        }
    }

    public function test_rbac_permissions(): void
    {
        // عضو اصلی: دسترسی کامل
        Auth::attempt('M001', 'faraat1403');
        $this->assertTrue(Auth::can('admin.access'));
        $this->assertTrue(Auth::can('finance.view'));
        $this->assertTrue(Auth::can('governance.view'));
        $this->assertTrue(Auth::can('voting.participate'));
        Auth::logout();

        // مدیر اجرایی: بدون مالی و حاکمیت
        Auth::attempt('S001', 'faraat1403');
        $this->assertTrue(Auth::can('admin.access'));
        $this->assertTrue(Auth::can('products.manage'));
        $this->assertFalse(Auth::can('finance.view'));
        $this->assertFalse(Auth::can('governance.view'));
        $this->assertFalse(Auth::can('voting.participate'));
        Auth::logout();

        // بافنده: فقط پنل خود
        Auth::attempt('W001', 'faraat1403');
        $this->assertTrue(Auth::can('weaver.own'));
        $this->assertFalse(Auth::can('admin.access'));
        $this->assertFalse(Auth::can('orders.manage'));
        Auth::logout();

        // مشتری
        Auth::attempt('C000001', 'faraat1403');
        $this->assertTrue(Auth::can('orders.own'));
        $this->assertTrue(Auth::can('tickets.own'));
        $this->assertFalse(Auth::can('admin.access'));
    }

    public function test_guest_has_no_permissions(): void
    {
        $this->assertFalse(Auth::can('admin.access'));
        $this->assertSame('guest', Auth::role());
        $this->assertFalse(Auth::check());
    }

    public function test_unknown_permission_denied(): void
    {
        Auth::attempt('M001', 'faraat1403');
        $this->assertFalse(Auth::can('non.existent.permission'));
    }

    public function test_profile_lookup(): void
    {
        Auth::attempt('W001', 'faraat1403');
        $profile = Auth::profile();
        $this->assertNotNull($profile);
        $this->assertSame('W001', $profile['code']);

        Auth::logout();
        Auth::attempt('A01', 'faraat1403');
        $this->assertSame('A01', Auth::profile()['code']);
    }

    public function test_login_updates_last_login(): void
    {
        Database::update("UPDATE users SET last_login_at = '2000-01-01 00:00:00' WHERE code='M001'");
        Auth::attempt('M001', 'faraat1403');
        $after = Database::selectOne("SELECT last_login_at FROM users WHERE code='M001'")['last_login_at'];
        $this->assertNotNull($after);
        $this->assertNotSame('2000-01-01 00:00:00', $after);
        $this->assertGreaterThan(
            strtotime('2020-01-01'),
            strtotime((string) $after),
            'آخرین ورود باید به‌روز شده باشد'
        );
    }

    public function test_permission_matrix_covers_all_roles(): void
    {
        $matrix = (array) Config::get('auth.permissions');
        $this->assertNotEmpty($matrix);
        foreach ($matrix as $permission => $roles) {
            $this->assertIsArray($roles, "مجوز {$permission} باید آرایهٔ نقش‌ها باشد");
            foreach ($roles as $role) {
                $this->assertTrue(
                    $role === '*' || array_key_exists($role, array_flip(['member', 'staff', 'weaver', 'agency', 'customer'])),
                    "نقش نامعتبر «{$role}» در مجوز {$permission}"
                );
            }
        }
    }
}
