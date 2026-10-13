<?php
namespace App\Models;
use Core\Model;

class ProductVariant extends Model
{
    protected static string $table = 'product_variants';
    protected static string $primaryKey = 'sku';

    public static function findBySku(string $sku): ?static
    {
        $stmt = static::db()->prepare(
            "SELECT v.*, m.type_code, m.weave, m.knot, m.stitch, m.chaleh, m.base_weight_grams, m.width_cm, m.base_price AS model_base_price,
                    c.name_fa AS color_name, c.hex AS color_hex,
                    p.name_fa AS pattern_name, p.image_url AS pattern_image
             FROM product_variants v
             JOIN product_models m ON v.model_code = m.model_code
             JOIN colors c ON v.color_code = c.color_code
             JOIN patterns p ON v.pattern_code = p.pattern_code
             WHERE v.sku = :sku AND v.is_active = 1"
        );
        $stmt->execute(['sku' => $sku]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, static::class);
        return $stmt->fetch() ?: null;
    }

    public static function activeList(): array
    {
        $stmt = static::db()->query(
            "SELECT v.*, m.type_code, m.weave, m.knot, m.stitch, m.chaleh, m.base_price AS model_base_price,
                    c.name_fa AS color_name, p.name_fa AS pattern_name
             FROM product_variants v
             JOIN product_models m ON v.model_code = m.model_code
             JOIN colors c ON v.color_code = c.color_code
             JOIN patterns p ON v.pattern_code = p.pattern_code
             WHERE v.is_active = 1
             ORDER BY v.created_at DESC"
        );
        return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
    }

    public function finalPrice(): float
    {
        return (float)($this->model_base_price ?? 0) + (float)($this->price_modifier ?? 0);
    }

    public function getChalehDisplay(): string
    {
        return number_format(($this->chaleh ?? 0) / 10, 1);
    }
}