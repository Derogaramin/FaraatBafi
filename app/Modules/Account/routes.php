<?php
/**
 * مسیرهای ماژول Account — پنل مشتری (سفارش‌ها، پروفایل)
 */

declare(strict_types=1);

use App\Modules\Account\Controllers\AccountController;
use Core\Router;

return static function (Router $router): void {
    $router->group(['prefix' => '/my', 'middleware' => ['auth']], function (Router $r): void {
        $r->get('', [AccountController::class, 'dashboard'], 'my.dashboard');
        $r->get('/orders', [AccountController::class, 'orders'], 'my.orders');
        $r->get('/orders/{id:int}', [AccountController::class, 'orderShow'], 'my.orders.show');
        $r->get('/profile', [AccountController::class, 'profile'], 'my.profile');
        $r->post('/profile', [AccountController::class, 'updateProfile'], null, ['csrf']);
        $r->post('/password', [AccountController::class, 'changePassword'], null, ['csrf']);
    });
};
