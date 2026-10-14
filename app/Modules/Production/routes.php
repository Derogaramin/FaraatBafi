<?php
/**
 * مسیرهای ماژول Production — سفارش‌های تولید، توزیع مواد، کیفیت، انبار
 */

declare(strict_types=1);

use App\Modules\Production\Controllers\AdminInventoryController;
use App\Modules\Production\Controllers\AdminProductionController;
use Core\Router;

return static function (Router $router): void {
    // سفارش‌های تولید
    $router->group(['prefix' => '/admin/production', 'middleware' => ['auth', 'can:production.view']], function (Router $r): void {
        $r->get('', [AdminProductionController::class, 'index'], 'admin.production');
        $r->get('/create', [AdminProductionController::class, 'create'], 'admin.production.create');
        $r->post('', [AdminProductionController::class, 'store'], null, ['csrf']);
        $r->get('/{id:int}', [AdminProductionController::class, 'show'], 'admin.production.show');
        $r->post('/{id:int}/assign', [AdminProductionController::class, 'assign'], null, ['csrf']);
        $r->post('/{id:int}/status', [AdminProductionController::class, 'changeStatus'], null, ['csrf']);
        $r->post('/{id:int}/materials', [AdminProductionController::class, 'distribute'], null, ['csrf']);
        $r->post('/{id:int}/qc', [AdminProductionController::class, 'inspect'], null, ['csrf']);
        $r->post('/{id:int}/authenticity', [AdminProductionController::class, 'issueCodes'], null, ['csrf']);
    });

    // انبار مواد اولیه
    $router->group(['prefix' => '/admin/inventory', 'middleware' => ['auth', 'can:inventory.view']], function (Router $r): void {
        $r->get('/materials', [AdminInventoryController::class, 'materials'], 'admin.inventory.materials');
        $r->post('/materials', [AdminInventoryController::class, 'storeMaterial'], null, ['csrf']);
        $r->post('/materials/{id:int}/adjust', [AdminInventoryController::class, 'adjust'], null, ['csrf']);
        $r->get('/movements', [AdminInventoryController::class, 'movements'], 'admin.inventory.movements');
        $r->get('/defects', [AdminInventoryController::class, 'defects'], 'admin.inventory.defects');
        $r->post('/defects', [AdminInventoryController::class, 'storeDefect'], null, ['csrf']);
    });
};
