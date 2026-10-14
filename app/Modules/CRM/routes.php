<?php
/**
 * مسیرهای ماژول CRM — تیکت‌ها (پنل کاربر + مدیر) و اعلان‌ها
 */

declare(strict_types=1);

use App\Modules\CRM\Controllers\AdminTicketController;
use App\Modules\CRM\Controllers\NotificationController;
use App\Modules\CRM\Controllers\TicketController;
use Core\Router;

return static function (Router $router): void {
    // تیکت‌های کاربر جاری (هر نقشی)
    $router->group(['prefix' => '/my/tickets', 'middleware' => ['auth']], function (Router $r): void {
        $r->get('', [TicketController::class, 'index'], 'my.tickets');
        $r->get('/create', [TicketController::class, 'create'], 'my.tickets.create');
        $r->post('', [TicketController::class, 'store'], null, ['csrf']);
        $r->get('/{id:int}', [TicketController::class, 'show'], 'my.tickets.show');
        $r->post('/{id:int}/reply', [TicketController::class, 'reply'], null, ['csrf']);
    });

    // تیکت‌ها در پنل مدیر
    $router->group(['prefix' => '/admin/tickets', 'middleware' => ['auth', 'can:tickets.view']], function (Router $r): void {
        $r->get('', [AdminTicketController::class, 'index'], 'admin.tickets');
        $r->get('/{id:int}', [AdminTicketController::class, 'show'], 'admin.tickets.show');
        $r->post('/{id:int}/reply', [AdminTicketController::class, 'reply'], null, ['csrf']);
        $r->post('/{id:int}/status', [AdminTicketController::class, 'changeStatus'], null, ['csrf']);
    });

    // اعلان‌ها
    $router->group(['prefix' => '/notifications', 'middleware' => ['auth']], function (Router $r): void {
        $r->get('', [NotificationController::class, 'index'], 'notifications');
        $r->get('/unread-count', [NotificationController::class, 'unreadCount'], 'notifications.count');
        $r->post('/read-all', [NotificationController::class, 'readAll'], null, ['csrf']);
        $r->post('/{id:int}/read', [NotificationController::class, 'read'], null, ['csrf']);
    });
};
