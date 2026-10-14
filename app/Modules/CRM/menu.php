<?php
/** منوی ماژول CRM برای پنل مدیر */

declare(strict_types=1);

return static function (string $panel): array {
    if ($panel !== 'admin') {
        return [];
    }
    return [
        ['title' => 'تیکت‌های پشتیبانی', 'url' => url('admin/tickets'), 'icon' => 'ticket', 'order' => 70, 'permission' => 'tickets.view', 'match' => 'admin/tickets'],
    ];
};
