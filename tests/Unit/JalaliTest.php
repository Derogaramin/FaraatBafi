<?php
/**
 * تست واحد تقویم جلالی — تبدیل‌های مرجع، قالب‌بندی و رقم فارسی
 */

declare(strict_types=1);

namespace Tests\Unit;

use Core\Jalali;
use PHPUnit\Framework\TestCase;

final class JalaliTest extends TestCase
{
    /** داده‌های مرجع تبدیل میلادی ↔ شمسی */
    public static function conversionProvider(): array
    {
        return [
            'نوروز ۱۴۰۳'      => [[2024, 3, 20], [1403, 1, 1]],
            'آخرین روز ۱۴۰۳'  => [[2025, 3, 20], [1403, 12, 30]],  // ۱۴۰۳ کبیسه بود
            'نوروز ۱۴۰۴'      => [[2025, 3, 21], [1404, 1, 1]],
            'مهر ۱۴۰۵'        => [[2026, 10, 14], [1405, 7, 22]],
            'آغاز ۲۰۰۰'       => [[2000, 1, 1], [1378, 10, 11]],
            'روز کبیسهٔ میلادی' => [[2024, 2, 29], [1402, 12, 10]],
        ];
    }

    /** @dataProvider conversionProvider */
    public function test_gregorian_to_jalali(array $g, array $j): void
    {
        $this->assertSame($j, Jalali::toJalali($g[0], $g[1], $g[2]));
    }

    /** @dataProvider conversionProvider */
    public function test_jalali_to_gregorian(array $g, array $j): void
    {
        $this->assertSame($g, Jalali::toGregorian($j[0], $j[1], $j[2]));
    }

    public function test_roundtrip_30_years(): void
    {
        // رفت‌وبرگشت ۳۰ سال — هر روز باید به خودش برگردد
        $ts = strtotime('2010-01-01');
        for ($i = 0; $i < 366 * 30; $i += 7) {
            $gy = (int) date('Y', $ts); $gm = (int) date('n', $ts); $gd = (int) date('j', $ts);
            [$jy, $jm, $jd] = Jalali::toJalali($gy, $gm, $gd);
            $this->assertSame([$gy, $gm, $gd], Jalali::toGregorian($jy, $jm, $jd));
            $ts += 7 * 86400;
        }
    }

    public function test_leap_years(): void
    {
        $this->assertTrue(Jalali::isLeap(1399));
        $this->assertTrue(Jalali::isLeap(1403));
        $this->assertFalse(Jalali::isLeap(1400));
        $this->assertFalse(Jalali::isLeap(1404));
        $this->assertSame(30, Jalali::monthLength(1403, 12)); // اسفند کبیسه
        $this->assertSame(29, Jalali::monthLength(1404, 12));
    }

    public function test_month_lengths(): void
    {
        for ($m = 1; $m <= 6; $m++) {
            $this->assertSame(31, Jalali::monthLength(1404, $m));
        }
        for ($m = 7; $m <= 11; $m++) {
            $this->assertSame(30, Jalali::monthLength(1404, $m));
        }
    }

    public function test_format(): void
    {
        $ts = mktime(13, 45, 0, 10, 14, 2026); // ۱۴۰۵/۰۷/۲۲
        $this->assertSame('۱۴۰۵/۰۷/۲۲', Jalali::format('Y/m/d', $ts));
        $this->assertSame('۱۴۰۵', Jalali::format('Y', $ts));
        $this->assertSame('مهر', Jalali::format('F', $ts));
        $this->assertStringContainsString('۱۳:۴۵', Jalali::format('H:i', $ts));
        // بدون رقم فارسی
        $this->assertSame('1405-07-22', Jalali::format('Y-m-d', $ts, false));
    }

    public function test_format_sql_and_relative(): void
    {
        $this->assertSame('—', Jalali::formatSql(null));
        $this->assertSame('—', Jalali::formatSql('0000-00-00 00:00:00'));
        $this->assertMatchesRegularExpression('/^\d{4}\/\d{2}\/\d{2}$/u', Jalali::formatSql('2026-10-14'));
        $this->assertSame('همین حالا', Jalali::relative(date('Y-m-d H:i:s')));
        $this->assertStringContainsString('دقیقه پیش', Jalali::relative(date('Y-m-d H:i:s', time() - 300)));
        $this->assertStringContainsString('روز پیش', Jalali::relative(date('Y-m-d H:i:s', time() - 3 * 86400)));
    }

    public function test_digits(): void
    {
        $this->assertSame('۰۱۲۳۴۵۶۷۸۹', Jalali::digits('0123456789'));
        $this->assertSame('0123456789', Jalali::toLatinDigits('۰۱۲۳۴۵۶۷۸۹'));
        $this->assertSame('0123456789', Jalali::toLatinDigits('٠١٢٣٤٥٦٧٨٩')); // عربی
    }

    public function test_jalali_date_to_sql(): void
    {
        $this->assertSame('2026-10-14', Jalali::jalaliDateToSql(1405, 7, 22));
        $this->assertSame('2024-03-20', Jalali::jalaliDateToSql(1403, 1, 1));
    }

    public function test_countdown(): void
    {
        $this->assertSame('بدون موعد', Jalali::countdown(null));
        $this->assertStringContainsString('روز مانده', Jalali::countdown(date('Y-m-d', strtotime('+5 days'))));
        $this->assertStringContainsString('تأخیر', Jalali::countdown(date('Y-m-d', strtotime('-3 days'))));
    }

    public function test_current_year_is_sane(): void
    {
        $year = Jalali::currentYear();
        $this->assertGreaterThanOrEqual(1400, $year);
        $this->assertLessThanOrEqual(1500, $year);
    }
}
