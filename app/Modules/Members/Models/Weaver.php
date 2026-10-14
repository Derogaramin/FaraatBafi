<?php
/**
 * مدل Weaver — ماژول Members
 * جدول: weavers
 */

declare(strict_types=1);

namespace App\Modules\Members\Models;

use Core\Model;

final class Weaver extends Model
{
    protected static string $table = 'weavers';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'user_id',
        'code',
        'name',
        'phone',
        'national_id',
        'city',
        'village',
        'address',
        'specialty',
        'skill_level',
        'loom_count',
        'wage_rate',
        'balance',
        'rating',
        'status',
        'joined_at',
        'note',
    ];
    protected static array $casts = [
        'wage_rate' => 'int',
        'balance' => 'int',
        'loom_count' => 'int',
    ];

    public function skillLabel(): string
    {
        return \Core\Statuses::SKILL[$this->attributes['skill_level'] ?? 'mid'] ?? '—';
    }

    /** سفارش‌های تولید فعال این بافنده */
    public function activeWorkOrders(): array
    {
        return \Core\Database::select(
            'SELECT wo.*, pv.sku, pv.image, pm.name_fa AS product_name
             FROM work_orders wo
             JOIN product_variants pv ON pv.id = wo.variant_id
             JOIN product_models pm ON pm.id = pv.model_id
             WHERE wo.weaver_id = ? AND wo.status IN ("assigned","materials_received","weaving")
             ORDER BY wo.due_date ASC, wo.priority DESC',
            [$this->id()]
        );
    }
}
