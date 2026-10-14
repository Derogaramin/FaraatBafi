<?php
/**
 * مسیرهای ماژول Agency — پنل نمایندگی + فروش حضوری (POS)
 */

declare(strict_types=1);

use App\Modules\Agency\Controllers\AgencyController;
use Core\Router;

return static function (Router $router): void {
    $router->group(['prefix' => '/agency', 'middleware' => ['auth', 'role:A']], function (Router $r): void {
        $r->get('', [AgencyController::class, 'dashboard'], 'agency.dashboard');
        $r->get('/pos', [AgencyController::class, 'pos'], 'agency.pos');
        $r->post('/pos/add', [AgencyController::class, 'posAdd'], null, ['csrf']);
        $r->post('/pos/sell', [AgencyController::class, 'posSell'], null, ['csrf']);
        $r->post('/pos/clear', [AgencyController::class, 'posClear'], null, ['csrf']);
        $r->get('/consignment', [AgencyController::class, 'consignment'], 'agency.consignment');
        $r->get('/commissions', [AgencyController::class, 'commissions'], 'agency.commissions');
        $r->get('/orders', [AgencyController::class, 'orders'], 'agency.orders');
    });
};
