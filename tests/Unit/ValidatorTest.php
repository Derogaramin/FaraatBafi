<?php
/**
 * تست واحد اعتبارسنج — قواعد بدون نیاز به DB
 */

declare(strict_types=1);

namespace Tests\Unit;

use Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_required_and_messages_are_persian(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required'], ['name' => 'نام']);
        $this->assertTrue($v->fails());
        $this->assertSame('«نام» الزامی است.', $v->firstError());
    }

    public function test_min_max_length(): void
    {
        $this->assertTrue(Validator::make(['t' => 'ab'], ['t' => 'required|min:3'])->fails());
        $this->assertTrue(Validator::make(['t' => 'abcd'], ['t' => 'required|min:3'])->passes());
        $this->assertTrue(Validator::make(['t' => 'abcdef'], ['t' => 'max:4'])->fails());
    }

    public function test_numeric_min_is_value_not_length(): void
    {
        $this->assertTrue(Validator::make(['price' => '500'], ['price' => 'required|numeric|min:1000'])->fails());
        $this->assertTrue(Validator::make(['price' => '1500'], ['price' => 'required|numeric|min:1000'])->passes());
    }

    public function test_phone_normalization(): void
    {
        foreach (['09151234567', '+989151234567', '989151234567', '9151234567', '۰۹۱۵۱۲۳۴۵۶۷'] as $phone) {
            $v = Validator::make(['p' => $phone], ['p' => 'required|phone']);
            $this->assertTrue($v->passes(), "شمارهٔ {$phone} باید معتبر باشد");
        }
        $this->assertTrue(Validator::make(['p' => '02112345678'], ['p' => 'phone'])->fails());
        $this->assertTrue(Validator::make(['p' => '12345'], ['p' => 'phone'])->fails());
    }

    public function test_national_id(): void
    {
        $this->assertTrue(Validator::make(['n' => '0084575948'], ['n' => 'melli'])->passes());
        $this->assertTrue(Validator::make(['n' => '1111111111'], ['n' => 'melli'])->fails()); // رقم تکراری
        $this->assertTrue(Validator::make(['n' => '123'], ['n' => 'melli'])->fails());
    }

    public function test_jalali_date(): void
    {
        $this->assertTrue(Validator::make(['d' => '1405/07/22'], ['d' => 'jalali_date'])->passes());
        $this->assertTrue(Validator::make(['d' => '۱۴۰۵/۰۷/۲۲'], ['d' => 'jalali_date'])->passes()); // رقم فارسی
        $this->assertTrue(Validator::make(['d' => '1405/13/01'], ['d' => 'jalali_date'])->fails()); // ماه ۱۳
        $this->assertTrue(Validator::make(['d' => '1404/12/30'], ['d' => 'jalali_date'])->fails()); // ۱۴۰۴ کبیسه نیست
        $this->assertTrue(Validator::make(['d' => '1403-12-30'], ['d' => 'jalali_date'])->passes()); // ۱۴۰۳ کبیسه
    }

    public function test_in_regex_confirmed(): void
    {
        $this->assertTrue(Validator::make(['s' => 'weaving'], ['s' => 'in:weaving,delivered'])->passes());
        $this->assertTrue(Validator::make(['s' => 'flying'], ['s' => 'in:weaving,delivered'])->fails());
        $this->assertTrue(Validator::make(['c' => 'HT'], ['c' => 'regex:/^[A-Z]{2,3}$/'])->passes());
        $this->assertTrue(Validator::make(
            ['p' => 'abc', 'p_confirmation' => 'abc'],
            ['p' => 'confirmed']
        )->passes());
        $this->assertTrue(Validator::make(
            ['p' => 'abc', 'p_confirmation' => 'xyz'],
            ['p' => 'confirmed']
        )->fails());
    }

    public function test_optional_field_skips_rules_when_empty(): void
    {
        $v = Validator::make(['email' => ''], ['email' => 'email']);
        $this->assertTrue($v->passes());
    }

    public function test_validated_returns_only_rule_fields(): void
    {
        $v = Validator::make(['a' => '1', 'b' => '2', 'evil' => '<x>'], ['a' => 'required', 'b' => 'numeric']);
        $this->assertSame(['a' => '1', 'b' => '2'], $v->validated());
    }

    public function test_errors_keyed_by_field(): void
    {
        $v = Validator::make(['x' => '', 'y' => ''], ['x' => 'required', 'y' => 'required'], ['x' => 'ایکس', 'y' => 'ایگرک']);
        $this->assertCount(2, $v->errors());
        $this->assertArrayHasKey('x', $v->errors());
        $this->assertArrayHasKey('y', $v->errors());
    }
}
