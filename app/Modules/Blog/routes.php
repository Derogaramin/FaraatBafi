<?php
/**
 * مسیرهای ماژول Blog — وبلاگ عمومی و مدیریت محتوا
 */

declare(strict_types=1);

use App\Modules\Blog\Controllers\AdminPostController;
use App\Modules\Blog\Controllers\BlogController;
use Core\Router;

return static function (Router $router): void {
    $router->get('/blog', [BlogController::class, 'index'], 'blog');
    $router->get('/blog/{slug}', [BlogController::class, 'show'], 'blog.show');

    $router->group(['prefix' => '/admin/blog', 'middleware' => ['auth', 'can:blog.view']], function (Router $r): void {
        $r->get('', [AdminPostController::class, 'index'], 'admin.blog');
        $r->get('/create', [AdminPostController::class, 'create'], 'admin.blog.create');
        $r->post('', [AdminPostController::class, 'store'], null, ['csrf']);
        $r->get('/{id:int}/edit', [AdminPostController::class, 'edit'], 'admin.blog.edit');
        $r->post('/{id:int}', [AdminPostController::class, 'update'], null, ['csrf']);
        $r->post('/{id:int}/delete', [AdminPostController::class, 'delete'], null, ['csrf']);
    });
};
