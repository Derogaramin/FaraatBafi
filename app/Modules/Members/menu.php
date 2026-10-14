<?php
/** منوی ماژول Members برای پنل مدیر */

declare(strict_types=1);

return static function (string $panel): array {
    if ($panel !== 'admin') {
        return [];
    }
    return [
        ['title' => 'بافندگان',   'url' => url('admin/weavers'),   'icon' => 'users',  'order' => 40, 'permission' => 'weavers.view',   'match' => 'admin/weavers'],
        ['title' => 'نمایندگی‌ها', 'url' => url('admin/agencies'),  'icon' => 'store',  'order' => 41, 'permission' => 'agencies.view',  'match' => 'admin/agencies'],
        ['title' => 'مشتریان',    'url' => url('admin/customers'), 'icon' => 'user',   'order' => 42, 'permission' => 'customers.view', 'match' => 'admin/customers'],
        ['title' => 'اعضای تعاونی', 'url' => url('admin/hr/members'), 'icon' => 'handshake', 'order' => 43, 'permission' => 'hr.view', 'match' => 'admin/hr/members'],
        ['title' => 'پرسنل',      'url' => url('admin/hr/staff'),  'icon' => 'letter', 'order' => 44, 'permission' => 'hr.view',    'match' => 'admin/hr/staff'],
    ];
};
