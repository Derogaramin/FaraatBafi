<?php
/**
 * پیکربندی نشست
 */

declare(strict_types=1);

use Core\Config;

return [
    // انقضا پس از این مدت بی‌فعالی (ثانیه) — پیش‌فرض ۳۰ دقیقه
    'idle_timeout'      => (int) Config::env('SESSION_IDLE', '1800'),
    // حداکثر عمر مطلق نشست فارغ از فعالیت — پیش‌فرض ۸ ساعت
    'absolute_lifetime' => (int) Config::env('SESSION_ABSOLUTE', '28800'),
];
