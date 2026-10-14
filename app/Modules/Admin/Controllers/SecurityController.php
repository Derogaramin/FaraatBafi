<?php
/**
 * کنترلر امنیت حساب — ثبت‌نام/غیرفعال‌سازی احراز دو مرحله‌ای (TOTP)
 */

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\QR;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Totp;

final class SecurityController extends Controller
{
    /** صفحهٔ امنیت حساب: وضعیت 2FA + QR ثبت‌نام + تاریخچهٔ ورود */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $secret = (string) ($user['totp_secret'] ?? '');
        $pendingSecret = (string) Session::get('2fa_pending_secret', '');

        // اگر ثبت‌نام در جریان نباشد، کلید جدید موقت تولید می‌شود
        if ($secret === '' && $pendingSecret === '') {
            $pendingSecret = Totp::generateSecret();
            Session::put('2fa_pending_secret', $pendingSecret);
        }
        $activeSecret = $pendingSecret !== '' ? $pendingSecret : $secret;

        $qrSvg = '';
        if ($activeSecret !== '') {
            $uri = Totp::provisioningUri($activeSecret, (string) $user['code'] . ' — ' . $user['name'], 'فرت‌بافی');
            $qrSvg = QR::svg($uri, 6, '#1A3B4C');
        }

        $logins = Database::select(
            'SELECT identifier, ip, success, attempted_at FROM login_attempts
             WHERE identifier IN (?, ?) ORDER BY id DESC LIMIT 15',
            [mb_strtolower((string) $user['code']), mb_strtolower((string) $user['phone'])]
        );

        return $this->view('Admin::security', [
            'title'         => 'امنیت حساب',
            'secret'        => $activeSecret,
            'qrSvg'         => $qrSvg,
            'enabled'       => $secret !== '',
            'pending'       => $secret === '' && $pendingSecret !== '',
            'mustChange'    => (int) ($user['must_change_password'] ?? 0) === 1,
            'logins'        => $logins,
        ]);
    }

    /** تأیید رمز و فعال‌سازی 2FA */
    public function enable(Request $request): Response
    {
        $pending = (string) Session::get('2fa_pending_secret', '');
        if ($pending === '') {
            Session::flash('error', 'کلید موقتی در جریان نیست؛ صفحه را تازه کنید.');
            return $this->redirect(url('admin/security'));
        }
        $code = $request->str('code');
        if (!Totp::verify($pending, $code)) {
            Session::flash('error', 'رمز یک‌بارمصرف نادرست است. دوباره تلاش کنید.');
            return $this->redirect(url('admin/security'));
        }
        Database::update('UPDATE users SET totp_secret = ? WHERE id = ?', [$pending, Auth::id()]);
        Session::forget('2fa_pending_secret');
        Session::put('2fa_ok', true);
        Auth::resetResolution();
        Session::flash('success', 'احراز هویت دو مرحله‌ای فعال شد. کلید بازیابی را یادداشت کنید.');
        return $this->redirect(url('admin/security'));
    }

    /** غیرفعال‌سازی 2FA (با گذرواژهٔ فعلی) */
    public function disable(Request $request): Response
    {
        $user = Auth::user();
        if (!password_verify($request->str('password'), (string) $user['password_hash'])) {
            Session::flash('error', 'گذرواژهٔ فعلی نادرست است.');
            return $this->redirect(url('admin/security'));
        }
        Database::update('UPDATE users SET totp_secret = NULL WHERE id = ?', [Auth::id()]);
        Session::forget('2fa_ok');
        Auth::resetResolution();
        Session::flash('info', 'احراز دو مرحله‌ای غیرفعال شد.');
        return $this->redirect(url('admin/security'));
    }
}
