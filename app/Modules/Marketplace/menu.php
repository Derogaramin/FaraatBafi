<?php
/** منوی ماژول Marketplace برای پنل مدیر */

declare(strict_types=1);

return static function (string $panel): array {
    if ($panel !== 'admin') {
        return [];
    }
    return [
        ['title' => 'سفارش‌های فروش', 'url' => url('admin/orders'), 'icon' => 'cart', 'order' => 20, 'permission' => 'orders.view', 'match' => 'admin/orders'],
    ];
};
