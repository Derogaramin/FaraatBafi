<?php
/**
 * سرویس سبد خرید — ذخیرهٔ نشست‌محور سبد با کلید SKU
 *
 * ساختار نشست: ['HT-2LBSTD-CH125-R01-P0003' => 2, ...]
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Services;

use App\Modules\Catalog\Models\ProductVariant;
use Core\Database;
use Core\Session;

final class CartService
{
    /** @return array<string, int> */
    public static function items(): array
    {
        return (array) Session::get('cart', []);
    }

    public static function count(): int
    {
        return array_sum(array_map('intval', self::items()));
    }

    /** افزودن به سبد — بررسی موجودی واقعی */
    public static function add(string $sku, int $qty = 1): array
    {
        $qty = max(1, $qty);
        $variant = self::variantBySku($sku);
        if ($variant === null) {
            return ['ok' => false, 'error' => 'محصول مورد نظر یافت نشد.'];
        }
        if ((int) $variant['is_active'] !== 1) {
            return ['ok' => false, 'error' => 'این محصول فعلاً فعال نیست.'];
        }
        $cart = self::items();
        $newQty = ($cart[$sku] ?? 0) + $qty;
        if ($newQty > (int) $variant['stock']) {
            return ['ok' => false, 'error' => 'موجودی انبار برای این محصول کافی نیست (موجودی: ' . fa((string) $variant['stock']) . ').'];
        }
        $cart[$sku] = $newQty;
        Session::put('cart', $cart);
        return ['ok' => true, 'message' => 'به سبد خرید اضافه شد.', 'sku' => $sku];
    }

    /** به‌روزرسانی تعداد یک قلم */
    public static function update(string $sku, int $qty): array
    {
        $cart = self::items();
        if (!isset($cart[$sku])) {
            return ['ok' => false, 'error' => 'این قلم در سبد نیست.'];
        }
        if ($qty <= 0) {
            return self::remove($sku);
        }
        $variant = self::variantBySku($sku);
        if ($variant !== null && $qty > (int) $variant['stock']) {
            return ['ok' => false, 'error' => 'موجودی انبار کافی نیست.'];
        }
        $cart[$sku] = $qty;
        Session::put('cart', $cart);
        return ['ok' => true, 'message' => 'سبد به‌روزرسانی شد.'];
    }

    public static function remove(string $sku): array
    {
        $cart = self::items();
        unset($cart[$sku]);
        Session::put('cart', $cart);
        return ['ok' => true, 'message' => 'از سبد حذف شد.'];
    }

    public static function clear(): void
    {
        Session::forget('cart');
    }

    /**
     * جزئیات کامل سبد برای نمایش/پرداخت.
     *
     * @return array{items: array, subtotal: int, count: int}
     */
    public static function details(): array
    {
        $cart = self::items();
        if ($cart === []) {
            return ['items' => [], 'subtotal' => 0, 'count' => 0];
        }
        $skus = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($skus), '?'));
        $rows = Database::select(
            "SELECT pv.id, pv.sku, pv.price, pv.stock, pv.image, pv.is_active,
                    pm.name_fa AS product_name, pm.size_cm, pt.name_fa AS type_name,
                    c.hex AS color_hex, c.name_fa AS color_name
             FROM product_variants pv
             JOIN product_models pm ON pm.id = pv.model_id
             JOIN product_types pt ON pt.id = pm.type_id
             JOIN colors c ON c.id = pv.color_id
             WHERE pv.sku IN ({$placeholders})",
            $skus
        );
        $items = [];
        $subtotal = 0;
        foreach ($rows as $row) {
            $qty = min((int) $cart[$row['sku']], max(0, (int) $row['stock']));
            if ($qty <= 0 || (int) $row['is_active'] !== 1) {
                continue; // ناموجود/غیرفعال → حذف از نمایش
            }
            $total = $qty * (int) $row['price'];
            $subtotal += $total;
            $items[] = [...$row, 'qty' => $qty, 'line_total' => $total];
        }
        return ['items' => $items, 'subtotal' => $subtotal, 'count' => array_sum(array_column($items, 'qty'))];
    }

    private static function variantBySku(string $sku): ?array
    {
        return ProductVariant::query()
            ->select('id', 'sku', 'stock', 'price', 'is_active')
            ->where('sku', $sku)
            ->first();
    }
}
