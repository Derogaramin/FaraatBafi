<?php
/**
 * کلاس QR — تولید QR Code (SVG) برای کدهای اصالت فرت
 *
 * بر پایهٔ کتابخانهٔ chillerlan/php-qrcode.
 * طبق مستندات، QR باید حاوی «آدرس صفحهٔ اصالت» باشد نه کد خام:
 *   https://faraat.ir/authenticity/FB-1403-0521-W023
 */

declare(strict_types=1);

namespace Core;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;

final class QR
{
    /** آدرس صفحهٔ اصالت برای یک کد FB-... */
    public static function authenticityUrl(string $code): string
    {
        $base = rtrim((string) Config::get('app.public_url', Config::get('app.base_url', '')), '/');
        return $base . '/authenticity/' . rawurlencode($code);
    }

    /** تولید SVG یک کد اصالت (رنگ زرشکی برند) */
    public static function authenticitySvg(string $code, int $scale = 6): string
    {
        return self::svg(self::authenticityUrl($code), $scale, '#8B2E3A');
    }

    /** تولید SVG دلخواه */
    public static function svg(string $data, int $scale = 6, string $color = '#1A3B4C'): string
    {
        $options = new QROptions([
            'outputInterface' => QROutputInterface::MARKUP_SVG,
            'eccLevel'        => EccLevel::M,
            'scale'           => $scale,
            'svgViewBoxSize'  => 256,
            'cssClass'        => 'qrcode',
            'moduleValues'    => [
                1024 => $color, // ماژول داده (dark)
                5    => $color,
                15   => $color,
                30   => $color,
                45   => $color,
                60   => $color,
            ],
        ]);
        return (new QRCode($options))->render($data);
    }

    /** data URI برای استفاده در <img> */
    public static function dataUri(string $data, int $scale = 6, string $color = '#1A3B4C'): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::svg($data, $scale, $color));
    }
}
