<?php
/**
 * enum WorkOrderStatus — وضعیت‌های سفارش تولید (منبع حقیقت)
 */

declare(strict_types=1);

namespace Core\Enums;

enum WorkOrderStatus: string
{
    case Draft             = 'draft';
    case Assigned          = 'assigned';
    case MaterialsReceived = 'materials_received';
    case Weaving           = 'weaving';
    case Delivered         = 'delivered';
    case QcPassed          = 'qc_passed';
    case QcRejected        = 'qc_rejected';
    case Completed         = 'completed';
    case Cancelled         = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft             => 'پیش‌نویس',
            self::Assigned          => 'تخصیص به بافنده',
            self::MaterialsReceived => 'مواد دریافت شد',
            self::Weaving           => 'در حال بافت',
            self::Delivered         => 'تحویل به انبار',
            self::QcPassed          => 'تأیید کیفیت',
            self::QcRejected        => 'رد کیفیت',
            self::Completed         => 'تکمیل و تسویه',
            self::Cancelled         => 'لغوشده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft             => 'muted',
            self::Assigned          => 'info',
            self::MaterialsReceived => 'secondary',
            self::Weaving           => 'warning',
            self::Delivered         => 'primary',
            self::QcPassed          => 'success',
            self::QcRejected        => 'danger',
            self::Completed         => 'success',
            self::Cancelled         => 'danger',
        };
    }

    /** @return self[] */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft             => [self::Assigned, self::Cancelled],
            self::Assigned          => [self::MaterialsReceived, self::Cancelled],
            self::MaterialsReceived => [self::Weaving, self::Cancelled],
            self::Weaving           => [self::Delivered],
            self::Delivered         => [self::QcPassed, self::QcRejected],
            self::QcPassed          => [self::Completed],
            self::QcRejected        => [self::Weaving, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /** ستون زمانی مربوط به هر گذار */
    public function timestampField(): ?string
    {
        return match ($this) {
            self::Assigned          => 'assigned_at',
            self::MaterialsReceived => 'materials_at',
            self::Weaving           => 'started_at',
            self::Delivered         => 'delivered_at',
            self::Completed         => 'completed_at',
            default                 => null,
        };
    }

    /** @return array<string, array{label:string,color:string}> */
    public static function map(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = ['label' => $case->label(), 'color' => $case->color()];
        }
        return $map;
    }

    public static function all(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }
}
