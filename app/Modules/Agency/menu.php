<?php
/** منوی پنل نمایندگی */

declare(strict_types=1);

return static function (string $panel): array {
    if ($panel !== 'agency') {
        return [];
    }
    return [
        ['title' => 'داشبورد',     'url' => url('agency'),              'icon' => 'dashboard', 'order' => 1,  'match' => 'exact:agency'],
        ['title' => 'فروش حضوری',  'url' => url('agency/pos'),          'icon' => 'barcode',   'order' => 2,  'match' => 'agency/pos'],
        ['title' => 'کالای امانی', 'url' => url('agency/consignment'),  'icon' => 'warehouse', 'order' => 3,  'match' => 'agency/consignment'],
        ['title' => 'پورسانت‌ها',  'url' => url('agency/commissions'),  'icon' => 'coin',      'order' => 4,  'match' => 'agency/commissions'],
        ['title' => 'سفارش‌ها',    'url' => url('agency/orders'),       'icon' => 'cart',      'order' => 5,  'match' => 'agency/orders'],
    ];
};
