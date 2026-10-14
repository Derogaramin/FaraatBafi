<?php
/**
 * تست واحد نظام کدگذاری FUCS — بخش خالص (بدون DB)
 * بخش شمارنده‌ها در tests/Feature/CodeGeneratorDbTest.php
 */

declare(strict_types=1);

namespace Tests\Unit;

use Core\CodeGenerator;
use PHPUnit\Framework\TestCase;

final class CodeGeneratorPatternTest extends TestCase
{
    public function test_product_model_code(): void
    {
        // مثال مستندات: HT-2LBSTD-CH125 (حولهٔ دولا، بی‌گره، دوخت استاندارد، چله ۱۲٫۵)
        $this->assertSame('HT-2LBSTD-CH125', CodeGenerator::productModelCode('HT', '2L', 'B', 'STD', 12.5));
        $this->assertSame('SH-1LG2N-CH85', CodeGenerator::productModelCode('SH', '1L', 'G', '2N', 8.5));
        $this->assertSame('FB-4LBNS-CH100', CodeGenerator::productModelCode('FB', '4L', 'B', 'NS', 10));
        $this->assertSame('DS-2LBCUST-CH90', CodeGenerator::productModelCode('DS', '2L', 'B', 'CUST', 9));
    }

    public function test_product_model_code_rejects_invalid_parts(): void
    {
        $this->expectException(\RuntimeException::class);
        CodeGenerator::productModelCode('HT', '5L', 'B', 'STD', 12.5); // پود نامعتبر
    }

    public function test_sku_composition(): void
    {
        $this->assertSame(
            'HT-2LBSTD-CH125-R01-P0003',
            CodeGenerator::sku('HT-2LBSTD-CH125', 'R01', 'P0003')
        );
    }

    public function test_validate_all_pattern_types(): void
    {
        $valid = [
            'weaver'       => 'W045',
            'member'       => 'M007',
            'staff'        => 'S001',
            'customer'     => 'C000215',
            'agency'       => 'A03',
            'product_model'=> 'HT-2LBSTD-CH125',
            'color'        => 'R01',
            'pattern'      => 'P0042',
            'sku'          => 'HT-2LBSTD-CH125-R01-P0003',
            'authenticity' => 'FB-1403-0521-W023',
            'work_order'   => 'WO-1403-0145',
            'quality'      => 'QC-1403-0023',
            'defect'       => 'DEF-CLR-01',
            'raw_material' => 'RM-COT-R01',
            'location'     => 'LOC-MAIN-A3-01',
            'box'          => 'BOX-140305-012',
            'order'        => 'ORD-1403-000125',
            'invoice'      => 'INV-1403-00230',
            'rma'          => 'RMA-1403-0007',
            'shipment'     => 'SHP-1403-0041',
            'coupon'       => 'CPN-NOWRUZ-1403',
            'meeting'      => 'MT-1403-04',
            'resolution'   => 'RES-1403-04-02',
            'vote'         => 'VOT-1403-001',
            'letter'       => 'IR-1403-0105',
            'ticket'       => 'TKT-1403-00210',
            'accounting'   => 'ACC-1403-05-003',
            'payment'      => 'PAY-W045-1403-02',
            'commission'   => 'COM-A03-1403-03',
            'receipt'      => 'RCP-1403-00215',
        ];
        foreach ($valid as $type => $code) {
            $this->assertTrue(CodeGenerator::validate($code, $type), "کد {$code} باید برای نوع {$type} معتبر باشد");
        }
    }

    public function test_validate_rejects_malformed(): void
    {
        $this->assertFalse(CodeGenerator::validate('W45', 'weaver'));          // طول کم
        $this->assertFalse(CodeGenerator::validate('X045', 'weaver'));         // پیشوند غلط
        $this->assertFalse(CodeGenerator::validate('FB-1403-521-W23', 'authenticity'));
        $this->assertFalse(CodeGenerator::validate('HT-2LXSTD-CH125', 'product_model')); // گرهٔ نامعتبر
        $this->assertFalse(CodeGenerator::validate('ORD-1403-12345', 'order'));          // سری کوتاه
    }

    public function test_detect_type(): void
    {
        $this->assertSame('authenticity', CodeGenerator::detectType('FB-1403-0521-W023'));
        $this->assertSame('sku', CodeGenerator::detectType('HT-2LBSTD-CH125-R01-P0003'));
        $this->assertSame('weaver', CodeGenerator::detectType('W045'));
        $this->assertSame('order', CodeGenerator::detectType('ORD-1403-000125'));
        $this->assertNull(CodeGenerator::detectType('GARBAGE-##'));
    }

    public function test_unknown_type_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        CodeGenerator::validate('X', 'nonexistent_type');
    }

    public function test_defect_and_coupon_codes(): void
    {
        $this->assertSame('DEF-CLR-07', CodeGenerator::defectCode('CLR', 7));
        $this->assertSame('CPN-YALDA-1405', CodeGenerator::couponCode('yalda', 1405));
    }
}
