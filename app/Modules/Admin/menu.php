<?php
/**
 * منوی ماژول Admin برای پنل مدیر
 */

declare(strict_types=1);

return static function (string $panel): array {
    if ($panel !== 'admin') {
        return [];
    }
    return [
        ['title' => 'داشبورد',      'url' => url('admin'),               'icon' => 'dashboard', 'order' => 1,  'match' => 'exact:admin'],
        ['title' => 'هوش تجاری',    'url' => url('admin/bi'),            'icon' => 'chart',     'order' => 90, 'permission' => 'bi.view', 'match' => 'admin/bi'],
        ['title' => 'جلسات و مصوبات', 'url' => url('admin/governance/meetings'), 'icon' => 'meeting', 'order' => 91, 'permission' => 'governance.view', 'match' => 'admin/governance/meetings'],
        ['title' => 'رأی‌گیری',     'url' => url('admin/governance/votes'), 'icon' => 'vote',   'order' => 92, 'permission' => 'governance.view', 'match' => 'admin/governance/votes'],
        ['title' => 'مکاتبات داخلی', 'url' => url('admin/governance/letters'), 'icon' => 'letter', 'order' => 93, 'permission' => 'governance.view', 'match' => 'admin/governance/letters'],
        ['title' => 'ممیزی تغییرات', 'url' => url('admin/audits'),       'icon' => 'clock',     'order' => 95, 'permission' => 'admin.access',  'match' => 'admin/audits'],
        ['title' => 'امنیت حساب',    'url' => url('admin/security'),     'icon' => 'quality',   'order' => 98, 'match' => 'admin/security'],
        ['title' => 'تنظیمات',      'url' => url('admin/settings'),      'icon' => 'settings',  'order' => 99, 'permission' => 'governance.view', 'match' => 'admin/settings'],
    ];
};
