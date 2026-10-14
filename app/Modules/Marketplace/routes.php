<?php
/**
 * مسیرهای ماژول Marketplace — بازارگاه عمومی، سبد، پرداخت، پیگیری، اصالت
 * و مدیریت سفارش‌ها در پنل مدیر
 */

declare(strict_types=1);

use App\Modules\Marketplace\Controllers\AdminOrderController;
use App\Modules\Marketplace\Controllers\AuthenticityController;
use App\Modules\Marketplace\Controllers\CartController;
use App\Modules\Marketplace\Controllers\CatalogController;
use App\Modules\Marketplace\Controllers\CheckoutController;
use App\Modules\Marketplace\Controllers\TrackingController;
use Core\Router;

return static function (Router $router): void {
    // ── بازارگاه عمومی ──
    $router->get('/marketplace', [CatalogController::class, 'index'], 'marketplace');
    $router->get('/marketplace/product/{sku}', [CatalogController::class, 'show'], 'product.show');

    // ── سبد خرید ──
    $router->get('/cart', [CartController::class, 'index'], 'cart');
    $router->get('/cart/drawer', [CartController::class, 'drawer'], 'cart.drawer');
    $router->post('/cart/add', [CartController::class, 'add'], null, ['csrf']);
    $router->post('/cart/update', [CartController::class, 'update'], null, ['csrf']);
    $router->post('/cart/remove', [CartController::class, 'remove'], null, ['csrf']);
    $router->post('/cart/clear', [CartController::class, 'clear'], null, ['csrf']);

    // ── پرداخت و ثبت سفارش ──
    $router->get('/checkout', [CheckoutController::class, 'show'], 'checkout');
    $router->post('/checkout', [CheckoutController::class, 'place'], null, ['csrf']);

    // ── پیگیری سفارش ──
    $router->get('/orders/track', [TrackingController::class, 'form'], 'orders.track.form');
    $router->post('/orders/track', [TrackingController::class, 'lookup'], null, ['csrf']);
    $router->get('/orders/{code}', [TrackingController::class, 'show'], 'orders.track');

    // ── اصالت محصول (QR) ──
    $router->get('/authenticity', [AuthenticityController::class, 'form'], 'authenticity.form');
    $router->post('/authenticity', [AuthenticityController::class, 'lookup'], null, ['csrf']);
    $router->get('/authenticity/{code}', [AuthenticityController::class, 'show'], 'authenticity.show');
    $router->get('/authenticity/{code}/qr', [AuthenticityController::class, 'qr'], 'authenticity.qr');

    // ── پنل مدیر: سفارش‌ها ──
    $router->group(['prefix' => '/admin/orders', 'middleware' => ['auth', 'can:orders.view']], function (Router $r): void {
        $r->get('', [AdminOrderController::class, 'index'], 'admin.orders');
        $r->get('/{id:int}', [AdminOrderController::class, 'show'], 'admin.orders.show');
        $r->get('/{id:int}/print', [AdminOrderController::class, 'printInvoice'], 'admin.orders.print');
        $r->get('/export', [AdminOrderController::class, 'exportCsv'], 'admin.orders.export');
        $r->post('/{id:int}/status', [AdminOrderController::class, 'changeStatus'], null, ['csrf']);
        $r->post('/{id:int}/ship', [AdminOrderController::class, 'ship'], null, ['csrf']);
        $r->post('/{id:int}/pay', [AdminOrderController::class, 'markPaid'], null, ['csrf']);
    });
};
