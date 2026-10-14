<?php
/**
 * مدل WorkOrder — ماژول Production
 * جدول: work_orders
 */

declare(strict_types=1);

namespace App\Modules\Production\Models;

use Core\Model;

final class WorkOrder extends Model
{
    protected static string $table = 'work_orders';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'variant_id',
        'order_id',
        'weaver_id',
        'quantity',
        'unit_wage',
        'status',
        'priority',
        'due_date',
        'assigned_at',
        'materials_at',
        'started_at',
        'delivered_at',
        'completed_at',
        'note',
    ];
    protected static array $casts = [
        'quantity' => 'int',
        'unit_wage' => 'int',
        'variant_id' => 'int',
    ];

    /**
     * گذارهای مجاز از وضعیت فعلی (ماشین حالت — منبع: WorkOrderStatus enum).
     *
     * @return string[]
     */
    public function allowedTransitions(): array
    {
        $current = \Core\Enums\WorkOrderStatus::tryFrom((string) ($this->attributes['status'] ?? 'draft'));
        if ($current === null) {
            return [];
        }
        return array_map(static fn ($c) => $c->value, $current->allowedTransitions());
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function statusLabel(): string
    {
        return \Core\Statuses::label('work_order', (string) ($this->attributes['status'] ?? ''));
    }

    public function statusColor(): string
    {
        return \Core\Statuses::color('work_order', (string) ($this->attributes['status'] ?? ''));
    }

    /** درصد پیشرفت برای نوار چندمرحله‌ای */
    public function progressPercent(): int
    {
        $steps = \Core\Statuses::WORK_ORDER_STEPS;
        $current = $this->attributes['status'] ?? 'draft';
        $index = array_search($current, $steps, true);
        if ($index === false) {
            return $current === 'qc_rejected' ? 60 : 0;
        }
        return (int) round((($index + 1) / count($steps)) * 100);
    }

    /** کل دستمزد این سفارش تولید */
    public function totalWage(): int
    {
        return (int) $this->attributes['quantity'] * (int) $this->attributes['unit_wage'];
    }

    /** تغییر وضعیت با ثبت timestamp مرتبط (ستون زمانی از enum گرفته می‌شود) */
    public function transitionTo(string $status): void
    {
        $target = \Core\Enums\WorkOrderStatus::tryFrom($status);
        if ($target !== null && ($field = $target->timestampField()) !== null) {
            $this->attributes[$field] = date('Y-m-d H:i:s');
        }
        $this->attributes['status'] = $status;
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        $this->save();
    }
}
