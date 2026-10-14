<?php
/**
 * کلاس Totp — رمز یک‌بارمصرف زمان‌محور (RFC 6238) برای نقش‌های حساس
 *
 * بدون وابستگی خارجی: HMAC-SHA1، دورهٔ ۳۰ ثانیه، ۶ رقم، پنجرهٔ تحمل ±۱.
 * سازگار با Google Authenticator / Authy / FreeOTP.
 */

declare(strict_types=1);

namespace Core;

final class Totp
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGO = 'sha1';

    /** تولید کلید base32 تصادفی (۱۶۰ بیت) */
    public static function generateSecret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $random = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[ord($random[$i]) & 31];
        }
        return $secret;
    }

    /** کد فعلی (یا زمان مشخص) برای یک کلید */
    public static function code(string $secret, ?int $timestamp = null): string
    {
        $counter = intdiv($timestamp ?? time(), self::PERIOD);
        return self::hotp($secret, $counter);
    }

    /** اعتبارسنجی کد با پنجرهٔ تحمل ±$window */
    public static function verify(string $secret, string $code, int $window = 1, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== self::DIGITS) {
            return false;
        }
        $now = $timestamp ?? time();
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $now + $i * self::PERIOD), $code)) {
                return true;
            }
        }
        return false;
    }

    /** URI استاندارد otpauth:// برای QR provisioning */
    public static function provisioningUri(string $secret, string $account, string $issuer = 'FaraatBafi'): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD
        );
    }

    /* ── داخلی ── */

    private static function hotp(string $secret, int $counter): string
    {
        $binary = self::base32Decode($secret);
        $binCounter = pack('N*', 0, $counter); // 8 بایت big-endian
        $hash = hash_hmac(self::ALGO, $binCounter, $binary, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $part = substr($hash, $offset, 4);
        $value = unpack('N', $part)[1] & 0x7FFFFFFF;
        return str_pad((string) ($value % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));
        $buffer = 0;
        $bits = 0;
        $output = '';
        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $pos = strpos($alphabet, $input[$i]);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $output .= chr(($buffer >> $bits) & 0xFF);
            }
        }
        return $output;
    }
}
