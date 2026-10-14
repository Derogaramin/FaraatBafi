<?php
/**
 * کلاس Statuses — نمای (Facade) برچسب‌ها و رنگ‌های وضعیت‌ها
 *
 * منبع حقیقت وضعیت‌های سفارش و تولید، enumهای Core\Enums هستند
 * (تصمیم ADR-003)؛ این کلاس برای سازگاری ویوها و سایر گروه‌های وضعیت
 * (کیفیت، پرداخت، تیکت و…) نگه داشته شده است.
 */

declare(strict_types=1);

namespace Core;

use Core\Enums\OrderStatus;
use Core\Enums\WorkOrderStatus;

final class Statuses
{
    /** مراحل نوار پیشرفت پنل بافنده */
    public const WORK_ORDER_STEPS = ['assigned', 'materials_received', 'weaving', 'delivered', 'qc_passed', 'completed'];

    /** نتیجهٔ بازرسی کیفیت */
    public const QC = [
        'passed'      => ['label' => 'قبول',      'color' => 'success'],
        'rejected'    => ['label' => 'رد',        'color' => 'danger'],
        'conditional' => ['label' => 'قبول مشروط', 'color' => 'warning'],
    ];

    /** وضعیت پرداخت دستمزد */
    public const PAYMENT = [
        'pending'   => ['label' => 'در انتظار پرداخت', 'color' => 'warning'],
        'paid'      => ['label' => 'پرداخت‌شده',       'color' => 'success'],
        'cancelled' => ['label' => 'لغوشده',           'color' => 'danger'],
    ];

    /** وضعیت پورسانت نمایندگی */
    public const COMMISSION = [
        'pending'  => ['label' => 'در انتظار بررسی', 'color' => 'warning'],
        'approved' => ['label' => 'تأییدشده',        'color' => 'info'],
        'paid'     => ['label' => 'پرداخت‌شده',      'color' => 'success'],
        'rejected' => ['label' => 'ردشده',           'color' => 'danger'],
    ];

    /** وضعیت تیکت */
    public const TICKET = [
        'open'     => ['label' => 'باز',        'color' => 'warning'],
        'answered' => ['label' => 'پاسخ‌داده', 'color' => 'info'],
        'closed'   => ['label' => 'بسته',       'color' => 'muted'],
    ];

    /** وضعیت محموله */
    public const SHIPMENT = [
        'preparing' => ['label' => 'آماده‌سازی',  'color' => 'muted'],
        'sent'      => ['label' => 'ارسال‌شده',   'color' => 'info'],
        'in_transit'=> ['label' => 'در مسیر',     'color' => 'secondary'],
        'delivered' => ['label' => 'تحویل‌شده',   'color' => 'success'],
        'failed'    => ['label' => 'ناموفق',      'color' => 'danger'],
    ];

    /** وضعیت کد اصالت */
    public const AUTHENTICITY = [
        'reserved' => ['label' => 'در انبار (فروش‌نرفته)', 'color' => 'muted'],
        'sold'     => ['label' => 'فروش‌رفته و اصیل',     'color' => 'success'],
        'returned' => ['label' => 'مرجوع‌شده',            'color' => 'warning'],
        'void'     => ['label' => 'باطل‌شده',             'color' => 'danger'],
    ];

    /** سطح مهارت بافنده */
    public const SKILL = [
        'apprentice' => 'کارآموز',
        'junior'     => 'تازه‌کار',
        'mid'        => 'متوسط',
        'senior'     => 'ارشد',
        'master'     => 'استادکار',
    ];

    /** اولویت */
    public const PRIORITY = [
        'low'    => ['label' => 'کم',     'color' => 'muted'],
        'normal' => ['label' => 'عادی',   'color' => 'info'],
        'high'   => ['label' => 'زیاد',   'color' => 'warning'],
        'urgent' => ['label' => 'فوری',   'color' => 'danger'],
    ];

    /** کانال فروش */
    public const CHANNEL = [
        'web'    => 'بازارگاه آنلاین',
        'agency' => 'نمایندگی',
        'phone'  => 'تلفنی',
        'admin'  => 'ثبت داخلی',
    ];

    /** @return array<string, array{label:string,color:string}> */
    public static function map(string $group): array
    {
        return match ($group) {
            'order'         => OrderStatus::map(),
            'work_order'    => WorkOrderStatus::map(),
            'qc'            => self::QC,
            'payment'       => self::PAYMENT,
            'commission'    => self::COMMISSION,
            'ticket'        => self::TICKET,
            'shipment'      => self::SHIPMENT,
            'authenticity'  => self::AUTHENTICITY,
            'priority'      => self::PRIORITY,
            default         => [],
        };
    }

    /** برچسب فارسی یک وضعیت */
    public static function label(string $group, string $status): string
    {
        return self::map($group)[$status]['label'] ?? $status;
    }

    public static function color(string $group, string $status): string
    {
        return self::map($group)[$status]['color'] ?? 'muted';
    }
}
