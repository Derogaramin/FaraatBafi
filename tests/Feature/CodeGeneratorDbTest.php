<?php
/**
 * تست ویژگی — CodeGenerator با پایگاه داده (شمارنده + GET_LOCK + یکتایی)
 */

declare(strict_types=1);

namespace Tests\Feature;

use Core\CodeGenerator;
use Core\Database;
use Core\Jalali;
use PHPUnit\Framework\TestCase;

final class CodeGeneratorDbTest extends TestCase
{
    public function test_sequential_counter_increments(): void
    {
        $scope = 'test.counter.' . uniqid();
        $this->assertSame(1, CodeGenerator::nextCounter($scope));
        $this->assertSame(2, CodeGenerator::nextCounter($scope));
        $this->assertSame(3, CodeGenerator::nextCounter($scope));
        CodeGenerator::resetCounter($scope);
        $this->assertSame(1, CodeGenerator::nextCounter($scope)); // پس از ریست
        CodeGenerator::resetCounter($scope);
    }

    public function test_counter_is_isolated_per_scope(): void
    {
        $a = 'test.a.' . uniqid();
        $b = 'test.b.' . uniqid();
        CodeGenerator::nextCounter($a);
        CodeGenerator::nextCounter($a);
        $this->assertSame(1, CodeGenerator::nextCounter($b));
        $this->assertSame(3, CodeGenerator::nextCounter($a));
        CodeGenerator::resetCounter($a);
        CodeGenerator::resetCounter($b);
    }

    public function test_weaver_codes_are_unique_and_valid(): void
    {
        $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $code = CodeGenerator::weaverCode();
            $this->assertMatchesRegularExpression(CodeGenerator::PATTERNS['weaver'], $code);
            $this->assertNotContains($code, $codes, 'کد بافنده باید یکتا باشد');
            $codes[] = $code;
        }
    }

    public function test_order_code_uses_jalali_year_scope(): void
    {
        $code = CodeGenerator::orderCode(1405);
        $this->assertStringStartsWith('ORD-1405-', $code);
        $this->assertTrue(CodeGenerator::validate($code, 'order'));

        // دامنهٔ سال متفاوت → شمارندهٔ جدا
        $other = CodeGenerator::orderCode(1399);
        $this->assertStringStartsWith('ORD-1399-000001', $other);
    }

    public function test_authenticity_code(): void
    {
        $code = CodeGenerator::authenticityCode('W001', 1405);
        $this->assertMatchesRegularExpression('/^FB-1405-\d{4}-W001$/', $code);
    }

    public function test_payment_and_commission_codes(): void
    {
        $pay = CodeGenerator::paymentCode('W045', 1403);
        $this->assertStringStartsWith('PAY-W045-1403-', $pay);
        $this->assertTrue(CodeGenerator::validate($pay, 'payment'));

        $com = CodeGenerator::commissionCode('A03', 1403);
        $this->assertTrue(CodeGenerator::validate($com, 'commission'));
    }

    public function test_color_code_auto_increment_per_family(): void
    {
        $before = Database::selectOne("SELECT MAX(code) AS mx FROM colors WHERE code LIKE 'R%'");
        $code = CodeGenerator::nextColorCode('R');
        $this->assertMatchesRegularExpression('/^R\d{2}$/', $code);
        $this->assertGreaterThan((string) $before['mx'], $code);
    }

    public function test_pattern_code_sequence(): void
    {
        $c1 = CodeGenerator::nextPatternCode();
        $c2 = CodeGenerator::nextPatternCode();
        $this->assertTrue(CodeGenerator::validate($c1, 'pattern'));
        $this->assertNotSame($c1, $c2);
    }

    public function test_regex_constraints_enforced_in_database(): void
    {
        // پایگاه داده هم باید کد نامعتبر را رد کند (CHECK + REGEXP)
        $this->expectException(\PDOException::class);
        Database::insert(
            'INSERT INTO weavers (code, name, status, created_at) VALUES (?, ?, "active", NOW())',
            ['BAD-CODE', 'تست']
        );
    }

    public function test_current_jalali_year_matches_system(): void
    {
        $year = Jalali::currentYear();
        $code = CodeGenerator::workOrderCode();
        $this->assertStringStartsWith('WO-' . $year . '-', $code);
    }
}
