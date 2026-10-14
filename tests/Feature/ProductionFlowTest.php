<?php
/**
 * تست ویژگی — چرخهٔ کامل تولید:
 * WO → تخصیص → مواد → بافت → تحویل → QC → اصالت FB → فیش PAY → پرداخت → تسویه
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\PayrollService;
use App\Modules\Production\Models\WorkOrder;
use App\Modules\Production\Services\ProductionService;
use Core\Auth;
use Core\CodeGenerator;
use Core\Database;
use Core\Events;
use Core\Session;
use PHPUnit\Framework\TestCase;

final class ProductionFlowTest extends TestCase
{
    private ?WorkOrder $wo = null;

    private int $weaverId;

    protected function setUp(): void
    {
        Events::flush();
        $weaver = Database::selectOne("SELECT id, wage_rate FROM weavers WHERE code = 'W002'");
        $this->weaverId = (int) $weaver['id'];
        $variant = Database::selectOne('SELECT id FROM product_variants WHERE is_active = 1 ORDER BY id LIMIT 1');

        $this->wo = ProductionService::create([
            'variant_id' => (int) $variant['id'],
            'quantity'   => 3,
            'weaver_id'  => $this->weaverId,
            'priority'   => 'normal',
            'due_date'   => date('Y-m-d', strtotime('+10 days')),
        ]);
    }

    public function test_work_order_created_with_valid_code(): void
    {
        $this->assertMatchesRegularExpression(CodeGenerator::PATTERNS['work_order'], (string) $this->wo->code);
        $this->assertSame('assigned', $this->wo->status); // تخصیص اولیه → assigned
        $this->assertSame((int) Database::selectOne('SELECT wage_rate FROM weavers WHERE id = ?', [$this->weaverId])['wage_rate'], (int) $this->wo->unit_wage);
    }

    public function test_state_machine_transitions(): void
    {
        // ماده توزیع کن تا دریافت مواد معتبر باشد
        $material = Database::selectOne('SELECT id, stock FROM materials WHERE stock > 5 LIMIT 1');
        ProductionService::distributeMaterial($this->wo, (int) $material['id'], 1.0);

        $this->assertTrue($this->wo->canTransitionTo('materials_received'));
        $this->assertFalse($this->wo->canTransitionTo('delivered')); // پرش غیرمجاز

        ProductionService::changeStatus($this->wo, 'materials_received', 'weaver');
        $this->assertSame('materials_received', WorkOrder::find((int) $this->wo->id())->status);

        ProductionService::changeStatus($this->wo, 'weaving', 'weaver');
        ProductionService::changeStatus($this->wo, 'delivered', 'weaver');
        $wo = WorkOrder::find((int) $this->wo->id());
        $this->assertSame('delivered', $wo->status);
        $this->assertNotNull($wo->delivered_at);
    }

    public function test_illegal_transition_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        ProductionService::changeStatus($this->wo, 'completed'); // از assigned مستقیم به completed نمی‌شود
    }

    public function test_material_distribution_decrements_stock(): void
    {
        $material = Database::selectOne('SELECT id, stock FROM materials WHERE stock > 5 LIMIT 1');
        $before = (float) $material['stock'];
        ProductionService::distributeMaterial($this->wo, (int) $material['id'], 2.5);
        $after = (float) Database::selectOne('SELECT stock FROM materials WHERE id = ?', [$material['id']])['stock'];
        $this->assertEqualsWithDelta($before - 2.5, $after, 0.001);

        $dist = Database::selectOne('SELECT * FROM material_distributions WHERE work_order_id = ? ORDER BY id DESC LIMIT 1', [$this->wo->id()]);
        $this->assertNotNull($dist);
        $this->assertEqualsWithDelta(2.5, (float) $dist['quantity'], 0.001);
    }

    public function test_insufficient_material_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        ProductionService::distributeMaterial($this->wo, (int) Database::selectOne('SELECT id FROM materials LIMIT 1')['id'], 999999);
    }

    public function test_delivery_increases_product_stock(): void
    {
        $this->prepareForQc();
        $variantId = (int) $this->wo->variant_id;
        $before = (int) Database::selectOne('SELECT stock FROM product_variants WHERE id = ?', [$variantId])['stock'];

        ProductionService::changeStatus($this->wo, 'delivered', 'weaver');
        $after = (int) Database::selectOne('SELECT stock FROM product_variants WHERE id = ?', [$variantId])['stock'];
        $this->assertSame($before + (int) $this->wo->quantity, $after);
    }

    public function test_qc_passed_issues_authenticity_and_payment(): void
    {
        $this->prepareForQc();
        ProductionService::changeStatus($this->wo, 'delivered', 'weaver');
        ProductionService::inspect($this->wo, 'passed', null, 90, 'بافت تمیز');

        $wo = WorkOrder::find((int) $this->wo->id());
        $this->assertSame('qc_passed', $wo->status);

        // کد بازرسی
        $qc = Database::selectOne('SELECT code FROM quality_inspections WHERE work_order_id = ?', [$wo->id()]);
        $this->assertMatchesRegularExpression(CodeGenerator::PATTERNS['quality'], (string) $qc['code']);

        // کدهای اصالت به تعداد qty (حداکثر ۲۵)
        $codes = Database::select('SELECT code FROM authenticity_codes WHERE work_order_id = ?', [$wo->id()]);
        $this->assertCount(3, $codes);
        foreach ($codes as $c) {
            $this->assertMatchesRegularExpression(CodeGenerator::PATTERNS['authenticity'], $c['code']);
        }
        // QR باید آدرس صفحهٔ اصالت باشد نه کد خام
        $qrUrl = (string) Database::selectOne('SELECT qr_url FROM authenticity_codes WHERE work_order_id = ? LIMIT 1', [$wo->id()])['qr_url'];
        $this->assertStringContainsString('/authenticity/', $qrUrl);

        // فیش دستمزد
        $payment = Database::selectOne('SELECT * FROM payments WHERE work_order_id = ?', [$wo->id()]);
        $this->assertNotNull($payment);
        $this->assertMatchesRegularExpression(CodeGenerator::PATTERNS['payment'], $payment['code']);
        $this->assertSame(3 * (int) $wo->unit_wage, (int) $payment['amount']);
        $this->assertSame('pending', $payment['status']);
    }

    public function test_qc_rejected_returns_to_rejected_state(): void
    {
        $this->prepareForQc();
        ProductionService::changeStatus($this->wo, 'delivered', 'weaver');
        ProductionService::inspect($this->wo, 'rejected', null, 30, 'پارگی تار');
        $this->assertSame('qc_rejected', WorkOrder::find((int) $this->wo->id())->status);

        // بازگشت به بافت مجاز است
        $wo = WorkOrder::find((int) $this->wo->id());
        $this->assertTrue($wo->canTransitionTo('weaving'));
    }

    public function test_payment_settles_balance_and_completes_wo(): void
    {
        $this->prepareForQc();
        ProductionService::changeStatus($this->wo, 'delivered', 'weaver');
        ProductionService::inspect($this->wo, 'passed', null, 95, null);

        $balanceBefore = (int) Database::selectOne('SELECT balance FROM weavers WHERE id = ?', [$this->weaverId])['balance'];
        $payment = Payment::findBy('work_order_id', (int) $this->wo->id());
        $this->assertNotNull($payment);
        $this->assertGreaterThan(0, $balanceBefore); // بدهی تعاونی به بافنده

        PayrollService::markPaid($payment);

        $balanceAfter = (int) Database::selectOne('SELECT balance FROM weavers WHERE id = ?', [$this->weaverId])['balance'];
        $this->assertSame($balanceBefore - (int) $payment->amount, $balanceAfter);
        $this->assertSame('completed', WorkOrder::find((int) $this->wo->id())->status);

        // پرداخت دوباره باید خطا بدهد
        $this->expectException(\RuntimeException::class);
        PayrollService::markPaid(Payment::find((int) $payment->id()));
    }

    public function test_progress_percent(): void
    {
        $this->assertSame(0, (new WorkOrder(['status' => 'draft']))->progressPercent());
        $steps = \Core\Statuses::WORK_ORDER_STEPS;
        $wo = new WorkOrder(['status' => 'weaving']);
        $idx = array_search('weaving', $steps, true);
        $this->assertSame((int) round((($idx + 1) / count($steps)) * 100), $wo->progressPercent());
        $this->assertSame(100, (new WorkOrder(['status' => 'completed']))->progressPercent());
    }

    public function test_events_published_through_cycle(): void
    {
        $this->prepareForQc();
        ProductionService::changeStatus($this->wo, 'delivered', 'weaver');
        $this->assertNotEmpty(Events::dispatched('workorder.delivered'));
        ProductionService::inspect($this->wo, 'passed', null, 88, null);
        $this->assertNotEmpty(Events::dispatched('workorder.qc_passed'));
        $this->assertNotEmpty(Events::dispatched('payment.issued'));
    }

    /** WO را به وضعیت delivered می‌رساند (با مواد لازم) */
    private function prepareForQc(): void
    {
        $material = Database::selectOne('SELECT id FROM materials WHERE stock > 5 LIMIT 1');
        ProductionService::distributeMaterial($this->wo, (int) $material['id'], 0.5);
        ProductionService::changeStatus($this->wo, 'materials_received', 'weaver');
        ProductionService::changeStatus($this->wo, 'weaving', 'weaver');
    }
}
