<?php
/**
 * مسیرهای ماژول Weaver — پنل بافنده (Mobile-first)
 */

declare(strict_types=1);

use App\Modules\Weaver\Controllers\WeaverController;
use Core\Router;

return static function (Router $router): void {
    $router->group(['prefix' => '/weaver', 'middleware' => ['auth', 'role:W']], function (Router $r): void {
        $r->get('', [WeaverController::class, 'dashboard'], 'weaver.dashboard');
        $r->get('/orders', [WeaverController::class, 'orders'], 'weaver.orders');
        $r->get('/orders/{id:int}', [WeaverController::class, 'orderShow'], 'weaver.orders.show');
        $r->post('/orders/{id:int}/status', [WeaverController::class, 'changeStatus'], null, ['csrf']);
        $r->get('/materials', [WeaverController::class, 'materials'], 'weaver.materials');
        $r->get('/payments', [WeaverController::class, 'payments'], 'weaver.payments');
        $r->get('/profile', [WeaverController::class, 'profile'], 'weaver.profile');
    });
};
