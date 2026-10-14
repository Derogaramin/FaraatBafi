<?php
/**
 * نقطهٔ ورود عمومی سامانهٔ فرت‌بافی
 *
 * تمام درخواست‌ها از اینجا وارد هسته می‌شوند (Front Controller).
 * برای اجرای محلی:  php -S localhost:8080 -t public
 */

declare(strict_types=1);

// مسیرهای autoload و هسته
require dirname(__DIR__) . '/vendor/autoload.php';

use Core\App;
use Core\Request;

App::boot(dirname(__DIR__));

$response = App::handle(new Request());
$response->send();
