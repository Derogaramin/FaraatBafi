<?php
/**
 * بوت‌استرپ تست — پایگاه دادهٔ جداگانهٔ faraatbafi_test
 * یک‌بار مهاجرت + دانه‌گذاری برای کل اجرای تست‌ها
 */

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Core\App;
use Core\Database;
use Core\Migrator;
use Core\Seeder;

// پیش از boot، پیکربندی پایگاه دادهٔ تست را در 환경 بارگذاری کن
// تا هسته و همهٔ ماژول‌ها از همان ابتدا به پایگاه تست متصل شوند.
putenv('DB_NAME=faraatbafi_test');
$_ENV['DB_NAME'] = 'faraatbafi_test';
$_SERVER['DB_NAME'] = 'faraatbafi_test';

App::boot(dirname(__DIR__));

// اتصال صریح به پایگاه دادهٔ تست (برای اطمینان)
Database::setConfig([
    'host'    => getenv('DB_HOST') ?: '127.0.0.1',
    'port'    => (int) (getenv('DB_PORT') ?: 3306),
    'name'    => 'faraatbafi_test',
    'user'    => getenv('DB_USER') ?: 'faraat',
    'pass'    => getenv('DB_PASS') ?: 'faraat_1403',
    'charset' => 'utf8mb4',
]);

// ساخت و دانه‌گذاری پایگاه دادهٔ تست (یک‌بار در هر اجرا)
Migrator::fresh();
ob_start();
Seeder::run(true);
ob_end_clean();
