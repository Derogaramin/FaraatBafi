<?php
/**
 * سرویس اعلان CRM — کمک‌های مشترک اطلاع‌رسانی
 * (جایگزین تابع سراسری faraat_admin_user_ids — نقد §۳٫۵: آلودگی namespace عمومی)
 */

declare(strict_types=1);

namespace App\Modules\CRM\Services;

use Core\Database;

final class Notify
{
    /**
     * شناسهٔ کاربران مدیر (اعضای اصلی M و پرسنل S) برای اعلان‌های سازمانی.
     *
     * @return int[]
     */
    public static function adminIds(int $limit = 5): array
    {
        $rows = Database::select(
            "SELECT id FROM users WHERE (code LIKE 'M%' OR code LIKE 'S%') AND status = 'active' ORDER BY id LIMIT ?",
            [$limit]
        );
        return array_map(static fn ($r) => (int) $r['id'], $rows);
    }

    /** شمارهٔ موبایل کاربر یک بافنده (برای پیامک) */
    public static function weaverPhone(int $weaverId): ?string
    {
        $row = Database::selectOne('SELECT phone FROM weavers WHERE id = ?', [$weaverId]);
        $phone = $row['phone'] ?? null;
        return $phone !== null && $phone !== '' ? (string) $phone : null;
    }
}
