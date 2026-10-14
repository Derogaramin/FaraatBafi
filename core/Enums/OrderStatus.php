<?php
/**
 * enum OrderStatus — وضعیت‌های سفارش فروش (منبع حقیقت)
 *
 * جایگزین رشته‌های پراکنده (نقد §۳٫۳ و §۱۱-۶):
 * ماشین حالت، برچسب فارسی و رنگ نشان‌ها همه از اینجا مشتق می‌شوند.
 */

declare(strict_types=1);

namespace Core\Enums;

enum OrderStatus: string
{
    case Pending      = 'pending';
    case Confirmed    = 'confirmed';
    case InProduction = 'in_production';
    case Ready        = 'ready';
    case Shipped      = 'shipped';
    case Delivered    = 'delivered';
    case Cancelled    = 'cancelled';
    case Returned     = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending      => 'در انتظار تأیید',
            self::Confirmed    => 'تأییدشده',
            self::InProduction => 'در حال بافت',
            self::Ready        => 'آمادهٔ ارسال',
            self::Shipped      => 'ارسال‌شده',
            self::Delivered    => 'تحویل‌شده',
            self::Cancelled    => 'لغوشده',
            self::Returned     => 'مرجوع‌شده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending      => 'warning',
            self::Confirmed    => 'info',
            self::InProduction => 'primary',
            self::Ready        => 'secondary',
            self::Shipped      => 'info',
            self::Delivered    => 'success',
            self::Cancelled, self::Returned => 'danger',
        };
    }

    /**
     * گذارهای مجاز ماشین حالت سفارش (نقد §۳٫۵ — جلوگیری از drift).
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending      => [self::Confirmed, self::Cancelled],
            self::Confirmed    => [self::InProduction, self::Ready, self::Cancelled],
            self::InProduction => [self::Ready, self::Cancelled],
            self::Ready        => [self::Shipped, self::Cancelled],
            self::Shipped      => [self::Delivered, self::Returned],
            self::Delivered    => [self::Returned],
            self::Cancelled, self::Returned => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /** آیا لغو/مرجوعی موجودی انبار را برمی‌گرداند؟ */
    public function restoresStockOnLeave(): bool
    {
        return false;
    }

    /** @return array<string, array{label:string,color:string}> برای ویوها */
    public static function map(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = ['label' => $case->label(), 'color' => $case->color()];
        }
        return $map;
    }

    public static function values(): array
    {
        return array_column(self::map(), 'label');
    }

    /** همهٔ مقدارهای معتبر (برای اعتبارسنجی) */
    public static function all(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }

    public static function tryFromAny(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }
}
