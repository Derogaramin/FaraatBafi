<?php
/** منوی پنل بافنده (ناوبری پایین — موبایل) */

declare(strict_types=1);

return static function (string $panel): array {
    if ($panel !== 'weaver') {
        return [];
    }
    return [
        ['title' => 'داشبورد',  'url' => url('weaver'),           'icon' => 'dashboard', 'order' => 1, 'match' => 'exact:weaver'],
        ['title' => 'سفارش‌ها', 'url' => url('weaver/orders'),    'icon' => 'shuttle',   'order' => 2, 'match' => 'weaver/orders'],
        ['title' => 'مواد من',  'url' => url('weaver/materials'), 'icon' => 'thread',    'order' => 3, 'match' => 'weaver/materials'],
        ['title' => 'دستمزد',   'url' => url('weaver/payments'),  'icon' => 'coin',      'order' => 4, 'match' => 'weaver/payments'],
        ['title' => 'پروفایل',  'url' => url('weaver/profile'),   'icon' => 'user',      'order' => 5, 'match' => 'weaver/profile'],
    ];
};
