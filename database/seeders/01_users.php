<?php
/**
 * دانه‌گذار ۰۱ — کاربران و اشخاص
 *
 * گذرواژهٔ همهٔ حساب‌های نمونه: faraat1403
 * کدها با CodeGenerator (FUCS) تولید می‌شوند تا شمارنده‌ها همگام بمانند.
 */

declare(strict_types=1);

use App\Modules\Members\Services\UserService;
use Core\Auth;
use Core\Database;

return static function (): void {
    if ((int) (Database::selectOne('SELECT COUNT(*) AS c FROM users')['c'] ?? 0) > 0) {
        echo "  (کاربران پیش‌تر دانه‌گذاری شده‌اند — رد شد)\n";
        return;
    }

    /* ── اعضای اصلی تعاونی (M) ── */
    $members = [
        ['name' => 'حاج محمد دروگر', 'phone' => '09155210001', 'position' => 'رئیس هیئت‌مدیره', 'shares' => 20, 'share_value' => 50_000_000],
        ['name' => 'فاطمه دروگر', 'phone' => '09155210002', 'position' => 'نایب‌رئیس', 'shares' => 15, 'share_value' => 50_000_000],
        ['name' => 'علی‌اکبر دروگر', 'phone' => '09155210003', 'position' => 'عضو هیئت‌مدیره — مسئول تولید', 'shares' => 12, 'share_value' => 50_000_000],
    ];
    foreach ($members as $m) {
        UserService::createMember($m + ['password' => 'faraat1403']);
    }

    /* ── پرسنل اجرایی (S) ── */
    $staff = [
        ['name' => 'زهرا کریمی', 'phone' => '09155220001', 'position' => 'مدیر اجرایی', 'base_salary' => 28_000_000],
        ['name' => 'حسین نوروزی', 'phone' => '09155220002', 'position' => 'انباردار و کنترل کیفیت', 'base_salary' => 18_000_000],
    ];
    foreach ($staff as $s) {
        UserService::createStaff($s + ['password' => 'faraat1403']);
    }

    /* ── بافندگان (W) — نمونهٔ ۲۴ نفر از ~۱۰۰ بافنده ── */
    $weavers = [
        ['name' => 'بی‌بی گل مرادی', 'city' => 'تربت جام', 'village' => 'سمیع‌آباد', 'specialty' => 'حوله و دستمال', 'skill_level' => 'master', 'loom_count' => 2, 'wage_rate' => 320_000, 'phone' => '09155310001'],
        ['name' => 'گل‌بیبی جامی', 'city' => 'تربت جام', 'village' => 'کاریز', 'specialty' => 'شال و روبندک', 'skill_level' => 'senior', 'loom_count' => 1, 'wage_rate' => 260_000, 'phone' => '09155310002'],
        ['name' => 'زرگل احمدی', 'city' => 'تربت جام', 'village' => 'نصرآباد', 'specialty' => 'فرت و پارچه', 'skill_level' => 'senior', 'loom_count' => 2, 'wage_rate' => 280_000, 'phone' => '09155310003'],
        ['name' => 'مریم قهرمانی', 'city' => 'تربت جام', 'village' => 'مزرعه کال', 'specialty' => 'حوله', 'skill_level' => 'mid', 'loom_count' => 1, 'wage_rate' => 210_000, 'phone' => '09155310004'],
        ['name' => 'سکینه تیموری', 'city' => 'تربت جام', 'village' => 'چمن گل', 'specialty' => 'دستمال و شال', 'skill_level' => 'mid', 'loom_count' => 1, 'wage_rate' => 200_000, 'phone' => '09155310005'],
        ['name' => 'فاطمه رحیمی', 'city' => 'تربت جام', 'village' => 'سمیع‌آباد', 'specialty' => 'تن‌پوش', 'skill_level' => 'senior', 'loom_count' => 1, 'wage_rate' => 270_000, 'phone' => '09155310006'],
        ['name' => 'خدیجه عرب', 'city' => 'تربت جام', 'village' => 'کاریز', 'specialty' => 'حوله', 'skill_level' => 'mid', 'loom_count' => 1, 'wage_rate' => 190_000, 'phone' => '09155310007'],
        ['name' => 'نرگس نوروزی', 'city' => 'تربت جام', 'village' => 'نصرآباد', 'specialty' => 'شال', 'skill_level' => 'apprentice', 'loom_count' => 1, 'wage_rate' => 140_000, 'phone' => '09155310008'],
    ];
    // بافندگان بیشتر بدون پنل ورود (فقط رکورد تولیدی)
    $extraNames = ['زینب صالحی', 'آمنه کاظمی', 'حلیمه جعفری', 'ربابه میرزایی', 'سمن‌گل ترکمانی', 'عزیز بیگم',
        'ماه‌گل احمدی', 'گل‌پری رحیمی', 'شهربانو قاسمی', 'کبری نظری', 'معصومه شریفی', 'طوبی حسینی',
        'ساره دهقان', 'حدیث اکبری', 'ریحانه موسوی', 'زهره باقری'];
    foreach ($extraNames as $i => $n) {
        $weavers[] = ['name' => $n, 'city' => 'تربت جام', 'village' => '', 'specialty' => 'متنوع', 'skill_level' => ['junior', 'mid', 'senior'][$i % 3], 'loom_count' => 1, 'wage_rate' => 150_000 + ($i % 5) * 20_000, 'phone' => ''];
    }

    foreach ($weavers as $i => $w) {
        UserService::createWeaver($w + [
            'joined_at' => date('Y-m-d', strtotime('-' . (100 + $i * 13) . ' days')),
            'password'  => 'faraat1403', // نمونه: صریح تا پرچم «تغییر اجباری» برای دمو فعال نشود
        ], $w['phone'] !== '');
    }

    /* ── نمایندگی‌ها (A) ── */
    $agencies = [
        ['name' => 'نمایندگی مرکزی تربت جام', 'manager_name' => 'مهدی سلمانی', 'city' => 'تربت جام', 'province' => 'خراسان رضوی', 'phone' => '09155410001', 'commission_rate' => 12, 'credit_limit' => 200_000_000],
        ['name' => 'نمایندگی مشهد — احمدآباد', 'manager_name' => 'سارا فتحی', 'city' => 'مشهد', 'province' => 'خراسان رضوی', 'phone' => '09155410002', 'commission_rate' => 10, 'credit_limit' => 300_000_000],
        ['name' => 'نمایندگی تهران — بازار تجریش', 'manager_name' => 'امیر رستگار', 'city' => 'تهران', 'province' => 'تهران', 'phone' => '09121110003', 'commission_rate' => 10, 'credit_limit' => 400_000_000],
        ['name' => 'نمایندگی نیشابور', 'manager_name' => 'حمید شریف‌زاده', 'city' => 'نیشابور', 'province' => 'خراسان رضوی', 'phone' => '09155410004', 'commission_rate' => 9, 'credit_limit' => 100_000_000],
        ['name' => 'نمایندگی هرات (صادراتی)', 'manager_name' => 'نصیر احمدی', 'city' => 'هرات', 'province' => 'هرات', 'phone' => '09155410005', 'commission_rate' => 8, 'credit_limit' => 150_000_000],
    ];
    foreach ($agencies as $a) {
        UserService::createAgency($a + ['password' => 'faraat1403', 'address' => $a['city'] . ' — خیابان اصلی']);
    }

    /* ── مشتریان (C) ── */
    $customers = [
        ['name' => 'نگار موسوی', 'phone' => '09121234567', 'city' => 'مشهد'],
        ['name' => 'رضا شریفی', 'phone' => '09123456781', 'city' => 'تهران'],
        ['name' => 'سمیرا قاسمی', 'phone' => '09123456782', 'city' => 'تربت جام'],
        ['name' => 'مهدی انصاری', 'phone' => '09123456783', 'city' => 'شیراز'],
        ['name' => 'الهام رجبی', 'phone' => '09123456784', 'city' => 'اصفهان'],
        ['name' => 'یوسف بهرامی', 'phone' => '09123456785', 'city' => 'تبریز'],
        ['name' => 'مریم صادقی', 'phone' => '09123456786', 'city' => 'مشهد'],
        ['name' => 'احسان کاظمی', 'phone' => '09123456787', 'city' => 'کرج'],
    ];
    foreach ($customers as $c) {
        $phone = normalize_phone($c['phone']);
        Database::transaction(function () use ($c, $phone): void {
            $code = \Core\CodeGenerator::customerCode();
            $user = \App\Modules\Members\Models\User::create([
                'code' => $code, 'name' => $c['name'], 'phone' => $phone,
                'password_hash' => Auth::hash('faraat1403'), 'status' => 'active',
            ]);
            \App\Modules\Members\Models\Customer::create([
                'user_id' => $user->id(), 'code' => $code, 'name' => $c['name'],
                'phone' => $phone, 'city' => $c['city'], 'province' => $c['city'],
                'address' => $c['city'] . ' — نشانی نمونه، خیابان بهار، پلاک ۱۲',
            ]);
        });
    }

    // تنظیم رمز عبور یکسان برای همه (از قبل در سرویس‌ها انجام شده) — بررسی نهایی
    $count = (int) (Database::selectOne('SELECT COUNT(*) AS c FROM users')['c'] ?? 0);
    echo '  → ' . fa((string) $count) . " کاربر ساخته شد (گذرواژهٔ نمونه: faraat1403)\n";
};
