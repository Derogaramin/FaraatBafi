<?php
namespace App\Models;
use Core\Model;

class WorkOrder extends Model
{
    protected static string $table = 'work_orders';

    public static function findByWeaver(int $weaverId): array
    {
        $stmt = static::db()->prepare(
            "SELECT wo.*, pv.sku, pm.type_code, c.name_fa AS color_name, p.name_fa AS pattern_name
             FROM work_orders wo
             JOIN product_variants pv ON wo.sku = pv.sku
             JOIN product_models pm ON pv.model_code = pm.model_code
             JOIN colors c ON pv.color_code = c.color_code
             JOIN patterns p ON pv.pattern_code = p.pattern_code
             WHERE wo.weaver_id = :wid
             ORDER BY wo.created_at DESC"
        );
        $stmt->execute(['wid' => $weaverId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
    }
}