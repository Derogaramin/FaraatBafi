<?php
/**
 * مسیرهای ماژول Home — صفحهٔ فرود، دربارهٔ ما، تماس
 */

declare(strict_types=1);

use App\Modules\Home\Controllers\HomeController;
use App\Modules\Home\Controllers\SearchController;
use Core\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index'], 'home');
    $router->get('/search', [SearchController::class, 'search'], 'search');
    $router->get('/about', [HomeController::class, 'about'], 'about');
    $router->get('/contact', [HomeController::class, 'contact'], 'contact');
    $router->post('/contact', [HomeController::class, 'contactStore'], null, ['csrf']);
};
