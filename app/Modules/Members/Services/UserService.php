<?php
/**
 * سرویس ثبت‌نام و مدیریت کاربران
 *
 * - ثبت‌نام مشتری: ساخت user با کد C + پروفایل customers
 * - ساخت بافنده توسط مدیر: user با کد W + پروفایل weavers (کد FUCS خودکار)
 * - ساخت نمایندگی/عضو/پرسنل به‌همین سبک
 */

declare(strict_types=1);

namespace App\Modules\Members\Services;

use App\Modules\Members\Models\Agency;
use App\Modules\Members\Models\Customer;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Models\Staff;
use App\Modules\Members\Models\User;
use App\Modules\Members\Models\Weaver;
use Core\Auth;
use Core\CodeGenerator;
use Core\Database;
use Core\Events;
use RuntimeException;

final class UserService
{
    /** ثبت‌نام مشتری از بازارگاه */
    public static function registerCustomer(array $data): User
    {
        $phone = normalize_phone((string) $data['phone']);

        return Database::transaction(function () use ($data, $phone): User {
            $customerCode = CodeGenerator::customerCode(); // C000123

            $user = User::create([
                'code'          => $customerCode,
                'name'          => (string) $data['name'],
                'phone'         => $phone,
                'email'         => ($data['email'] ?? '') !== '' ? $data['email'] : null,
                'password_hash' => Auth::hash((string) $data['password']),
                'status'        => 'active',
            ]);

            Customer::create([
                'user_id'  => $user->id(),
                'code'     => $customerCode,
                'name'     => (string) $data['name'],
                'phone'    => $phone,
                'email'    => ($data['email'] ?? '') !== '' ? $data['email'] : null,
                'province' => $data['province'] ?? null,
                'city'     => $data['city'] ?? null,
                'address'  => $data['address'] ?? null,
            ]);

            Events::dispatch('user.registered', ['user' => $user, 'role' => 'customer']);
            return $user;
        });
    }

    /** ساخت بافنده توسط مدیر (با کاربر پنل اختیاری) */
    public static function createWeaver(array $data, bool $withLogin = true): Weaver
    {
        return Database::transaction(function () use ($data, $withLogin): Weaver {
            $code = CodeGenerator::weaverCode(); // W045
            $userId = null;

            if ($withLogin && !empty($data['phone'])) {
                $phone = normalize_phone((string) $data['phone']);
                $userId = User::create([
                    'code'          => $code,
                    'name'          => (string) $data['name'],
                    'phone'         => $phone,
                    'password_hash' => Auth::hash((string) ($data['password'] ?? 'faraat1403')),
                    'status'        => 'active',
                    // گذرواژهٔ پیش‌فرض → تغییر اجباری در اولین ورود
                    'must_change_password' => isset($data['password']) ? 0 : 1,
                ])->id();
            }

            $weaver = Weaver::create([
                'user_id'     => $userId,
                'code'        => $code,
                'name'        => (string) $data['name'],
                'phone'       => !empty($data['phone']) ? normalize_phone((string) $data['phone']) : null,
                'national_id' => $data['national_id'] ?? null,
                'city'        => $data['city'] ?? null,
                'village'     => $data['village'] ?? null,
                'address'     => $data['address'] ?? null,
                'specialty'   => $data['specialty'] ?? null,
                'skill_level' => $data['skill_level'] ?? 'mid',
                'loom_count'  => max(1, (int) ($data['loom_count'] ?? 1)),
                'wage_rate'   => max(0, (int) ($data['wage_rate'] ?? 0)),
                'status'      => 'active',
                'joined_at'   => $data['joined_at'] ?? date('Y-m-d'),
                'note'        => $data['note'] ?? null,
            ]);

            Events::dispatch('weaver.created', ['weaver' => $weaver]);
            return $weaver;
        });
    }

    /** ساخت نمایندگی */
    public static function createAgency(array $data): Agency
    {
        return Database::transaction(function () use ($data): Agency {
            $code = CodeGenerator::agencyCode(); // A06
            $userId = null;
            if (!empty($data['phone'])) {
                $userId = User::create([
                    'code'          => $code,
                    'name'          => (string) $data['name'],
                    'phone'         => normalize_phone((string) $data['phone']),
                    'password_hash' => Auth::hash((string) ($data['password'] ?? 'faraat1403')),
                    'status'        => 'active',
                    'must_change_password' => isset($data['password']) ? 0 : 1,
                ])->id();
            }
            $agency = Agency::create([
                'user_id'         => $userId,
                'code'            => $code,
                'name'            => (string) $data['name'],
                'manager_name'    => $data['manager_name'] ?? null,
                'province'        => $data['province'] ?? null,
                'city'            => $data['city'] ?? null,
                'address'         => $data['address'] ?? null,
                'phone'           => !empty($data['phone']) ? normalize_phone((string) $data['phone']) : null,
                'commission_rate' => (float) ($data['commission_rate'] ?? 10),
                'credit_limit'    => (int) ($data['credit_limit'] ?? 0),
                'status'          => 'active',
                'started_at'      => $data['started_at'] ?? date('Y-m-d'),
            ]);
            Events::dispatch('agency.created', ['agency' => $agency]);
            return $agency;
        });
    }

    /** ساخت عضو اصلی (سهام‌دار) با کاربر M### */
    public static function createMember(array $data): Member
    {
        return Database::transaction(function () use ($data): Member {
            $code = CodeGenerator::memberCode(); // M007
            $user = User::create([
                'code'          => $code,
                'name'          => (string) $data['name'],
                'phone'         => normalize_phone((string) $data['phone']),
                'email'         => ($data['email'] ?? '') !== '' ? $data['email'] : null,
                'password_hash' => Auth::hash((string) ($data['password'] ?? 'faraat1403')),
                'status'        => 'active',
                'must_change_password' => isset($data['password']) ? 0 : 1,
            ]);
            return Member::create([
                'user_id'     => $user->id(),
                'code'        => $code,
                'national_id' => $data['national_id'] ?? null,
                'position'    => $data['position'] ?? null,
                'shares'      => max(1, (int) ($data['shares'] ?? 1)),
                'share_value' => (int) ($data['share_value'] ?? 0),
                'join_date'   => $data['join_date'] ?? date('Y-m-d'),
                'note'        => $data['note'] ?? null,
            ]);
        });
    }

    /** ساخت پرسنل اجرایی با کاربر S### */
    public static function createStaff(array $data): Staff
    {
        return Database::transaction(function () use ($data): Staff {
            $code = CodeGenerator::staffCode(); // S001
            $user = User::create([
                'code'          => $code,
                'name'          => (string) $data['name'],
                'phone'         => normalize_phone((string) $data['phone']),
                'email'         => ($data['email'] ?? '') !== '' ? $data['email'] : null,
                'password_hash' => Auth::hash((string) ($data['password'] ?? 'faraat1403')),
                'status'        => 'active',
                'must_change_password' => isset($data['password']) ? 0 : 1,
            ]);
            return Staff::create([
                'user_id'     => $user->id(),
                'code'        => $code,
                'position'    => $data['position'] ?? null,
                'hire_date'   => $data['hire_date'] ?? date('Y-m-d'),
                'base_salary' => (int) ($data['base_salary'] ?? 0),
                'national_id' => $data['national_id'] ?? null,
            ]);
        });
    }

    /** تغییر گذرواژهٔ کاربر جاری با تأیید گذرواژهٔ فعلی */
    public static function changePassword(User $user, string $current, string $new): void
    {
        if (!password_verify($current, (string) $user->password_hash)) {
            throw new RuntimeException('گذرواژهٔ فعلی نادرست است.');
        }
        $user->update(['password_hash' => Auth::hash($new)]);
    }
}
