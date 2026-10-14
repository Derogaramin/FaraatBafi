<?php
/**
 * کلاس Icons — مجموعه آیکون‌های SVG درون‌خطی سامانه (سبک خطی، الهام از Tabler)
 *
 * همهٔ آیکون‌ها viewBox=24، stroke=currentcolor و بدون وابستگی بیرونی‌اند
 * تا سامانه کاملاً آفلاین کار کند.
 */

declare(strict_types=1);

namespace Core;

final class Icons
{
    /** @var array<string, string> مسیرهای SVG هر آیکون */
    private const PATHS = [
        'dashboard' => '<path d="M4 13h6a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1zm0 7h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1zm10 0h6a1 1 0 0 0 1-1v-7a1 1 0 0 0-1-1h-6a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1zm0-17v4a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1h-7a1 1 0 0 0-1 1z"/>',
        'box' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/>',
        'users' => '<path d="M9 7a4 4 0 1 0 8 0a4 4 0 0 0-8 0"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
        'cart' => '<circle cx="9" cy="20" r="1.6"/><circle cx="17" cy="20" r="1.6"/><path d="M3 3h2l2.4 12.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 7H6"/>',
        'truck' => '<path d="M3 6h11v9H3zM14 9h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
        'coin' => '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5A3 3 0 0 0 12 8.5c-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2a3 3 0 0 1-2.5-1M12 6.5v11"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'loom' => '<path d="M4 4h16M4 20h16M8 4v16M16 4v16M4 9h16M4 15h16"/>',
        'shuttle' => '<path d="M12 3c4 2 7 5 7 9s-3 7-7 9c-4-2-7-5-7-9s3-7 7-9z"/><circle cx="12" cy="12" r="2.5"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'bell' => '<path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3H4a4 4 0 0 0 2-3v-3a7 7 0 0 1 4-6"/><path d="M10 21h4"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M12 3a6.4 6.4 0 0 0 9 9 9 9 0 1 1-9-9z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M7 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1"/><path d="M20.4 3.6a2.1 2.1 0 1 1 0 3L11.5 15.5 8 17l1.5-3.5z"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'qr' => '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z"/><path d="M14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z"/>',
        'check' => '<path d="M5 13l4 4L19 7"/>',
        'x' => '<path d="M18 6L6 18M6 6l12 12"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
        'logout' => '<path d="M14 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2v-2"/><path d="M9 12h12l-3-3M21 12l-3 3"/>',
        'login' => '<path d="M10 8V6a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2v-2"/><path d="M15 12H3l3-3M3 12l3 3"/>',
        'ticket' => '<path d="M4 8V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4"/><path d="M12 4v2M12 10v2M12 16v2"/>',
        'blog' => '<path d="M4 4h16v14H8l-4 4z"/><path d="M8 8h8M8 12h5"/>',
        'quality' => '<path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/>',
        'warehouse' => '<path d="M3 21V9l9-5 9 5v12"/><path d="M7 21v-6h10v6M7 17h10"/>',
        'store' => '<path d="M4 9h16v11H4zM3 9l2-5h14l2 5M9 20v-6h6v6"/>',
        'package' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M4 7.5l8 4.5 8-4.5M12 12v9"/><path d="M8 5.2l8 4.6"/>',
        'scissors' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4L8.1 15.9M14.5 14.5L20 20M8.1 8.1L5 5"/>',
        'money' => '<path d="M3 6h18v12H3z"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
        'vote' => '<path d="M4 13h16v7H4zM9 10V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v5"/><path d="M11 10h2v3h-2z"/>',
        'letter' => '<path d="M3 5h18v14H3z"/><path d="M3 7l9 6 9-6"/>',
        'meeting' => '<circle cx="9" cy="7" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 21v-2a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v2"/><path d="M17 14a4 4 0 0 1 5 4v3"/>',
        'heart' => '<path d="M12 20s-7-4.6-9.3-9A5 5 0 0 1 12 6.5 5 5 0 0 1 21.3 11c-2.3 4.4-9.3 9-9.3 9z"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'map-pin' => '<path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0-6 0"/><path d="M17.7 17.7L12 23l-5.7-5.3A8 8 0 1 1 17.7 17.7z"/>',
        'refresh' => '<path d="M20 11a8.1 8.1 0 0 0-15.6-2.4M4 13a8.1 8.1 0 0 0 15.6 2.4"/><path d="M20 4v7h-7M4 20v-7h7"/>',
        'download' => '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 3v12M7 10l5 5 5-5"/>',
        'print' => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/>',
        'alert' => '<path d="M12 3l10 18H2z"/><path d="M12 9v5M12 17.5v.5"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>',
        'arrow-left' => '<path d="M5 12h14M12 5l-7 7 7 7"/>',
        'arrow-right' => '<path d="M19 12H5M12 19l7-7-7-7"/>',
        'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
        'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'star' => '<path d="M12 3l2.9 5.8 6.1.9-4.5 4.4 1.1 6.4-5.6-3-5.6 3 1.1-6.4L3 9.7l6.1-.9z"/>',
        'barcode' => '<path d="M4 5v14M7 5v14M10 5v10M13 5v14M16 5v10M20 5v14"/>',
        'thread' => '<path d="M12 3v18M8 6c8 2-8 4 0 6s-8 4 0 6"/>',
        'palette' => '<path d="M12 3a9 9 0 1 0 0 18c1 0 1.7-.8 1.7-1.7 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-1 .8-1.7 1.7-1.7H16a5 5 0 0 0 5-5c0-3.9-4-7.2-9-7.2z"/><circle cx="7.5" cy="10.5" r="1"/><circle cx="12" cy="7.5" r="1"/><circle cx="16.5" cy="10.5" r="1"/>',
        'book' => '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v18H6.5a2.5 2.5 0 0 0 0 5H20"/>',
        'handshake' => '<path d="M12 11l-2-2a2.8 2.8 0 0 0-4 4l4 4 4-4M12 11l2-2a2.8 2.8 0 0 1 4 4l-4 4-2-2"/><path d="M2 9l4-4 3 3M22 9l-4-4-3 3"/>',
    ];

    /** رندر آیکون SVG درون‌خطی */
    public static function render(string $name, int $size = 20, string $class = ''): string
    {
        $path = self::PATHS[$name] ?? self::PATHS['box'];
        $cls = $class !== '' ? ' ' . htmlspecialchars($class, ENT_QUOTES) : '';
        return sprintf(
            '<svg class="icon%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
            $cls,
            $size,
            $size,
            $path
        );
    }

    public static function has(string $name): bool
    {
        return isset(self::PATHS[$name]);
    }

    /** @return string[] نام همهٔ آیکون‌ها */
    public static function names(): array
    {
        return array_keys(self::PATHS);
    }
}
