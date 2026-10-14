<?php
/**
 * تست ویژگی — فروش حضوری نمایندگی (POS)
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Agency\Services\PosService;
use App\Modules\Members\Models\Agency;
use App\Modules\Marketplace\Services\CartService;
use Core\Auth;
use Core\CodeGenerator;
use Core\Database;
use Core\Events;
use PHPUnit\Framework\TestCase;

final class PosFlowTest extends TestCase
{
    private Agency $agency;

    protected function setUp(): void
    {
        Events::flush();
        CartService::clear();
        Auth::logout();
        Auth::resetResolution();
        $row = Database::selectOne("SELECT * FROM agencies WHERE code = 'A01'");
        $this->agency = Agency::hydrate($row);
    }

    public function test_pos_sale_creates_order_invoice_receipt_commission(): void
    {
        // کالای امانی با موجودی مثبت
        $cons = Database::selectOne(
            'SELECT cs.*, pv.sku, pv.stock FROM consignments cs
             JOIN product_variants pv ON pv.id = cs.variant_id
             WHERE cs.agency_id = ? AND cs.quantity > 0 AND pv.stock > 0 AND pv.is_active = 1 LIMIT 1',
            [$this->agency->id()]
        );
        $this->assertNotNull($cons, 'کالای امانی نمونه لازم است');

        $commBefore = (int) Database::selectOne('SELECT COUNT(*) AS c FROM commissions WHERE agency_id = ?', [$this->agency->id()])['c'];

        CartService::add($cons['sku'], 1);
        $result = PosService::sell($this->agency, [
            'receiver' => 'خریدار حضوری تست',
            'phone'    => '09155551234',
            'city'     => 'تربت جام',
        ]);

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $this->assertMatchesRegularExpression(CodeGenerator::PATTERNS['order'], $result['code']);

        $orderId = (int) $result['id'];
        $order = Database::selectOne('SELECT * FROM orders WHERE id = ?', [$orderId]);
        $this->assertSame('agency', $order['channel']);
        $this->assertSame('delivered', $order['status']);      // فروش حضوری = تحویل در جا
        $this->assertSame((int) $this->agency->id(), (int) $order['agency_id']);

        // فاکتور پرداخت‌شده + رسید
        $invoice = Database::selectOne('SELECT * FROM invoices WHERE order_id = ?', [$orderId]);
        $this->assertSame(1, (int) $invoice['paid']);
        $receipt = Database::selectOne('SELECT * FROM receipts WHERE order_id = ?', [$orderId]);
        $this->assertNotNull($receipt);
        $this->assertMatchesRegularExpression(CodeGenerator::PATTERNS['receipt'], $receipt['code']);

        // پورسانت با نرخ نمایندگی
        $comm = Database::selectOne('SELECT * FROM commissions WHERE order_id = ?', [$orderId]);
        $this->assertNotNull($comm);
        $this->assertMatchesRegularExpression(CodeGenerator::PATTERNS['commission'], $comm['code']);
        $expected = (int) round((int) $order['subtotal'] * ((float) $this->agency->commission_rate / 100));
        $this->assertSame($expected, (int) $comm['amount']);

        // کسر از کالای امانی
        $consAfter = Database::selectOne(
            'SELECT quantity FROM consignments WHERE agency_id = ? AND variant_id = ?',
            [$this->agency->id(), $cons['variant_id']]
        );
        $this->assertSame((int) $cons['quantity'] - 1, (int) $consAfter['quantity']);

        // رویداد pos.sale
        $this->assertNotEmpty(Events::dispatched('pos.sale'));
        $this->assertSame(0, CartService::count()); // سبد تخلیه شد
    }

    public function test_pos_empty_cart_fails(): void
    {
        $result = PosService::sell($this->agency, ['receiver' => 'تست', 'phone' => '09155551235', 'city' => 'تربت جام']);
        $this->assertFalse($result['ok']);
    }

    public function test_consignment_moves_stock_from_center(): void
    {
        $variant = Database::selectOne('SELECT id, stock FROM product_variants WHERE stock > 5 AND is_active = 1 ORDER BY id LIMIT 1');
        $centerBefore = (int) $variant['stock'];
        $agencyBefore = (int) (Database::selectOne(
            'SELECT COALESCE(quantity,0) AS q FROM consignments WHERE agency_id = ? AND variant_id = ?',
            [$this->agency->id(), $variant['id']]
        )['q'] ?? 0);

        PosService::consign($this->agency, (int) $variant['id'], 3);

        $centerAfter = (int) Database::selectOne('SELECT stock FROM product_variants WHERE id = ?', [$variant['id']])['stock'];
        $agencyAfter = (int) Database::selectOne(
            'SELECT quantity FROM consignments WHERE agency_id = ? AND variant_id = ?',
            [$this->agency->id(), $variant['id']]
        )['quantity'];

        $this->assertSame($centerBefore - 3, $centerAfter);
        $this->assertSame($agencyBefore + 3, $agencyAfter);
    }

    public function test_consignment_more_than_center_stock_fails(): void
    {
        $variant = Database::selectOne('SELECT id, stock FROM product_variants ORDER BY stock ASC LIMIT 1');
        $this->expectException(\RuntimeException::class);
        PosService::consign($this->agency, (int) $variant['id'], (int) $variant['stock'] + 1000);
    }
}
