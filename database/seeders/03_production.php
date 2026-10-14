<?php
/**
 * دانه‌گذار ۰۳ — تولید: مواد اولیه، عیب‌ها، سفارش‌های بافت در وضعیت‌های مختلف،
 * بازرسی کیفیت، کدهای اصالت و فیش‌های دستمزد
 */

declare(strict_types=1);

use App\Modules\Finance\Services\PayrollService;
use App\Modules\Production\Models\DefectType;
use App\Modules\Production\Models\Material;
use App\Modules\Production\Models\WorkOrder;
use App\Modules\Production\Services\ProductionService;
use Core\CodeGenerator;
use Core\Database;
use Core\Jalali;

return static function (): void {
    if ((int) (Database::selectOne('SELECT COUNT(*) AS c FROM materials')['c'] ?? 0) > 0) {
        echo "  (تولید پیش‌تر دانه‌گذاری شده — رد شد)\n";
        return;
    }

    /* ── مواد اولیه (RM-...) ── */
    $materials = [
        ['RM-COT-W01', 'پنبهٔ ریسیدهٔ سفید', 'cotton', 'kg', 320_000, 480, 100, 'LOC-MAIN-A1-01'],
        ['RM-YRN-R01', 'نخ پنبهٔ رنگ‌شده — قرمز روناس', 'yarn', 'kg', 540_000, 95, 40, 'LOC-MAIN-A2-03'],
        ['RM-YRN-I01', 'نخ پنبهٔ رنگ‌شده — نیلی', 'yarn', 'kg', 560_000, 60, 40, 'LOC-MAIN-A2-04'],
        ['RM-DYE-R01', 'رنگ طبیعی روناس', 'dye', 'kg', 1_800_000, 12, 8, 'LOC-MAIN-B1-01'],
        ['RM-DYE-I01', 'رنگ طبیعی نیل', 'dye', 'kg', 2_400_000, 5, 6, 'LOC-MAIN-B1-02'],
        ['RM-WOL-K01', 'پشم رنگ‌شده — گردویی', 'wool', 'kg', 780_000, 38, 20, 'LOC-MAIN-A3-01'],
        ['RM-PKG-K01', 'کارتن بسته‌بندی فرت', 'packaging', 'count', 18_000, 650, 300, 'LOC-MAIN-C1-05'],
    ];
    $materialIds = [];
    foreach ($materials as [$code, $name, $cat, $unit, $price, $stock, $min, $loc]) {
        $m = Material::create([
            'code' => $code, 'name_fa' => $name, 'category' => $cat, 'unit' => $unit,
            'price_per_unit' => $price, 'stock' => $stock, 'min_stock' => $min,
            'location_code' => $loc, 'is_active' => 1,
        ]);
        $materialIds[$code] = (int) $m->id();
    }

    /* ── انواع عیب (DEF-...) ── */
    $defects = [
        ['DEF-WVP-01', 'تراکم ناهمسان پود', 'WVP', 'major'],
        ['DEF-WVP-02', 'پارگی تار', 'WVP', 'critical'],
        ['DEF-CLR-01', 'ناهمگونی رنگ', 'CLR', 'major'],
        ['DEF-CLR-02', 'پخش‌شدگی رنگ در شست‌وشو', 'CLR', 'critical'],
        ['DEF-STC-01', 'دوخت لبهٔ نامنظم', 'STC', 'minor'],
        ['DEF-DIM-01', 'انحراف ابعاد', 'DIM', 'major'],
    ];
    $defectIds = [];
    foreach ($defects as [$code, $name, $cat, $sev]) {
        $d = DefectType::create(['code' => $code, 'name_fa' => $name, 'category' => $cat, 'severity' => $sev]);
        $defectIds[$code] = (int) $d->id();
    }

    /* ── سفارش‌های تولید در وضعیت‌های مختلف ── */
    $weaverIds = array_map(fn ($r) => (int) $r['id'], Database::select('SELECT id FROM weavers ORDER BY id LIMIT 8'));
    $variantIds = array_map(fn ($r) => (int) $r['id'], Database::select('SELECT id FROM product_variants ORDER BY id'));
    $pickVariant = fn (int $i) => $variantIds[$i % count($variantIds)];
    $year = Jalali::currentYear();

    $wos = [];

    // ۱) در حال بافت — با مواد توزیع‌شده
    $wo = WorkOrder::create([
        'code' => CodeGenerator::workOrderCode($year), 'variant_id' => $pickVariant(0),
        'weaver_id' => $weaverIds[0], 'quantity' => 4, 'unit_wage' => 320_000,
        'status' => 'draft', 'priority' => 'high',
        'due_date' => date('Y-m-d', strtotime('+12 days')),
        'note' => 'سفارش مشتری حقوقی — هتل آپارتمان فردوسی مشهد',
    ]);
    $wo->transitionTo('assigned');
    ProductionService::distributeMaterial($wo, $materialIds['RM-YRN-R01'], 6.5, 'تحویل مرحلهٔ اول');
    ProductionService::distributeMaterial($wo, $materialIds['RM-COT-W01'], 3, 'پنبهٔ پود');
    $wo->transitionTo('materials_received');
    $wo->transitionTo('weaving');
    $wos[] = $wo;

    // ۲) تحویل‌شده در انتظار بازرسی
    $wo2 = WorkOrder::create([
        'code' => CodeGenerator::workOrderCode($year), 'variant_id' => $pickVariant(3),
        'weaver_id' => $weaverIds[1], 'quantity' => 2, 'unit_wage' => 260_000,
        'status' => 'draft', 'priority' => 'normal',
        'due_date' => date('Y-m-d', strtotime('+2 days')),
    ]);
    $wo2->transitionTo('assigned');
    ProductionService::distributeMaterial($wo2, $materialIds['RM-WOL-K01'], 1.8, 'پشم گردویی برای شال');
    $wo2->transitionTo('materials_received');
    $wo2->transitionTo('weaving');
    $wo2->transitionTo('delivered');
    $wos[] = $wo2;

    // ۳ و ۴) تکمیل‌شده با QC قبول، کد اصالت و فیش دستمزد
    foreach ([[2, 6, 300_000], [5, 10, 190_000]] as [$weaverIdx, $qty, $wage]) {
        $woX = WorkOrder::create([
            'code' => CodeGenerator::workOrderCode($year), 'variant_id' => $pickVariant($weaverIdx),
            'weaver_id' => $weaverIds[$weaverIdx], 'quantity' => $qty, 'unit_wage' => $wage,
            'status' => 'draft', 'priority' => 'normal',
            'due_date' => date('Y-m-d', strtotime('-5 days')),
        ]);
        $woX->transitionTo('assigned');
        ProductionService::distributeMaterial($woX, $materialIds['RM-COT-W01'], $qty * 0.9, 'پنبهٔ سفید');
        $woX->transitionTo('materials_received');
        $woX->transitionTo('weaving');
        $woX->transitionTo('delivered');
        ProductionService::inspect($woX, 'passed', null, 92, 'بافت یکنواخت، تراکم پود استاندارد، دوخت لبه تمیز.');
        // رویداد qc_passed دستی (شنونده‌ها در HTTP ثبت می‌شوند؛ در CLI seeder مستقیم صدا می‌زنیم)
        ProductionService::issueAuthenticityCodes($woX);
        PayrollService::issueForWorkOrder($woX);
        $wos[] = $woX;
    }

    // پرداخت یکی از فیش‌ها (نمونهٔ تسویه)
    $paidRow = Database::selectOne('SELECT id FROM payments WHERE status = "pending" ORDER BY id LIMIT 1');
    if ($paidRow) {
        PayrollService::markPaid(\App\Modules\Finance\Models\Payment::find((int) $paidRow['id']));
    }

    // ۵) در صف تخصیص (پیش‌نویس)
    WorkOrder::create([
        'code' => CodeGenerator::workOrderCode($year), 'variant_id' => $pickVariant(8),
        'weaver_id' => null, 'quantity' => 5, 'unit_wage' => 0,
        'status' => 'draft', 'priority' => 'low',
        'due_date' => date('Y-m-d', strtotime('+40 days')),
        'note' => 'تولید برای انبار — فصل پاییز',
    ]);

    echo '  → ' . fa('5') . " سفارش تولید، " . fa((string) count($materials)) . " مادهٔ اولیه، کدهای اصالت و فیش دستمزد نمونه\n";
};
