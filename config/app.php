<?php
/**
 * پیکربندی عمومی سامانه
 */

declare(strict_types=1);

use Core\Config;

return [
    'name'       => 'سامانه جامع تعاونی فرت‌بافی',
    'brand'      => 'فرت',
    'brand_full' => 'تعاونی تولیدی صنایع دستی نقشینه بافت تربت جام',
    'version'    => '2.0.0',
    'env'        => Config::env('APP_ENV', 'local'),
    'debug'      => Config::env('APP_DEBUG', '1') === '1',
    'base_url'   => rtrim(Config::env('APP_URL', ''), '/'),
    'base_path'  => parse_url(Config::env('APP_URL', ''), PHP_URL_PATH) ?: '',
    'public_url' => Config::env('PUBLIC_URL', Config::env('APP_URL', '')),
    'timezone'   => 'Asia/Tehran',
    'locale'     => 'fa',
    'per_page'   => 15,
    'log_events' => true,

    // رویدادهایی که به صف می‌روند (پردازش با `php bin/faraat queue:work`)
    // payload این رویدادها باید فقط دادهٔ سریال‌شدنی باشد.
    'queued_events' => [],
    'queue_batch'   => 50,

    // پیامک: driver=log یعنی فقط در storage/logs/sms.log نوشته می‌شود
    'sms' => [
        'driver' => Config::env('SMS_DRIVER', 'log'),
    ],
];
