<?php
/** منوی ماژول Finance برای پنل مدیر */

declare(strict_types=1);

return static function (string $panel): array {
    if ($panel !== 'admin') {
        return [];
    }
    return [
        ['title' => 'دستمزد بافندگان', 'url' => url('admin/finance/payments'),    'icon' => 'coin',    'order' => 50, 'permission' => 'payroll.view',  'match' => 'admin/finance/payments'],
        ['title' => 'پورسانت‌ها',      'url' => url('admin/finance/commissions'), 'icon' => 'money',   'order' => 51, 'permission' => 'finance.view',  'match' => 'admin/finance/commissions'],
        ['title' => 'گزارش‌های مالی',   'url' => url('admin/finance/reports'),     'icon' => 'chart',   'order' => 52, 'permission' => 'payroll.view',  'match' => 'admin/finance/reports'],
        ['title' => 'اسناد حسابداری',   'url' => url('admin/finance/documents'),   'icon' => 'book',    'order' => 53, 'permission' => 'finance.view',  'match' => 'admin/finance/documents'],
    ];
};
