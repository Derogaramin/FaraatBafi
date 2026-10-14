<?php
/** منوی ماژول Production برای پنل مدیر */

declare(strict_types=1);

return static function (string $panel): array {
    if ($panel !== 'admin') {
        return [];
    }
    return [
        ['title' => 'سفارش‌های تولید', 'url' => url('admin/production'),           'icon' => 'shuttle',   'order' => 30, 'permission' => 'production.view', 'match' => 'admin/production'],
        ['title' => 'انبار مواد اولیه', 'url' => url('admin/inventory/materials'), 'icon' => 'warehouse', 'order' => 31, 'permission' => 'inventory.view',  'match' => 'admin/inventory/materials'],
        ['title' => 'حرکت‌های انبار',   'url' => url('admin/inventory/movements'), 'icon' => 'refresh',   'order' => 32, 'permission' => 'inventory.view',  'match' => 'admin/inventory/movements'],
        ['title' => 'انواع عیب بافت',   'url' => url('admin/inventory/defects'),   'icon' => 'quality',   'order' => 33, 'permission' => 'quality.view',    'match' => 'admin/inventory/defects'],
    ];
};
