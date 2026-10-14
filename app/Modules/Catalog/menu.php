<?php
/** منوی ماژول Catalog برای پنل مدیر */

declare(strict_types=1);

return static function (string $panel): array {
    if ($panel !== 'admin') {
        return [];
    }
    return [
        ['title' => 'محصولات (SKU)', 'url' => url('admin/catalog/variants'), 'icon' => 'box',      'order' => 10, 'permission' => 'catalog.manage', 'match' => 'admin/catalog/variants'],
        ['title' => 'مدل‌های فنی',   'url' => url('admin/catalog/models'),   'icon' => 'loom',     'order' => 11, 'permission' => 'catalog.manage', 'match' => 'admin/catalog/models'],
        ['title' => 'رنگ‌ها',        'url' => url('admin/catalog/colors'),   'icon' => 'palette',  'order' => 12, 'permission' => 'catalog.manage', 'match' => 'admin/catalog/colors'],
        ['title' => 'طرح‌ها',        'url' => url('admin/catalog/patterns'), 'icon' => 'star',     'order' => 13, 'permission' => 'catalog.manage', 'match' => 'admin/catalog/patterns'],
        ['title' => 'انواع محصول',   'url' => url('admin/catalog/types'),    'icon' => 'package',  'order' => 14, 'permission' => 'catalog.manage', 'match' => 'admin/catalog/types'],
    ];
};
