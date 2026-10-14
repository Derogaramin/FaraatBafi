<?php
/**
 * دانه‌گذار ۰۷ — داده‌های نمونه برای قابلیت‌های فاز ۰ نقشهٔ راه
 * (ممیزی تغییرات، صف رویدادها، محدودسازی ورود)
 * در برابر اجرای مجدد مقاوم است.
 */

declare(strict_types=1);

use Core\Database;
use Core\Events;

return static function (): void {
    /* ── نمونه‌های ممیزی تغییرات (field_audits) ── */
    $admin = Database::selectOne("SELECT id FROM users WHERE code = 'M001'");
    $adminId = $admin ? (int) $admin['id'] : null;

    if ((int) (Database::selectOne('SELECT COUNT(*) AS c FROM field_audits')['c'] ?? 0) === 0) {
        $variant = Database::selectOne('SELECT id, sku, price FROM product_variants ORDER BY id LIMIT 1');
        if ($variant) {
            $oldPrice = (int) $variant['price'];
            $newPrice = $oldPrice + 40_000;
            Database::insert(
                'INSERT INTO field_audits (user_id, entity, entity_id, entity_code, field, old_value, new_value, created_at)
                 VALUES (?, "variant", ?, ?, "price", ?, ?, DATE_SUB(NOW(), INTERVAL 6 DAY))',
                [$adminId, (int) $variant['id'], $variant['sku'], (string) $oldPrice, (string) $newPrice]
            );
            // بازگرداندن قیمت به حالت قبل (فقط ردیف ممیزی نمونه می‌ماند)
            Database::insert(
                'INSERT INTO field_audits (user_id, entity, entity_id, entity_code, field, old_value, new_value, created_at)
                 VALUES (?, "variant", ?, ?, "price", ?, ?, DATE_SUB(NOW(), INTERVAL 5 DAY))',
                [$adminId, (int) $variant['id'], $variant['sku'], (string) $newPrice, (string) $oldPrice]
            );
        }
        $weaver = Database::selectOne("SELECT id, code, wage_rate FROM weavers WHERE code = 'W002'");
        if ($weaver) {
            $old = (int) $weaver['wage_rate'];
            $new = $old + 20_000;
            Database::update('UPDATE weavers SET wage_rate = ? WHERE id = ?', [$new, $weaver['id']]);
            Database::insert(
                'INSERT INTO field_audits (user_id, entity, entity_id, entity_code, field, old_value, new_value, created_at)
                 VALUES (?, "weaver", ?, ?, "wage_rate", ?, ?, DATE_SUB(NOW(), INTERVAL 3 DAY))',
                [$adminId, (int) $weaver['id'], $weaver['code'], (string) $old, (string) $new]
            );
        }
        $agency = Database::selectOne("SELECT id, code, credit_limit FROM agencies WHERE code = 'A02'");
        if ($agency) {
            Database::insert(
                'INSERT INTO field_audits (user_id, entity, entity_id, entity_code, field, old_value, new_value, created_at)
                 VALUES (?, "agency", ?, ?, "credit_limit", ?, ?, DATE_SUB(NOW(), INTERVAL 1 DAY))',
                [$adminId, (int) $agency['id'], $agency['code'], (string) $agency['credit_limit'], '450000000']
            );
        }
        echo "  → " . fa((string) 4) . " ردیف نمونهٔ ممیزی تغییرات\n";
    }

    /* ── صف رویدادها: دو رویداد در انتظار پردازش ── */
    if ((int) (Database::selectOne('SELECT COUNT(*) AS c FROM event_queue')['c'] ?? 0) === 0) {
        Events::queue('notification.demo', ['message' => 'رویداد نمونه در صف — با php bin/faraat queue:work پردازش می‌شود']);
        Events::queue('notification.demo', ['message' => 'رویداد نمونهٔ دوم در صف']);
        echo "  → " . fa('2') . " رویداد نمونه در صف\n";
    }

    /* ── تاریخچهٔ تلاش‌های ورود (برای صفحهٔ «امنیت حساب») ── */
    if ((int) (Database::selectOne('SELECT COUNT(*) AS c FROM login_attempts')['c'] ?? 0) === 0) {
        $attempts = [
            ['m001', '127.0.0.1', 1, '-9 DAY'],
            ['m001', '127.0.0.1', 0, '-7 DAY'],
            ['m001', '127.0.0.1', 0, '-7 DAY'],
            ['m001', '127.0.0.1', 1, '-7 DAY'],
            ['w001', '127.0.0.1', 1, '-4 DAY'],
            ['a01', '127.0.0.1', 1, '-2 DAY'],
            ['s001', '127.0.0.1', 0, '-1 DAY'],
            ['s001', '127.0.0.1', 1, '-1 DAY'],
        ];
        foreach ($attempts as [$ident, $ip, $ok, $ago]) {
            Database::insert(
                'INSERT INTO login_attempts (identifier, ip, success, attempted_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ' . $ago . '))',
                [$ident, $ip, $ok]
            );
        }
        echo '  → ' . fa((string) count($attempts)) . " رکورد نمونهٔ تلاش ورود\n";
    }
};
