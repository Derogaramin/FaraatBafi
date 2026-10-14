<?php
/**
 * کنترلر اعلان‌ها — ویجت HMVC و صفحهٔ فهرست
 */

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Modules\CRM\Models\Notification;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;

final class NotificationController extends Controller
{
    /**
     * ویجت زنگولهٔ اعلان‌ها — از طریق Modules::run در لایهٔ مدیر فراخوانی می‌شود
     * (نمونهٔ HMVC: ماژول دیگر بدون HTTP زیردرخواست داخلی می‌دهد)
     */
    public function widget(Request $request): Response
    {
        if (Auth::guest()) {
            return Response::html('');
        }
        $items = Notification::unreadFor((int) Auth::id(), 6);
        $count = (int) (Database::selectOne(
            'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0',
            [Auth::id()]
        )['c'] ?? 0);

        return Response::html($this->captureWidget($items, $count));
    }

    public function index(Request $request): Response
    {
        $notifications = Notification::builder()
            ->where('user_id', (int) Auth::id())
            ->orderBy('id', 'DESC')
            ->limit(60)
            ->get();
        return $this->view('CRM::notifications', [
            'title'         => 'اعلان‌های من',
            'notifications' => $notifications,
        ]);
    }

    /** شمارندهٔ نخوانده‌ها برای polling زندهٔ پنل‌ها */
    public function unreadCount(Request $request): Response
    {
        if (Auth::guest()) {
            return $this->json(['count' => 0]);
        }
        $row = Database::selectOne(
            'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0',
            [Auth::id()]
        );
        return $this->json(['count' => (int) ($row['c'] ?? 0)]);
    }

    public function readAll(Request $request): Response
    {
        Database::update('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [Auth::id()]);
        if ($request->wantsJson()) {
            return $this->json(['ok' => true, 'count' => 0]);
        }
        return $this->redirect(url('notifications'));
    }

    public function read(Request $request): Response
    {
        Database::update('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [$request->int('id'), Auth::id()]);
        return $this->json(['ok' => true]);
    }

    /** رندر HTML ویجت (بدون لایه) */
    private function captureWidget(array $items, int $count): string
    {
        return \Core\View::capture('CRM::widgets.notifications', ['items' => $items, 'count' => $count]);
    }
}
