<?php
/**
 * تست ویژگی — جریان کامل خرید از بازارگاه
 * سبد → ثبت سفارش → کد ORD → اقلام → کسر انبار → فاکتور → رویداد order.placed
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Marketplace\Models\Order;
use App\Modules\Marketplace\Services\CartService;
use App\Modules\Marketplace\Services\CheckoutService;
use Core\Auth;
use Core\Database;
use Core\Events;
use Core\Session;
use PHPUnit\Framework\TestCase;

final class CheckoutFlowTest extends TestCase
{
    protected function setUp(): void
    {
        Events::flush();
        CartService::clear();
        Auth::logout();
        Auth::resetResolution();
    }

    private function sampleSku(): array
    {
        $row = Database::selectOne('SELECT id, sku, stock, price FROM product_variants WHERE is_active = 1 AND stock > 2 ORDER BY id LIMIT 1');
        $this->assertNotNull($row, 'دادهٔ نمونه باید موجود باشد');
        return $row;
    }

    public function test_cart_add_update_remove(): void
    {
        $variant = $this->sampleSku();

        $result = CartService::add($variant['sku'], 2);
        $this->assertTrue($result['ok']);
        $this->assertSame(2, CartService::count());

        CartService::add($variant['sku'], 1);
        $this->assertSame(3, CartService::count());

        CartService::update($variant['sku'], 1);
        $this->assertSame(1, CartService::count());

        $details = CartService::details();
        $this->assertCount(1, $details['items']);
        $this->assertSame((int) $variant['price'], $details['subtotal']);

        CartService::remove($variant['sku']);
        $this->assertSame(0, CartService::count());
    }

    public function test_cannot_add_more_than_stock(): void
    {
        $variant = $this->sampleSku();
        $result = CartService::add($variant['sku'], (int) $variant['stock'] + 10);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('موجودی', $result['error']);
    }

    public function test_unknown_sku_rejected(): void
    {
        $result = CartService::add('FAKE-SKU-000', 1);
        $this->assertFalse($result['ok']);
    }

    public function test_place_order_full_flow(): void
    {
        $variant = $this->sampleSku();
        $stockBefore = (int) $variant['stock'];

        CartService::add($variant['sku'], 2);
        $result = CheckoutService::place([
            'receiver' => 'خریدار تست',
            'phone'    => '09121112233',
            'city'     => 'مشهد',
            'address'  => 'خیابان تست، کوچهٔ آزمون، پلاک ۱',
        ]);

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $this->assertArrayHasKey('id', $result);   // طبق مستندات: پاسخ باید id + کد داشته باشد
        $this->assertArrayHasKey('code', $result);
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{6}$/', $result['code']);

        $order = Order::find($result['id']);
        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status);
        $this->assertSame('web', $order->channel);
        $this->assertSame(2 * (int) $variant['price'] + CheckoutService::SHIPPING_FLAT, (int) $order->total);

        // اقلام با اسکنپشوت
        $items = $order->items();
        $this->assertCount(1, $items);
        $this->assertSame($variant['sku'], $items[0]['sku']);
        $this->assertSame(2, (int) $items[0]['quantity']);

        // کسر انبار
        $stockAfter = (int) Database::selectOne('SELECT stock FROM product_variants WHERE id = ?', [$variant['id']])['stock'];
        $this->assertSame($stockBefore - 2, $stockAfter);

        // فاکتور صادر شده
        $invoice = Database::selectOne('SELECT * FROM invoices WHERE order_id = ?', [$order->id()]);
        $this->assertNotNull($invoice);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{5}$/', $invoice['code']);
        $this->assertSame(0, (int) $invoice['paid']);

        // مشتری خودکار با کد C ساخته شده
        $customer = Database::selectOne('SELECT * FROM customers WHERE phone = ?', ['09121112233']);
        $this->assertNotNull($customer);
        $this->assertMatchesRegularExpression('/^C\d{6}$/', $customer['code']);

        // حرکت انبار ثبت شده
        $movement = Database::selectOne(
            'SELECT * FROM inventory_movements WHERE ref_type = "sale" AND ref_id = ?',
            [$order->id()]
        );
        $this->assertNotNull($movement);
        $this->assertSame('out', $movement['direction']);

        // رویداد order.placed منتشر شده (ED)
        $this->assertNotEmpty(Events::dispatched('order.placed'));

        // سبد خالی شده
        $this->assertSame(0, CartService::count());
    }

    public function test_empty_cart_cannot_checkout(): void
    {
        $result = CheckoutService::place(['receiver' => 'تست', 'phone' => '09121112234', 'city' => 'مشهد', 'address' => '—']);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('خالی', $result['error']);
    }

    public function test_coupon_applies_discount(): void
    {
        $variant = Database::selectOne('SELECT sku, price FROM product_variants WHERE is_active=1 AND stock >= 4 AND price * 4 > 600000 ORDER BY price DESC LIMIT 1');
        CartService::add($variant['sku'], 4);

        $couponCode = (string) Database::selectOne("SELECT code FROM coupons WHERE type='percent' AND is_active=1 LIMIT 1")['code'];
        $result = CheckoutService::place(
            ['receiver' => 'تست کوپن', 'phone' => '09121115566', 'city' => 'تهران', 'address' => 'آدرس تست کوپن پلاک ۲'],
            null,
            null,
            'web',
            $couponCode
        );
        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $order = Order::find($result['id']);
        $this->assertGreaterThan(0, (int) $order->discount);
        $this->assertSame((int) $order->subtotal - (int) $order->discount + (int) $order->shipping_cost, (int) $order->total);
    }

    public function test_invalid_coupon_rejected(): void
    {
        $variant = $this->sampleSku();
        CartService::add($variant['sku'], 1);
        $result = CheckoutService::place(
            ['receiver' => 'تست', 'phone' => '09121117788', 'city' => 'مشهد', 'address' => 'آدرس تست پلاک ۳'],
            null, null, 'web', 'CPN-FAKE-1400'
        );
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('کوپن', $result['error']);
    }

    public function test_order_tracking_phone_match(): void
    {
        $variant = $this->sampleSku();
        CartService::add($variant['sku'], 1);
        $result = CheckoutService::place(['receiver' => 'تست پیگیری', 'phone' => '09121119900', 'city' => 'مشهد', 'address' => 'آ ۴']);
        $order = Order::find($result['id']);
        $this->assertTrue($order->matchesPhone('09121119900'));
        $this->assertTrue($order->matchesPhone('+989121119900'));
        $this->assertFalse($order->matchesPhone('09120000000'));
    }
}
