<?php
/**
 * تست ویژگی — ماشین حالت سفارش (OrderFlow) + enumها
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Marketplace\Models\Order;
use App\Modules\Marketplace\Services\CartService;
use App\Modules\Marketplace\Services\CheckoutService;
use Core\Business\OrderFlow;
use Core\Database;
use Core\Enums\OrderStatus;
use Core\Enums\WorkOrderStatus;
use Core\Events;
use Core\Session;
use PHPUnit\Framework\TestCase;

final class OrderFlowTest extends TestCase
{
    protected function setUp(): void
    {
        Events::flush();
        CartService::clear();
    }

    private function makeOrder(): Order
    {
        $variant = Database::selectOne('SELECT sku, stock FROM product_variants WHERE is_active=1 AND stock > 1 ORDER BY id LIMIT 1');
        CartService::add($variant['sku'], 1);
        $result = CheckoutService::place(['receiver' => 'تست جریان', 'phone' => '09129998877', 'city' => 'مشهد', 'address' => 'آدرس تست جریان']);
        $this->assertTrue($result['ok'], $result['error'] ?? '');
        return Order::find($result['id']);
    }

    public function test_enum_labels_and_colors_cover_all_cases(): void
    {
        foreach (OrderStatus::cases() as $case) {
            $this->assertNotSame('', $case->label());
            $this->assertNotSame('', $case->color());
        }
        $this->assertSame('در انتظار تأیید', OrderStatus::Pending->label());
        $this->assertSame('در حال بافت', WorkOrderStatus::Weaving->label());
    }

    public function test_legal_path_to_delivered(): void
    {
        $order = $this->makeOrder();
        $this->assertSame('pending', $order->status);

        OrderFlow::change($order, 'confirmed');
        // شنوندهٔ order.confirmed (ماژول Production) سفارش را به in_production پیش می‌برد
        $this->assertContains(
            Order::find((int) $order->id())->status,
            ['confirmed', 'in_production'],
            'پس از تأیید، وضعیت باید confirmed یا in_production باشد'
        );

        OrderFlow::change($order, 'ready');
        OrderFlow::change($order, 'shipped');
        OrderFlow::change($order, 'delivered');
        $final = Order::find((int) $order->id());
        $this->assertSame('delivered', $final->status);
        $this->assertNotNull($final->delivered_at);
    }

    public function test_illegal_transitions_rejected(): void
    {
        $order = $this->makeOrder();
        // pending → shipped غیرمجاز است
        $this->expectException(\RuntimeException::class);
        OrderFlow::change($order, 'shipped');
    }

    public function test_illegal_transition_does_not_change_status(): void
    {
        $order = $this->makeOrder();
        try {
            OrderFlow::change($order, 'delivered');
            $this->fail('باید استثنا می‌داد');
        } catch (\RuntimeException) {
            // انتظار می‌رود
        }
        $this->assertSame('pending', Order::find((int) $order->id())->status);
    }

    public function test_cancel_restores_stock(): void
    {
        $variant = Database::selectOne('SELECT id, sku, stock FROM product_variants WHERE is_active=1 AND stock > 2 ORDER BY id LIMIT 1');
        CartService::add($variant['sku'], 2);
        $result = CheckoutService::place(['receiver' => 'تست لغو', 'phone' => '09129996655', 'city' => 'مشهد', 'address' => 'آدرس تست لغو']);
        $order = Order::find($result['id']);

        $afterBuy = (int) Database::selectOne('SELECT stock FROM product_variants WHERE id = ?', [$variant['id']])['stock'];
        OrderFlow::change($order, 'cancelled');
        $afterCancel = (int) Database::selectOne('SELECT stock FROM product_variants WHERE id = ?', [$variant['id']])['stock'];

        $this->assertSame($afterBuy + 2, $afterCancel);
        $this->assertSame('cancelled', Order::find((int) $order->id())->status);

        // گذار از وضعیت لغوشده ممنوع است
        $this->assertSame([], OrderFlow::allowedFor('cancelled'));
    }

    public function test_allowed_for_matches_enum(): void
    {
        $this->assertSame(['confirmed', 'cancelled'], OrderFlow::allowedFor('pending'));
        $this->assertContains('ready', OrderFlow::allowedFor('confirmed'));
        $this->assertSame([], OrderFlow::allowedFor('delivered') === [] ? [] : OrderFlow::allowedFor('returned'));
        $this->assertSame([], OrderFlow::allowedFor('garbage-status'));
    }

    public function test_flow_dispatches_events_and_audits(): void
    {
        $order = $this->makeOrder();
        OrderFlow::change($order, 'confirmed');
        $this->assertNotEmpty(Events::dispatched('order.status_changed'));
        $this->assertNotEmpty(Events::dispatched('order.confirmed'));

        $audit = Database::selectOne(
            'SELECT * FROM field_audits WHERE entity = "order" AND entity_id = ? AND field = "status" ORDER BY id DESC LIMIT 1',
            [$order->id()]
        );
        $this->assertNotNull($audit, 'ممیزی تغییر وضعیت باید ثبت شود');
        $this->assertSame('pending', $audit['old_value']);
        $this->assertSame('confirmed', $audit['new_value']);
    }

    public function test_workorder_enum_transitions(): void
    {
        $this->assertTrue(WorkOrderStatus::Assigned->canTransitionTo(WorkOrderStatus::MaterialsReceived));
        $this->assertFalse(WorkOrderStatus::Assigned->canTransitionTo(WorkOrderStatus::Delivered));
        $this->assertTrue(WorkOrderStatus::QcRejected->canTransitionTo(WorkOrderStatus::Weaving));
        $this->assertSame('delivered_at', WorkOrderStatus::Delivered->timestampField());
        $this->assertNull(WorkOrderStatus::QcRejected->timestampField());
    }
}
