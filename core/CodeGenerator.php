<?php
/**
 * کلاس CodeGenerator — نظام کدگذاری یکپارچهٔ فرت (FUCS نسخهٔ ۳.۰)
 *
 * ساختار کلی:  PREFIX – HISTORICAL/TECHNICAL_PART – UNIQUE_ID
 *
 * تولید کدهای ترتیبی با مکانیزم GET_LOCK مای‌اس‌کیوال و جدول code_counters
 * انجام می‌شود تا در اجرای همزمان، کد تکراری تولید نشود.
 * تمام کدها پیش از بازگشت با Regex مرجع اعتبارسنجی می‌شوند.
 */

declare(strict_types=1);

namespace Core;

use RuntimeException;

final class CodeGenerator
{
    /* ============ الگوهای اعتبارسنجی (Regex مرجع FUCS v3) ============ */

    public const PATTERNS = [
        'weaver'        => '/^W\d{3}$/',
        'member'        => '/^M\d{3}$/',
        'staff'         => '/^S\d{3}$/',
        'customer'      => '/^C\d{6}$/',
        'agency'        => '/^A\d{2}$/',
        'product_type'  => '/^[A-Z]{2,3}$/',
        'product_model' => '/^[A-Z]{2,3}-(1L|2L|3L|4L)(B|G)(NS|STD|2N|R|CUST)-CH\d{2,3}$/',
        'color'         => '/^[A-Z]\d{2}$/',
        'pattern'       => '/^P\d{4}$/',
        'sku'           => '/^[A-Z]{2,3}-(1L|2L|3L|4L)(B|G)(NS|STD|2N|R|CUST)-CH\d{2,3}-[A-Z]\d{2}-P\d{4}$/',
        'authenticity'  => '/^FB-\d{4}-\d{4}-W\d{3}$/',
        'work_order'    => '/^WO-\d{4}-\d{4}$/',
        'quality'       => '/^QC-\d{4}-\d{4}$/',
        'defect'        => '/^DEF-[A-Z]{3}-\d{2}$/',
        'raw_material'  => '/^RM-[A-Z]{3}-[A-Z]\d{2}$/',
        'location'      => '/^LOC-[A-Z]+-[A-Z]\d-\d{2}$/',
        'box'           => '/^BOX-\d{6}-\d{3}$/',
        'order'         => '/^ORD-\d{4}-\d{6}$/',
        'invoice'       => '/^INV-\d{4}-\d{5}$/',
        'rma'           => '/^RMA-\d{4}-\d{4}$/',
        'shipment'      => '/^SHP-\d{4}-\d{4}$/',
        'coupon'        => '/^CPN-[A-Z0-9]+-\d{4}$/',
        'meeting'       => '/^MT-\d{4}-\d{2}$/',
        'resolution'    => '/^RES-\d{4}-\d{2}-\d{2}$/',
        'vote'          => '/^VOT-\d{4}-\d{3}$/',
        'letter'        => '/^IR-\d{4}-\d{4}$/',
        'ticket'        => '/^TKT-\d{4}-\d{5}$/',
        'accounting'    => '/^ACC-\d{4}-\d{2}-\d{3}$/',
        'payment'       => '/^PAY-W\d{3}-\d{4}-\d{2}$/',
        'commission'    => '/^COM-A\d{2}-\d{4}-\d{2}$/',
        'receipt'       => '/^RCP-\d{4}-\d{5}$/',
    ];

    /** کدهای پودگذاری */
    public const WEAVES = ['1L' => 'یک‌لا', '2L' => 'دولا', '3L' => 'سه‌لا', '4L' => 'چهارلا'];

    /** کدهای گره */
    public const KNOTS = ['B' => 'بی‌گره', 'G' => 'گره‌دار'];

    /** کدهای دوخت */
    public const STITCHES = [
        'NS'   => 'بدون دوخت',
        'STD'  => 'دوخت استاندارد',
        '2N'   => 'دو سوزنه',
        'R'    => 'دوخت ریلی',
        'CUST' => 'دوخت سفارشی',
    ];

    /* ============ موتور شمارنده (با قفل تراکنشی) ============ */

    /**
     * دریافت مقدار بعدی یک شمارنده با قفل GET_LOCK.
     *
     * @param string $scope    دامنهٔ شمارنده (مثلاً order.1403)
     * @param int    $start    مقدار شروع
     * @param int    $step     گام
     */
    public static function nextCounter(string $scope, int $start = 1, int $step = 1): int
    {
        return Database::transaction(function () use ($scope, $start, $step): int {
            $lockName = 'fucs_' . md5($scope);
            $lock = Database::selectOne('SELECT GET_LOCK(?, 10) AS ok', [$lockName]);
            if ((int) ($lock['ok'] ?? 0) !== 1) {
                throw new RuntimeException("قفل کدگذاری برای «{$scope}» به دست نیامد.");
            }
            try {
                $row = Database::selectOne('SELECT last_value FROM code_counters WHERE scope = ? FOR UPDATE', [$scope]);
                if ($row === null) {
                    $value = $start;
                    Database::insert(
                        'INSERT INTO code_counters (scope, last_value, updated_at) VALUES (?, ?, NOW())
                         ON DUPLICATE KEY UPDATE last_value = VALUES(last_value)',
                        [$scope, $value]
                    );
                } else {
                    $value = (int) $row['last_value'] + $step;
                    Database::update('UPDATE code_counters SET last_value = ?, updated_at = NOW() WHERE scope = ?', [$value, $scope]);
                }
                return $value;
            } finally {
                Database::selectOne('SELECT RELEASE_LOCK(?)', [$lockName]);
            }
        });
    }

    /** بازنشانی یک شمارنده (برای تست) */
    public static function resetCounter(string $scope): void
    {
        Database::delete('DELETE FROM code_counters WHERE scope = ?', [$scope]);
    }

    /* ============ کدهای انسانی ============ */

    public static function weaverCode(): string
    {
        return self::finalize(sprintf('W%03d', self::nextCounter('weaver', 1)), 'weaver');
    }

    public static function memberCode(): string
    {
        return self::finalize(sprintf('M%03d', self::nextCounter('member', 1)), 'member');
    }

    public static function staffCode(): string
    {
        return self::finalize(sprintf('S%03d', self::nextCounter('staff', 1)), 'staff');
    }

    public static function customerCode(): string
    {
        return self::finalize(sprintf('C%06d', self::nextCounter('customer', 1)), 'customer');
    }

    public static function agencyCode(): string
    {
        return self::finalize(sprintf('A%02d', self::nextCounter('agency', 1)), 'agency');
    }

    /* ============ کدهای تاریخی (سالانه) ============ */

    public static function orderCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('ORD-%d-%06d', $year, self::nextCounter("order.{$year}", 1)), 'order');
    }

    public static function workOrderCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('WO-%d-%04d', $year, self::nextCounter("wo.{$year}", 1)), 'work_order');
    }

    public static function qualityCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('QC-%d-%04d', $year, self::nextCounter("qc.{$year}", 1)), 'quality');
    }

    public static function invoiceCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('INV-%d-%05d', $year, self::nextCounter("invoice.{$year}", 1)), 'invoice');
    }

    public static function receiptCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('RCP-%d-%05d', $year, self::nextCounter("receipt.{$year}", 1)), 'receipt');
    }

    public static function rmaCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('RMA-%d-%04d', $year, self::nextCounter("rma.{$year}", 1)), 'rma');
    }

    public static function shipmentCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('SHP-%d-%04d', $year, self::nextCounter("shipment.{$year}", 1)), 'shipment');
    }

    public static function ticketCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('TKT-%d-%05d', $year, self::nextCounter("ticket.{$year}", 1)), 'ticket');
    }

    public static function letterCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('IR-%d-%04d', $year, self::nextCounter("letter.{$year}", 1)), 'letter');
    }

    public static function meetingCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('MT-%d-%02d', $year, self::nextCounter("meeting.{$year}", 1)), 'meeting');
    }

    public static function voteCode(?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        return self::finalize(sprintf('VOT-%d-%03d', $year, self::nextCounter("vote.{$year}", 1)), 'vote');
    }

    public static function accountingCode(?int $year = null, ?int $month = null): string
    {
        $year ??= Jalali::currentYear();
        $month ??= Jalali::currentMonth();
        return self::finalize(sprintf('ACC-%d-%02d-%03d', $year, $month, self::nextCounter("acc.{$year}.{$month}", 1)), 'accounting');
    }

    public static function boxCode(?int $year = null, ?int $month = null): string
    {
        $year ??= Jalali::currentYear();
        $month ??= Jalali::currentMonth();
        return self::finalize(sprintf('BOX-%d%02d-%03d', $year, $month, self::nextCounter("box.{$year}.{$month}", 1)), 'box');
    }

    /** فیش دستمزد بافنده: PAY-W045-1403-02 */
    public static function paymentCode(string $weaverCode, ?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        $scope = "payment.{$weaverCode}.{$year}";
        return self::finalize(sprintf('PAY-%s-%d-%02d', $weaverCode, $year, self::nextCounter($scope, 1)), 'payment');
    }

    /** پورسانت نمایندگی: COM-A03-1403-03 */
    public static function commissionCode(string $agencyCode, ?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        $scope = "commission.{$agencyCode}.{$year}";
        return self::finalize(sprintf('COM-%s-%d-%02d', $agencyCode, $year, self::nextCounter($scope, 1)), 'commission');
    }

    /* ============ کدهای فنی محصول ============ */

    /**
     * کد مدل فنی:  [TYPE]-[WEAVE][KNOT][STITCH]-CH[XX]
     * مثال: HT-2LBSTD-CH125 → حوله دولا، بی‌گره، دوخت استاندارد، چله ۱۲٫۵
     *
     * @param float $chelle تعداد چله (مثلاً 12.5)
     */
    public static function productModelCode(string $typeCode, string $weave, string $knot, string $stitch, float $chelle): string
    {
        $typeCode = strtoupper(trim($typeCode));
        $weave = strtoupper(trim($weave));
        $knot = strtoupper(trim($knot));
        $stitch = strtoupper(trim($stitch));

        if (!isset(self::WEAVES[$weave])) {
            throw new RuntimeException("کد پودگذاری نامعتبر: {$weave}");
        }
        if (!isset(self::KNOTS[$knot])) {
            throw new RuntimeException("کد گره نامعتبر: {$knot}");
        }
        if (!isset(self::STITCHES[$stitch])) {
            throw new RuntimeException("کد دوخت نامعتبر: {$stitch}");
        }
        $ch = (int) round($chelle * 10);
        if ($ch < 1) {
            throw new RuntimeException('چله باید عددی مثبت باشد.');
        }
        return self::finalize(sprintf('%s-%s%s%s-CH%d', $typeCode, $weave, $knot, $stitch, $ch), 'product_model');
    }

    /** کد SKU نهایی: [MODEL_CODE]-[COLOR_CODE]-[PATTERN_CODE] */
    public static function sku(string $modelCode, string $colorCode, string $patternCode): string
    {
        return self::finalize(
            sprintf('%s-%s-%s', strtoupper(trim($modelCode)), strtoupper(trim($colorCode)), strtoupper(trim($patternCode))),
            'sku'
        );
    }

    /** کد رنگ بعدی در یک خانواده (مثلاً R → R07) */
    public static function nextColorCode(string $familyLetter): string
    {
        $familyLetter = strtoupper(trim($familyLetter))[0] ?? 'R';
        $row = Database::selectOne(
            "SELECT MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)) AS mx FROM colors WHERE code LIKE ?",
            [$familyLetter . '%']
        );
        $next = ((int) ($row['mx'] ?? 0)) + 1;
        return self::finalize(sprintf('%s%02d', $familyLetter, $next), 'color');
    }

    /** کد طرح بعدی: P0043 */
    public static function nextPatternCode(): string
    {
        return self::finalize(sprintf('P%04d', self::nextCounter('pattern', 1)), 'pattern');
    }

    /**
     * کد اصالت مشتری‌محور: FB-[YEAR]-[SERIES]-[WEAVER]
     * مثال: FB-1403-0521-W023 — به QR حاوی آدرس صفحهٔ اصالت تبدیل می‌شود.
     */
    public static function authenticityCode(string $weaverCode, ?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        $series = self::nextCounter("authenticity.{$year}", 1);
        return self::finalize(sprintf('FB-%d-%04d-%s', $year, $series, strtoupper($weaverCode)), 'authenticity');
    }

    /** کد کوپن: CPN-NOWRUZ-1403 */
    public static function couponCode(string $campaign, ?int $year = null): string
    {
        $year ??= Jalali::currentYear();
        $campaign = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $campaign) ?? '');
        return self::finalize("CPN-{$campaign}-{$year}", 'coupon');
    }

    /** کد نوع عیب: DEF-CLR-01 */
    public static function defectCode(string $categoryCode, int $sequence): string
    {
        $categoryCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $categoryCode) ?? '', 0, 3));
        return self::finalize(sprintf('DEF-%s-%02d', $categoryCode, $sequence), 'defect');
    }

    /* ============ اعتبارسنجی ============ */

    /** اعتبارسنجی یک کد بر اساس نوع آن */
    public static function validate(string $code, string $type): bool
    {
        $pattern = self::PATTERNS[$type] ?? null;
        if ($pattern === null) {
            throw new RuntimeException("نوع کد ناشناخته: {$type}");
        }
        return (bool) preg_match($pattern, $code);
    }

    /** تشخیص نوع کد از روی ساختار آن */
    public static function detectType(string $code): ?string
    {
        foreach (self::PATTERNS as $type => $pattern) {
            if (preg_match($pattern, $code)) {
                return $type;
            }
        }
        return null;
    }

    private static function finalize(string $code, string $type): string
    {
        if (!self::validate($code, $type)) {
            throw new RuntimeException("کد تولیدشده با الگوی مرجع «{$type}» مطابقت ندارد: {$code}");
        }
        return $code;
    }
}
