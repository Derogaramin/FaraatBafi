<?php
/**
 * کلاس Jalali — تبدیل و قالب‌بندی تاریخ شمسی (جلالی)
 *
 * الگوریتم: پیادهٔ استاندارد jalaali (مبتنی بر شکست‌های تقویم جلالی)
 * تمام تاریخ‌ها در پایگاه داده میلادی ذخیره و در نمایش شمسی می‌شوند.
 */

declare(strict_types=1);

namespace Core;

final class Jalali
{
    /** شکست‌های تقویم جلالی (الگوریتم مرجع jalaali) */
    private const BREAKS = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];

    public const MONTHS = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];

    public const WEEKDAYS = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

    public const WEEKDAYS_SHORT = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

    /** میلادی → جلالی : [سال, ماه, روز] */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        return self::d2j(self::g2d($gy, $gm, $gd));
    }

    /** جلالی → میلادی : [سال, ماه, روز] */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        return self::d2g(self::j2d($jy, $jm, $jd));
    }

    /**
     * قالب‌بندی زمان به شمسی.
     *
     * حروف پشتیبانی‌شده:
     *  Y سال چهاررقمی | y دو رقم | m ماه | d روز | H/i/s ساعت:دقیقه:ثانیه
     *  F نام ماه | l نام روز هفته | j روز بدون صفر | n ماه بدون صفر
     *  و شمارهٔ روز سال (z)
     */
    public static function format(string $format, ?int $timestamp = null, bool $persianDigits = true): string
    {
        $timestamp ??= time();
        [$jy, $jm, $jd] = self::toJalali((int) date('Y', $timestamp), (int) date('n', $timestamp), (int) date('j', $timestamp));

        $weekdayIndex = self::weekdayIndex($timestamp);
        $output = '';
        $len = strlen($format);
        for ($i = 0; $i < $len; $i++) {
            $c = $format[$i];
            $output .= match ($c) {
                'Y'     => (string) $jy,
                'y'     => substr((string) $jy, 2),
                'm'     => str_pad((string) $jm, 2, '0', STR_PAD_LEFT),
                'n'     => (string) $jm,
                'd'     => str_pad((string) $jd, 2, '0', STR_PAD_LEFT),
                'j'     => (string) $jd,
                'F'     => self::MONTHS[$jm],
                'M'     => mb_substr(self::MONTHS[$jm], 0, 3),
                'l'     => self::WEEKDAYS[$weekdayIndex],
                'D'     => self::WEEKDAYS_SHORT[$weekdayIndex],
                'w'     => (string) $weekdayIndex,
                'z'     => (string) self::dayOfYear($jy, $jm, $jd),
                'H', 'i', 's', 'G' => date($c, $timestamp),
                '\\'    => $format[++$i] ?? '',
                default => $c,
            };
        }
        return $persianDigits ? self::digits($output) : $output;
    }

    /** قالب‌بندی یک رشتهٔ تاریخ/زمان MySQL (Y-m-d H:i:s یا Y-m-d) */
    public static function formatSql(?string $sqlDatetime, string $format = 'Y/m/d', bool $withTime = false): string
    {
        if ($sqlDatetime === null || $sqlDatetime === '' || str_starts_with($sqlDatetime, '0000')) {
            return '—';
        }
        $ts = strtotime($sqlDatetime);
        if ($ts === false) {
            return '—';
        }
        if ($withTime) {
            $format .= ' — H:i';
        }
        return self::format($format, $ts);
    }

    /** «امروز» به شمسی */
    public static function today(string $format = 'Y/m/d'): string
    {
        return self::format($format);
    }

    /** زمان نسبی فارسی: «۳ روز پیش» */
    public static function relative(?string $sqlDatetime): string
    {
        if (!$sqlDatetime) {
            return '—';
        }
        $ts = strtotime($sqlDatetime);
        if ($ts === false) {
            return '—';
        }
        $diff = time() - $ts;
        if ($diff < 0) {
            return self::formatSql($sqlDatetime, 'Y/m/d', true);
        }
        if ($diff < 60) {
            return 'همین حالا';
        }
        if ($diff < 3600) {
            return self::digits((string) intdiv($diff, 60)) . ' دقیقه پیش';
        }
        if ($diff < 86400) {
            return self::digits((string) intdiv($diff, 3600)) . ' ساعت پیش';
        }
        if ($diff < 86400 * 7) {
            return self::digits((string) intdiv($diff, 86400)) . ' روز پیش';
        }
        if ($diff < 86400 * 30) {
            return self::digits((string) intdiv($diff, 86400 * 7)) . ' هفته پیش';
        }
        return self::formatSql($sqlDatetime, 'Y/m/d');
    }

    /** تبدیل رقم‌ها به فارسی */
    public static function digits(string|int|float $input): string
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str_replace($en, $fa, (string) $input);
    }

    /** تبدیل رقم‌های فارسی/عربی به انگلیسی */
    public static function toLatinDigits(string $input): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($fa, $en, $input);
    }

    /** سال شمسی جاری */
    public static function currentYear(): int
    {
        return self::toJalali((int) date('Y'), (int) date('n'), (int) date('j'))[0];
    }

    /** ماه شمسی جاری */
    public static function currentMonth(): int
    {
        return self::toJalali((int) date('Y'), (int) date('n'), (int) date('j'))[1];
    }

    /** تبدیل «سال/ماه/روز» شمسی به رشتهٔ تاریخ میلادی برای ذخیره در DB */
    public static function jalaliDateToSql(int $jy, int $jm, int $jd): string
    {
        [$gy, $gm, $gd] = self::toGregorian($jy, $jm, $jd);
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    /** شمارش معکوس فارسی تا یک موعد (برای پنل بافنده) */
    public static function countdown(?string $sqlDate): string
    {
        if (!$sqlDate) {
            return 'بدون موعد';
        }
        $ts = strtotime($sqlDate . ' 23:59:59');
        if ($ts === false) {
            return '—';
        }
        $days = (int) ceil(($ts - time()) / 86400);
        if ($days < 0) {
            return self::digits((string) abs($days)) . ' روز تأخیر';
        }
        if ($days === 0) {
            return 'امروز آخرین مهلت!';
        }
        return self::digits((string) $days) . ' روز مانده';
    }

    /* ================= الگوریتم مرجع jalaali ================= */

    private static function weekdayIndex(int $timestamp): int
    {
        // شنبه = ۰ (date('N'): دوشنبه=1 ... یکشنبه=7)
        return ((int) date('w', $timestamp) + 1) % 7;
    }

    private static function dayOfYear(int $jy, int $jm, int $jd): int
    {
        if ($jm <= 6) {
            return ($jm - 1) * 31 + $jd;
        }
        return (($jm - 7) * 30) + 186 + $jd;
    }

    /** محاسبهٔ پارامترهای سال جلالی */
    private static function jalCal(int $jy): array
    {
        $breaks = self::BREAKS;
        $bl = count($breaks);
        $gy = $jy + 621;
        $leapJ = -14;
        $jp = $breaks[0];
        $jump = 0;

        if ($jy < $jp || $jy >= $breaks[$bl - 1]) {
            throw new \InvalidArgumentException("سال جلالی نامعتبر: {$jy}");
        }

        $jm = 0;
        for ($i = 1; $i < $bl; $i++) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;
            if ($jy < $jm) {
                break;
            }
            $leapJ = $leapJ + intdiv($jump, 33) * 8 + intdiv($jump % 33, 4);
            $jp = $jm;
        }
        $n = $jy - $jp;

        $leapJ = $leapJ + intdiv($n, 33) * 8 + intdiv(($n % 33) + 3, 4);
        if (($jump % 33) === 4 && ($jump - $n) === 4) {
            $leapJ++;
        }

        $leapG = intdiv($gy, 4) - intdiv((intdiv($gy, 100) + 1) * 3, 4) - 150;
        $march = 20 + $leapJ - $leapG;

        if ($jump - $n < 6) {
            $n = $n - $jump + intdiv($jump + 4, 33) * 33;
        }
        $leap = (($n + 1) % 33 - 1) % 4;
        if ($leap === -1) {
            $leap = 4;
        }

        return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
    }

    /** روزهای ماه جلالی */
    public static function monthLength(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }
        if ($jm <= 11) {
            return 30;
        }
        return self::isLeap($jy) ? 30 : 29;
    }

    public static function isLeap(int $jy): bool
    {
        return self::jalCal($jy)['leap'] === 0;
    }

    /** جلالی → شمارهٔ روز ژولین */
    private static function j2d(int $jy, int $jm, int $jd): int
    {
        $r = self::jalCal($jy);
        return self::g2d($r['gy'], 3, $r['march']) + ($jm - 1) * 31 - intdiv($jm, 7) * ($jm - 7) + $jd - 1;
    }

    /** شمارهٔ روز ژولین → جلالی */
    private static function d2j(int $jdn): array
    {
        $gy = self::d2g($jdn)[0];
        $jy = $gy - 621;
        $r = self::jalCal($jy);
        $jdn1f = self::g2d($gy, 3, $r['march']);
        $k = $jdn - $jdn1f;
        if ($k >= 0) {
            if ($k <= 185) {
                $jm = 1 + intdiv($k, 31);
                $jd = ($k % 31) + 1;
                return [$jy, $jm, $jd];
            }
            $k -= 186;
        } else {
            $jy--;
            $k += 179;
            if ($r['leap'] === 1) {
                $k++;
            }
        }
        $jm = 7 + intdiv($k, 30);
        $jd = ($k % 30) + 1;
        return [$jy, $jm, $jd];
    }

    /** میلادی → شمارهٔ روز ژولین */
    private static function g2d(int $gy, int $gm, int $gd): int
    {
        $d = intdiv(($gy + intdiv($gm - 8, 6) + 100100) * 1461, 4)
            + intdiv(153 * (($gm + 9) % 12) + 2, 5)
            + $gd - 34840408;
        $d = $d - intdiv(intdiv($gy + 100100 + intdiv($gm - 8, 6), 100) * 3, 4) + 752;
        return $d;
    }

    /** شمارهٔ روز ژولین → میلادی */
    private static function d2g(int $jdn): array
    {
        $j = 4 * $jdn + 139361631;
        $j = $j + intdiv(intdiv(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = intdiv(($j % 1461), 4) * 5 + 308;
        $gd = intdiv($i % 153, 5) + 1;
        $gm = (intdiv($i, 153) % 12) + 1;
        $gy = intdiv($j, 1461) - 100100 + intdiv(8 - $gm, 6);
        return [$gy, $gm, $gd];
    }
}
