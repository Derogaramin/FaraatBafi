<?php
/**
 * کنترلر احراز هویت — ورود/ثبت‌نام/خروج/بازیابی
 *
 * شناسهٔ ورود: کد کاربری FUCS (M001/W023/A01/C000125)، شمارهٔ موبایل یا رایانامه.
 */

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Modules\Members\Services\UserService;
use Core\App;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Jalali;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Throttle;
use Core\Totp;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return $this->view('Auth::login', [
            'title'    => 'ورود به سامانه',
            'layout'   => 'layouts.auth',
            'demoHint' => true,
        ]);
    }

    public function login(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'identifier' => 'required',
            'password'   => 'required',
        ], ['identifier' => 'کد کاربری / موبایل', 'password' => 'گذرواژه']);

        $identifier = (string) $data['identifier'];
        $ip = $request->ip();

        // محدودسازی نرخ (نقد S2): قفل پس از ۵ شکست پیاپی
        $locked = Throttle::lockedSeconds($identifier, $ip);
        if ($locked > 0) {
            Session::flash('error', Throttle::lockMessage($locked));
            Session::flashInput(['identifier' => $identifier]);
            return $this->redirect(url('login'));
        }

        $user = Auth::verify($identifier, (string) $data['password']);
        if ($user === null) {
            Throttle::hit($identifier, $ip, false);
            $left = Throttle::attemptsLeft($identifier, $ip);
            $message = 'شناسهٔ کاربری یا گذرواژه نادرست است.';
            if ($left <= 2 && $left > 0) {
                $message .= ' (تنها ' . Jalali::digits((string) $left) . ' تلاش باقی مانده)';
            }
            Session::flash('error', $message);
            Session::flashInput(['identifier' => $identifier]);
            return $this->redirect(url('login'));
        }
        Throttle::hit($identifier, $ip, true);

        // احراز دو مرحله‌ای: ورود را معلق و به صفحهٔ رمز یک‌بارمصرف هدایت کن
        if (!empty($user['totp_secret'])) {
            Session::put('2fa_user_id', (int) $user['id']);
            Session::put('intended', Session::get('intended'));
            return $this->redirect(url('verify-2fa'));
        }

        Auth::login($user);
        Session::put('2fa_ok', true); // بدون 2FA یعنی یک‌مرحله‌ای گذرانده شد
        $intended = Session::get('intended');
        Session::forget('intended');
        Session::flash('success', 'خوش آمدید، ' . ($user['name'] ?? '') . ' 🧵');
        return $this->redirect($intended ? url(ltrim((string) $intended, '/')) : App::panelHome());
    }

    /** فرم رمز یک‌بارمصرف (TOTP) */
    public function showVerify2fa(Request $request): Response
    {
        if (!Session::has('2fa_user_id')) {
            return $this->redirect(url('login'));
        }
        return $this->view('Auth::verify-2fa', [
            'title'  => 'احراز هویت دو مرحله‌ای',
            'layout' => 'layouts.auth',
        ]);
    }

    /** بررسی رمز یک‌بارمصرف و تکمیل ورود */
    public function verify2fa(Request $request): Response
    {
        $userId = Session::get('2fa_user_id');
        if ($userId === null) {
            return $this->redirect(url('login'));
        }
        $user = Database::selectOne('SELECT * FROM users WHERE id = ? AND status = "active"', [(int) $userId]);
        if ($user === null || empty($user['totp_secret'])) {
            Session::forget('2fa_user_id');
            return $this->redirect(url('login'));
        }
        $code = $request->str('code');
        if (!Totp::verify((string) $user['totp_secret'], $code)) {
            Session::flash('error', 'رمز یک‌بارمصرف نامعتبر است. ساعت گوشی خود را بررسی کنید.');
            return $this->redirect(url('verify-2fa'));
        }
        Session::forget('2fa_user_id');
        Auth::login($user);
        Session::put('2fa_ok', true);
        $intended = Session::get('intended');
        Session::forget('intended');
        Session::flash('success', 'خوش آمدید، ' . $user['name'] . ' 🧵');
        return $this->redirect($intended ? url(ltrim((string) $intended, '/')) : App::panelHome());
    }

    public function showRegister(Request $request): Response
    {
        return $this->view('Auth::register', ['title' => 'ثبت‌نام مشتری', 'layout' => 'layouts.auth']);
    }

    public function register(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name'     => 'required|min:3|max:120',
            'phone'    => 'required|phone|unique:users,phone',
            'email'    => 'email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'city'     => 'max:80',
        ], [
            'name'     => 'نام و نام خانوادگی',
            'phone'    => 'شمارهٔ موبایل',
            'email'    => 'رایانامه',
            'password' => 'گذرواژه',
            'city'     => 'شهر',
        ]);

        $user = UserService::registerCustomer($data);
        Auth::login($user->attributes());

        Session::flash('success', 'ثبت‌نام کامل شد. کد کاربری شما: ' . $user->code . ' — آن را یادداشت کنید.');
        return $this->redirect(url('my'));
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        Session::flash('info', 'از سامانه خارج شدید.');
        return $this->redirect(url('/'));
    }

    public function showForgot(Request $request): Response
    {
        return $this->view('Auth::forgot', ['title' => 'بازیابی گذرواژه', 'layout' => 'layouts.auth']);
    }

    public function forgot(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'phone' => 'required|phone',
        ], ['phone' => 'شمارهٔ موبایل']);

        $phone = normalize_phone((string) $data['phone']);
        $user = Database::selectOne('SELECT id, name FROM users WHERE phone = ?', [$phone]);

        // در نسخهٔ جاری، لینک بازنشانی پیامک نمی‌شود؛ راهنمایی یکسان برای همه
        if ($user) {
            Session::flash('info', 'اگر شمارهٔ «' . fa($phone) . '» در سامانه ثبت شده باشد، راهنمای بازنشانی گذرواژه ارسال خواهد شد. در حال حاضر برای بازنشانی با دفتر تعاونی تماس بگیرید.');
        } else {
            Session::flash('info', 'اگر شمارهٔ «' . fa($phone) . '» در سامانه ثبت شده باشد، راهنمای بازنشانی گذرواژه ارسال خواهد شد.');
        }
        return $this->redirect(url('forgot-password'));
    }
}
