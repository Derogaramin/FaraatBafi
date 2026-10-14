<?php
/**
 * کلاس Auth — احراز هویت و کنترل دسترسی نقش‌محور (RBAC)
 *
 * نقش‌ها بر اساس پیشوند کد کاربر (FUCS):
 *   M → عضو اصلی (حاکمیت کامل، مالی، جلسات، رأی‌گیری)
 *   S → مدیر اجرایی (محصولات، سفارش‌ها، محتوا — بدون رأی‌گیری)
 *   W → بافنده (پنل شخصی: سفارش‌ها، مواد، دستمزد)
 *   A → نمایندگی (داشبورد فروش منطقه‌ای، فروش حضوری)
 *   C → مشتری (بازارگاه، پیگیری سفارش، تیکت)
 */

declare(strict_types=1);

namespace Core;

final class Auth
{
    /** نگاشت پیشوند کد → نقش */
    public const PREFIX_ROLE = [
        'M' => 'member',
        'S' => 'staff',
        'W' => 'weaver',
        'A' => 'agency',
        'C' => 'customer',
    ];

    public const ROLE_LABELS = [
        'member'   => 'عضو اصلی',
        'staff'    => 'مدیر اجرایی',
        'weaver'   => 'بافنده',
        'agency'   => 'نمایندگی',
        'customer' => 'مشتری',
    ];

    private static ?array $user = null;

    private static bool $resolved = false;

    /**
     * بررسی اعتبارنامه بدون ورود — رکورد کاربر یا null.
     * (برای جریان دو مرحله‌ای: ابتدا گذرواژه، سپس TOTP)
     */
    public static function verify(string $identifier, string $password): ?array
    {
        $identifier = trim($identifier);
        $phone = normalize_phone($identifier);

        $user = Database::selectOne(
            'SELECT * FROM users WHERE code = ? OR phone = ? OR email = ? LIMIT 1',
            [$identifier, $phone, $identifier !== '' ? $identifier : null]
        );

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return null;
        }
        if (($user['status'] ?? 'active') !== 'active') {
            return null;
        }
        return $user;
    }

    /** تلاش برای ورود با شناسه (کد کاربر / موبایل / رایانامه) و گذرواژه */
    public static function attempt(string $identifier, string $password): bool
    {
        $user = self::verify($identifier, $password);
        if ($user === null) {
            return false;
        }
        self::login($user);
        return true;
    }

    /** آیا کاربر جاری احراز دو مرحله‌ای را در این نشست گذرانده؟ */
    public static function twoFactorCleared(): bool
    {
        return (bool) Session::get('2fa_ok', false);
    }

    /** ورود کاربر (ذخیره در نشست) */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        Session::put('user_code', (string) $user['code']);
        Database::update('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        self::$user = $user;
        self::$resolved = true;
    }

    public static function logout(): void
    {
        Session::forget('user_id');
        Session::forget('user_code');
        Session::regenerate();
        self::$user = null;
        self::$resolved = true;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    /** رکورد کاربر جاری (از users) */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;
        $id = Session::get('user_id');
        if ($id === null) {
            return self::$user = null;
        }
        $user = Database::selectOne('SELECT * FROM users WHERE id = ? AND status = "active"', [(int) $id]);
        return self::$user = $user ?: null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function code(): ?string
    {
        return self::user()['code'] ?? null;
    }

    /** نقش کاربر جاری بر اساس پیشوند کد (FUCS) */
    public static function role(): string
    {
        $code = (string) (self::user()['code'] ?? '');
        $prefix = $code !== '' ? strtoupper($code[0]) : '';
        return self::PREFIX_ROLE[$prefix] ?? 'guest';
    }

    public static function roleLabel(): string
    {
        return self::ROLE_LABELS[self::role()] ?? 'مهمان';
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    /** عضو اصلی = بالاترین سطح دسترسی */
    public static function isSuper(): bool
    {
        return self::role() === 'member';
    }

    /** دسترسی مدیریتی (عضو اصلی یا مدیر اجرایی) */
    public static function isAdmin(): bool
    {
        return self::is('member', 'staff');
    }

    /**
     * بررسی مجوز RBAC — ماتریس مجوزها در config/auth.php
     * مثال: Auth::can('orders.manage')
     */
    public static function can(string $permission): bool
    {
        $role = self::role();
        if ($role === 'guest') {
            return false;
        }
        $matrix = (array) Config::get('auth.permissions', []);
        $allowedRoles = $matrix[$permission] ?? null;
        if ($allowedRoles === null) {
            return false; // مجوز ناشناخته → رد
        }
        return in_array('*', $allowedRoles, true) || in_array($role, $allowedRoles, true);
    }

    /** پروفایل تخصصی کاربر جاری (weaver/agency/customer/member/staff) */
    public static function profile(): ?array
    {
        $user = self::user();
        if (!$user) {
            return null;
        }
        $table = match (self::role()) {
            'weaver'   => 'weavers',
            'agency'   => 'agencies',
            'customer' => 'customers',
            'member'   => 'members',
            'staff'    => 'staff',
            default    => null,
        };
        if ($table === null) {
            return null;
        }
        return Database::selectOne("SELECT * FROM `{$table}` WHERE user_id = ? LIMIT 1", [$user['id']]);
    }

    /** hash گذرواژه */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    public static function resetResolution(): void
    {
        self::$user = null;
        self::$resolved = false;
    }
}
