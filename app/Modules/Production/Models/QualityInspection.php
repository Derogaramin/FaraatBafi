<?php
/**
 * مدل QualityInspection — ماژول Production
 * جدول: quality_inspections
 */

declare(strict_types=1);

namespace App\Modules\Production\Models;

use Core\Model;

final class QualityInspection extends Model
{
    protected static string $table = 'quality_inspections';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'work_order_id',
        'inspector_id',
        'result',
        'defect_type_id',
        'score',
        'note',
        'inspected_at',
    ];
    protected static array $casts = [
        'score' => 'int',
    ];

    public function resultLabel(): string
    {
        return \Core\Statuses::label('qc', (string) ($this->attributes['result'] ?? ''));
    }
}
