<?php
/**
 * مدل Material — ماژول Production
 * جدول: materials
 */

declare(strict_types=1);

namespace App\Modules\Production\Models;

use Core\Model;

final class Material extends Model
{
    protected static string $table = 'materials';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'name_fa',
        'category',
        'unit',
        'price_per_unit',
        'stock',
        'min_stock',
        'location_code',
        'is_active',
    ];
    protected static array $casts = [
        'price_per_unit' => 'int',
    ];

    public function unitLabel(): string
    {
        return match ($this->attributes['unit'] ?? '') {
            'kg' => 'کیلوگرم', 'gr' => 'گرم', 'm' => 'متر',
            'count' => 'عدد', 'l' => 'لیتر', default => '—',
        };
    }

    public function isLow(): bool
    {
        return (float) $this->attributes['stock'] <= (float) $this->attributes['min_stock'];
    }
}
