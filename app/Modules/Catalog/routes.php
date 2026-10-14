<?php
/**
 * مسیرهای ماژول Catalog — مدیریت کاتالوگ در پنل مدیر
 * انواع / مدل فنی / رنگ / طرح / واریانت (SKU)
 */

declare(strict_types=1);

use App\Modules\Catalog\Controllers\AdminColorController;
use App\Modules\Catalog\Controllers\AdminModelController;
use App\Modules\Catalog\Controllers\AdminPatternController;
use App\Modules\Catalog\Controllers\AdminTypeController;
use App\Modules\Catalog\Controllers\AdminVariantController;
use Core\Router;

return static function (Router $router): void {
    $router->group(['prefix' => '/admin/catalog', 'middleware' => ['auth', 'can:catalog.manage']], function (Router $r): void {
        // انواع محصول
        $r->get('/types', [AdminTypeController::class, 'index'], 'admin.types');
        $r->post('/types', [AdminTypeController::class, 'store'], null, ['csrf']);
        $r->post('/types/{id:int}/toggle', [AdminTypeController::class, 'toggle'], null, ['csrf']);

        // مدل‌های فنی
        $r->get('/models', [AdminModelController::class, 'index'], 'admin.models');
        $r->get('/models/create', [AdminModelController::class, 'create'], 'admin.models.create');
        $r->post('/models', [AdminModelController::class, 'store'], null, ['csrf']);
        $r->get('/models/{id:int}/edit', [AdminModelController::class, 'edit'], 'admin.models.edit');
        $r->post('/models/{id:int}', [AdminModelController::class, 'update'], null, ['csrf']);
        $r->post('/models/{id:int}/toggle', [AdminModelController::class, 'toggle'], null, ['csrf']);

        // رنگ‌ها
        $r->get('/colors', [AdminColorController::class, 'index'], 'admin.colors');
        $r->post('/colors', [AdminColorController::class, 'store'], null, ['csrf']);
        $r->post('/colors/{id:int}/toggle', [AdminColorController::class, 'toggle'], null, ['csrf']);

        // طرح‌ها
        $r->get('/patterns', [AdminPatternController::class, 'index'], 'admin.patterns');
        $r->get('/patterns/create', [AdminPatternController::class, 'create'], 'admin.patterns.create');
        $r->post('/patterns', [AdminPatternController::class, 'store'], null, ['csrf']);
        $r->get('/patterns/{id:int}/edit', [AdminPatternController::class, 'edit'], 'admin.patterns.edit');
        $r->post('/patterns/{id:int}', [AdminPatternController::class, 'update'], null, ['csrf']);

        // واریانت‌ها (SKU)
        $r->get('/variants', [AdminVariantController::class, 'index'], 'admin.variants');
        $r->get('/variants/create', [AdminVariantController::class, 'create'], 'admin.variants.create');
        $r->post('/variants', [AdminVariantController::class, 'store'], null, ['csrf']);
        $r->get('/variants/{id:int}/edit', [AdminVariantController::class, 'edit'], 'admin.variants.edit');
        $r->post('/variants/{id:int}', [AdminVariantController::class, 'update'], null, ['csrf']);
        $r->post('/variants/{id:int}/toggle', [AdminVariantController::class, 'toggle'], null, ['csrf']);
        $r->post('/sku-preview', [AdminVariantController::class, 'skuPreview'], 'admin.sku.preview', ['csrf']);
    });
};
