<?php
/**
 * تست واحد TOTP (RFC 6238) — بردارهای آزمون استاندارد
 */

declare(strict_types=1);

namespace Tests\Unit;

use Core\Totp;
use PHPUnit\Framework\TestCase;

final class TotpTest extends TestCase
{
    /** کلید base32 رشتهٔ مرجع RFC «12345678901234567890» */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_rfc_6238_vectors_sha1_6digit(): void
    {
        // HOTP/TOTP مرجع: شمارندهٔ T=59s → counter=1 → 287082
        $this->assertSame('287082', Totp::code(self::RFC_SECRET, 59));
        // T=1111111109 → counter=37037036 → 07081804 mod 10^6 = 081804
        $this->assertSame('081804', Totp::code(self::RFC_SECRET, 1111111109));
    }

    public function test_verify_accepts_current_and_adjacent_windows(): void
    {
        $now = 1_700_000_010; // وسط یک دورهٔ ۳۰ ثانیه‌ای
        $code = Totp::code(self::RFC_SECRET, $now);
        $this->assertTrue(Totp::verify(self::RFC_SECRET, $code, 1, $now));
        // کد دورهٔ قبل با پنجرهٔ ±۱ پذیرفته می‌شود
        $prev = Totp::code(self::RFC_SECRET, $now - 30);
        $this->assertTrue(Totp::verify(self::RFC_SECRET, $prev, 1, $now));
        // دو دوره قبل → رد
        $old = Totp::code(self::RFC_SECRET, $now - 90);
        $this->assertFalse(Totp::verify(self::RFC_SECRET, $old, 1, $now));
    }

    public function test_verify_rejects_garbage(): void
    {
        $this->assertFalse(Totp::verify(self::RFC_SECRET, 'abcdef'));
        $this->assertFalse(Totp::verify(self::RFC_SECRET, '12345'));
        $this->assertFalse(Totp::verify(self::RFC_SECRET, ''));
        $this->assertFalse(Totp::verify(self::RFC_SECRET, '000000', 0, 59)); // کد واقعی 287082 است
    }

    public function test_generated_secret_is_base32_and_unique(): void
    {
        $a = Totp::generateSecret();
        $b = Totp::generateSecret();
        $this->assertSame(32, strlen($a));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $a);
        $this->assertNotSame($a, $b);
        // کلید تصادفی باید کد معتبر تولید کند
        $this->assertMatchesRegularExpression('/^\d{6}$/', Totp::code($a));
    }

    public function test_generated_secret_verifies_own_code(): void
    {
        $secret = Totp::generateSecret();
        $this->assertTrue(Totp::verify($secret, Totp::code($secret)));
    }

    public function test_provisioning_uri_format(): void
    {
        $uri = Totp::provisioningUri('JBSWY3DPEHPK3PXP', 'W001 — بی‌بی گل', 'فرت‌بافی');
        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=', $uri);
        $this->assertStringContainsString('period=30', $uri);
        $this->assertStringContainsString('digits=6', $uri);
    }
}
