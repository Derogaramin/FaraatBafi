<?php
/** منوی ماژول Blog برای پنل مدیر */

declare(strict_types=1);

return static function (string $panel): array {
    if ($panel !== 'admin') {
        return [];
    }
    return [
        ['title' => 'وبلاگ و محتوا', 'url' => url('admin/blog'), 'icon' => 'blog', 'order' => 60, 'permission' => 'blog.view', 'match' => 'admin/blog'],
    ];
};
