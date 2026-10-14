<?php
/**
 * پیکربندی پایگاه داده (MySQL/MariaDB)
 */

declare(strict_types=1);

use Core\Config;

return [
    'host'    => Config::env('DB_HOST', '127.0.0.1'),
    'port'    => (int) Config::env('DB_PORT', '3306'),
    'name'    => Config::env('DB_NAME', 'faraatbafi'),
    'user'    => Config::env('DB_USER', 'faraat'),
    'pass'    => Config::env('DB_PASS', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
