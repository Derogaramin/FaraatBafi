<?php
/**
 * مسیرهای ماژول Members — بافندگان، نمایندگی‌ها، مشتریان، اعضا، پرسنل
 */

declare(strict_types=1);

use App\Modules\Members\Controllers\AdminAgencyController;
use App\Modules\Members\Controllers\AdminCustomerController;
use App\Modules\Members\Controllers\AdminMemberController;
use App\Modules\Members\Controllers\AdminWeaverController;
use Core\Router;

return static function (Router $router): void {
    // بافندگان
    $router->group(['prefix' => '/admin/weavers', 'middleware' => ['auth', 'can:weavers.view']], function (Router $r): void {
        $r->get('', [AdminWeaverController::class, 'index'], 'admin.weavers');
        $r->get('/create', [AdminWeaverController::class, 'create'], 'admin.weavers.create');
        $r->post('', [AdminWeaverController::class, 'store'], null, ['csrf']);
        $r->get('/{id:int}', [AdminWeaverController::class, 'show'], 'admin.weavers.show');
        $r->get('/{id:int}/edit', [AdminWeaverController::class, 'edit'], 'admin.weavers.edit');
        $r->post('/{id:int}', [AdminWeaverController::class, 'update'], null, ['csrf']);
        $r->post('/{id:int}/status', [AdminWeaverController::class, 'toggleStatus'], null, ['csrf']);
    });

    // نمایندگی‌ها
    $router->group(['prefix' => '/admin/agencies', 'middleware' => ['auth', 'can:agencies.view']], function (Router $r): void {
        $r->get('', [AdminAgencyController::class, 'index'], 'admin.agencies');
        $r->get('/create', [AdminAgencyController::class, 'create'], 'admin.agencies.create');
        $r->post('', [AdminAgencyController::class, 'store'], null, ['csrf']);
        $r->get('/{id:int}', [AdminAgencyController::class, 'show'], 'admin.agencies.show');
        $r->get('/{id:int}/edit', [AdminAgencyController::class, 'edit'], 'admin.agencies.edit');
        $r->post('/{id:int}', [AdminAgencyController::class, 'update'], null, ['csrf']);
        $r->post('/{id:int}/consign', [AdminAgencyController::class, 'consign'], null, ['csrf']);
    });

    // مشتریان
    $router->group(['prefix' => '/admin/customers', 'middleware' => ['auth', 'can:customers.view']], function (Router $r): void {
        $r->get('', [AdminCustomerController::class, 'index'], 'admin.customers');
        $r->get('/{id:int}', [AdminCustomerController::class, 'show'], 'admin.customers.show');
    });

    // اعضا و پرسنل (حاکمیت)
    $router->group(['prefix' => '/admin/hr', 'middleware' => ['auth', 'can:hr.view']], function (Router $r): void {
        $r->get('/members', [AdminMemberController::class, 'members'], 'admin.members');
        $r->post('/members', [AdminMemberController::class, 'storeMember'], null, ['csrf']);
        $r->get('/staff', [AdminMemberController::class, 'staff'], 'admin.staff');
        $r->post('/staff', [AdminMemberController::class, 'storeStaff'], null, ['csrf']);
    });
};
