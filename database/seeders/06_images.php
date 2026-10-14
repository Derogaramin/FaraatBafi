<?php
/**
 * دانه‌گذار ۰۶ — انتساب تصاویر نمونه به واریانت‌ها و نوشته‌ها
 * (فایل‌های تصویری در public/assets/uploads/products موجودند)
 */

declare(strict_types=1);

use Core\Database;

return static function (): void {
    $map = [
        // SKU → تصویر
        'HT-2LBSTD-CH125-R01-P0001' => 'products/towel-madder.webp',
        'HT-3LBSTD-CH140-K01-P0002' => 'products/towel-madder.webp',
        'SH-2LGSTD-CH100-R01-P0002' => 'products/shawl-indigo.webp',
        'SH-2LGSTD-CH100-I01-P0006' => 'products/shawl-indigo.webp',
        'SH-1LB2N-CH85-K02-P0005'  => 'products/shawl-indigo.webp',
        'HC-2LBR-CH100-G02-P0005'  => 'products/tablecloth-toranj.webp',
        'HC-2LBR-CH100-R02-P0003'  => 'products/tablecloth-toranj.webp',
        'FB-2LBNS-CH125-R01-P0006' => 'products/fabric-madder.webp',
        'FB-2LBNS-CH125-Y02-P0002' => 'products/fabric-madder.webp',
    ];
    $updated = 0;
    foreach ($map as $sku => $image) {
        $base = dirname(__DIR__, 2) . '/public/assets/uploads/' . $image;
        if (!is_file($base)) {
            continue;
        }
        $updated += Database::update('UPDATE product_variants SET image = ? WHERE sku = ?', [$image, $sku]);
    }

    // تصویر نوشته‌های وبلاگ
    Database::update("UPDATE posts SET image = 'products/fabric-madder.webp' WHERE slug = 'natural-dyes-of-faraat'");
    Database::update("UPDATE posts SET image = 'products/shawl-indigo.webp' WHERE slug = 'three-generations-at-the-loom'");
    Database::update("UPDATE posts SET image = 'products/towel-madder.webp' WHERE slug = 'warp-and-weft-explained'");

    echo '  → ' . fa((string) $updated) . " تصویر نمونه به محصولات انتساب شد\n";
};
