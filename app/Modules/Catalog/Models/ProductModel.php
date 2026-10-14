<?php
/**
 * مدل ProductModel — ماژول Catalog
 * جدول: product_models
 */

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Core\Model;

final class ProductModel extends Model
{
    protected static string $table = 'product_models';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'type_id',
        'weave',
        'knot',
        'stitch',
        'chelle',
        'name_fa',
        'base_price',
        'weight_gr',
        'size_cm',
        'description',
        'is_active',
    ];
    protected static array $casts = [
        'base_price' => 'int',
        'type_id' => 'int',
    ];

    /** اجزای فنی برای نمایش در ویو */
    public function specs(): array
    {
        return [
            'weave'  => \Core\CodeGenerator::WEAVES[$this->attributes['weave'] ?? ''] ?? '—',
            'knot'   => \Core\CodeGenerator::KNOTS[$this->attributes['knot'] ?? ''] ?? '—',
            'stitch' => \Core\CodeGenerator::STITCHES[$this->attributes['stitch'] ?? ''] ?? '—',
            'chelle' => \Core\Jalali::digits((string) $this->attributes['chelle']),
        ];
    }
}
