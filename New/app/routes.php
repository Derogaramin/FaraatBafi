<?php
use Core\Router;
use Core\Application;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\MarketplaceController;
use App\Controllers\ProductionController;
use App\Controllers\MemberController;
use Core\Middleware\AuthMiddleware;
use Core\Middleware\RoleMiddleware;

$router = Application::$app->router;

// صفحات عمومی
$router->get('/', [MarketplaceController::class, 'index']);
$router->get('/product/{sku}', [MarketplaceController::class, 'show']);
$router->get('/cart', [MarketplaceController::class, 'cart']);
$router->post('/cart/add', [MarketplaceController::class, 'addToCart']);
$router->get('/checkout', [MarketplaceController::class, 'checkout'], [AuthMiddleware::class]);
$router->post('/checkout', [MarketplaceController::class, 'placeOrder'], [AuthMiddleware::class]);
$router->get('/order/{code}', [MarketplaceController::class, 'orderTracking']);

// احراز هویت
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// پنل‌ها
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// پنل مدیریت (نقش admin/member)
$router->get('/admin/products', [DashboardController::class, 'products'], [AuthMiddleware::class]); // درون کنترلر چک نقش
$router->get('/admin/orders', [DashboardController::class, 'orders'], [AuthMiddleware::class]);

// پنل بافنده (نقش weaver)
$router->get('/weaver/orders', [ProductionController::class, 'weaverOrders'], [AuthMiddleware::class]);
$router->post('/weaver/update-status', [ProductionController::class, 'updateStatus'], [AuthMiddleware::class]);

// مدیریت اعضا (نقش member/admin)
$router->get('/members', [MemberController::class, 'index'], [AuthMiddleware::class]);
$router->get('/members/create', [MemberController::class, 'create'], [AuthMiddleware::class]);
$router->post('/members/store', [MemberController::class, 'store'], [AuthMiddleware::class]);