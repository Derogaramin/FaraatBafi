<?php
/**
 * مدل ProductVariant — ماژول Catalog
 * جدول: product_variants
 */

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Core\Model;

final class ProductVariant extends Model
{
    protected static string $table = 'product_variants';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'sku',
        'model_id',
        'color_id',
        'pattern_id',
        'price',
        'cost',
        'stock',
        'reserved',
        'image',
        'is_featured',
        'is_active',
    ];
    protected static array $casts = [
        'price' => 'int',
        'cost' => 'int',
        'stock' => 'int',
        'reserved' => 'int',
        'model_id' => 'int',
        'color_id' => 'int',
        'pattern_id' => 'int',
    ];

    /** بارگذاری اطلاعات کامل ویو (مدل، رنگ، طرح، نوع) در یک پرس‌وجو */
    public static function detailed(int $id): ?array
    {
        return \Core\Database::selectOne(
            'SELECT pv.*, pm.code AS model_code, pm.name_fa AS model_name, pm.base_price, pm.weave, pm.knot, pm.stitch,
                    pm.chelle, pm.weight_gr, pm.size_cm, pm.description AS model_description,
                    pt.name_fa AS type_name, pt.code AS type_code,
                    c.name_fa AS color_name, c.hex AS color_hex, c.code AS color_code,
                    p.name_fa AS pattern_name, p.code AS pattern_code, p.motif, p.image AS pattern_image
             FROM product_variants pv
             JOIN product_models pm ON pm.id = pv.model_id
             JOIN product_types pt ON pt.id = pm.type_id
             JOIN colors c ON c.id = pv.color_id
             JOIN patterns p ON p.id = pv.pattern_id
             WHERE pv.id = ?',
            [$id]
        );
    }

    public static function detailedBySku(string $sku): ?array
    {
        $row = self::query()->where('sku', $sku)->first();
        return $row ? self::detailed((int) $row['id']) : null;
    }

    /** موجودی قابل فروش */
    public function available(): int
    {
        return max(0, (int) $this->attributes['stock'] - (int) ($this->attributes['reserved'] ?? 0));
    }
}
