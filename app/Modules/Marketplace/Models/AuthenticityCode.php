<?php
/**
 * مدل AuthenticityCode — ماژول Marketplace
 * جدول: authenticity_codes
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Models;

use Core\Model;

final class AuthenticityCode extends Model
{
    protected static string $table = 'authenticity_codes';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'variant_id',
        'work_order_id',
        'weaver_id',
        'qr_url',
        'status',
        'order_id',
        'sold_at',
    ];
    protected static array $casts = [];

    /** اطلاعات کامل صفحهٔ اصالت (بافنده، محصول، بافت) */
    public static function detail(string $code): ?array
    {
        return \Core\Database::selectOne(
            'SELECT ac.*, w.name AS weaver_name, w.code AS weaver_code, w.city AS weaver_city, w.specialty, w.rating,
                    pv.sku, pv.image, pm.name_fa AS product_name, pm.code AS model_code,
                    pm.weave, pm.knot, pm.stitch, pm.chelle, pm.size_cm,
                    c.name_fa AS color_name, c.hex AS color_hex, pt.name_fa AS type_name,
                    p.name_fa AS pattern_name,
                    wo.code AS wo_code, wo.delivered_at AS woven_at
             FROM authenticity_codes ac
             JOIN weavers w ON w.id = ac.weaver_id
             JOIN product_variants pv ON pv.id = ac.variant_id
             JOIN product_models pm ON pm.id = pv.model_id
             JOIN product_types pt ON pt.id = pm.type_id
             JOIN colors c ON c.id = pv.color_id
             JOIN patterns p ON p.id = pv.pattern_id
             LEFT JOIN work_orders wo ON wo.id = ac.work_order_id
             WHERE ac.code = ?',
            [$code]
        );
    }
}
