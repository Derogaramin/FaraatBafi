<?php
/**
 * مسیرهای ماژول Auth — ورود، ثبت‌نام، خروج، بازیابی گذرواژه
 */

declare(strict_types=1);

use App\Modules\Auth\Controllers\AuthController;
use Core\Router;

return static function (Router $router): void {
    $router->get('/login', [AuthController::class, 'showLogin'], 'login', ['guest']);
    $router->post('/login', [AuthController::class, 'login'], null, ['guest', 'csrf']);

    $router->get('/register', [AuthController::class, 'showRegister'], 'register', ['guest']);
    $router->post('/register', [AuthController::class, 'register'], null, ['guest', 'csrf']);

    $router->get('/verify-2fa', [AuthController::class, 'showVerify2fa'], 'verify2fa.form', ['guest']);
    $router->post('/verify-2fa', [AuthController::class, 'verify2fa'], null, ['guest', 'csrf']);

    $router->post('/logout', [AuthController::class, 'logout'], 'logout', ['auth', 'csrf']);

    $router->get('/forgot-password', [AuthController::class, 'showForgot'], 'forgot', ['guest']);
    $router->post('/forgot-password', [AuthController::class, 'forgot'], null, ['guest', 'csrf']);
};
