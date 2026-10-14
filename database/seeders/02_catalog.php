<?php
/**
 * دانه‌گذار ۰۲ — کاتالوگ محصولات بر اساس FUCS v3
 * انواع، رنگ‌های طبیعی، طرح‌ها، مدل‌های فنی و واریانت‌های SKU
 */

declare(strict_types=1);

use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Pattern;
use App\Modules\Catalog\Models\ProductModel;
use App\Modules\Catalog\Models\ProductType;
use App\Modules\Catalog\Models\ProductVariant;
use Core\CodeGenerator;
use Core\Database;

return static function (): void {
    if ((int) (Database::selectOne('SELECT COUNT(*) AS c FROM product_types')['c'] ?? 0) > 0) {
        echo "  (کاتالوگ پیش‌تر دانه‌گذاری شده — رد شد)\n";
        return;
    }

    /* ── انواع محصول ── */
    $types = [
        ['HT', 'حولهٔ حمام', 'Hamam Towel', 'حولهٔ دست‌باف پنبه‌ای با جذب آب بالا', 1],
        ['DS', 'دستمال و حولهٔ دستی', 'Hand/Dish Towel', 'دستمال‌های آشپزخانه و حولهٔ کوچک', 2],
        ['SH', 'شال', 'Shawl', 'شال‌های پشمی و پنبه‌ای با نقش‌مایهٔ خراسانی', 3],
        ['TN', 'تن‌پوش', 'Tunic', 'پوشاک سنتی دست‌باف', 4],
        ['MO', 'ملافه و روتختی', 'Bed Sheet', 'ملافهٔ پنبه‌ای بافت دستی', 5],
        ['FB', 'پارچهٔ فرت', 'Faraat Fabric', 'پارچهٔ سنتی فرت — طاقه‌ای', 6],
        ['HC', 'رومیزی و رانر', 'Tablecloth', 'رومیزی با حاشیهٔ دست‌بافت', 7],
    ];
    foreach ($types as [$code, $fa, $en, $desc, $order]) {
        ProductType::create(['code' => $code, 'name_fa' => $fa, 'name_en' => $en, 'description' => $desc, 'sort_order' => $order, 'is_active' => 1]);
    }

    /* ── رنگ‌های طبیعی ── */
    $colors = [
        ['R', 'قرمز روناس', '#8B2E3A', 'گرم', 1],
        ['R', 'لاکی', '#A63D4A', 'گرم', 1],
        ['I', 'نیلی', '#1A3B4C', 'سرد', 1],
        ['I', 'فیروزه‌ای', '#1BA8A1', 'سرد', 0],
        ['Y', 'زعفرانی', '#C68A3C', 'گرم', 1],
        ['Y', 'پوست پیازی', '#B0713A', 'گرم', 1],
        ['G', 'سبز گیاهی', '#4A6B4A', 'سرد', 1],
        ['G', 'یشمی', '#3F7D6E', 'سرد', 0],
        ['W', 'سفید پنبه', '#F5F0E6', 'خنثی', 0],
        ['K', 'قهوه‌ای گردو', '#5A4634', 'خنثی', 1],
        ['K', 'خاکی', '#C2A878', 'خنثی', 0],
        ['B', 'آبی آسمانی', '#5B87A5', 'سرد', 0],
    ];
    foreach ($colors as [$letter, $name, $hex, $family, $natural]) {
        Color::create([
            'code' => CodeGenerator::nextColorCode($letter),
            'name_fa' => $name, 'hex' => $hex, 'family' => $family,
            'is_natural' => $natural, 'is_active' => 1,
        ]);
    }

    /* ── طرح‌ها ── */
    $patterns = [
        ['ترنج خراسانی', 'استاد محمد دروگر', 'هندسی', 'نقش ترنج مرکزی با حاشیهٔ geometric خراسان'],
        ['بته‌جقه', 'مرحوم حاجیه بی‌بی', 'گیاهی', 'بتهٔ خمیدهٔ کلاسیک با گل‌های ریز'],
        ['خشتی', 'کارگاه نقشینه', 'هندسی', 'چیدمان خشتی نقش‌ها — الهام از معماری خراسان'],
        ['راه‌راه ترمه', 'کارگاه نقشینه', 'خطی', 'نوارهای رنگین عمودی به سبک ترمه'],
        ['گل مینا', 'زرگل احمدی', 'گیاهی', 'گل‌های پراکندهٔ مینا روی زمینهٔ ساده'],
        ['محرمات', 'استاد محمد دروگر', 'سنتی', 'نقش محرمات سنتی پارچهٔ خراسانی'],
    ];
    foreach ($patterns as [$name, $designer, $motif, $desc]) {
        Pattern::create([
            'code' => CodeGenerator::nextPatternCode(),
            'name_fa' => $name, 'designer' => $designer, 'motif' => $motif,
            'description' => $desc, 'is_active' => 1,
        ]);
    }

    /* ── مدل‌های فنی — کد خودکار با FUCS ── */
    $typeId = fn (string $code) => (int) ProductType::findBy('code', $code)->id();
    $models = [
        // [نوع, پود, گره, دوخت, چله, نام, قیمت پایه, وزن, ابعاد]
        ['HT', '2L', 'B', 'STD', 12.5, 'حولهٔ حمام دولا', 680_000, 480, '70×140'],
        ['HT', '3L', 'B', 'STD', 14.0, 'حولهٔ حمام سه‌لا ممتاز', 940_000, 620, '80×150'],
        ['DS', '1L', 'B', 'NS', 8.0, 'دستمال آشپزخانه تک‌لا', 180_000, 110, '40×60'],
        ['DS', '2L', 'B', 'STD', 10.0, 'حولهٔ دستی دولا', 260_000, 190, '50×90'],
        ['SH', '2L', 'G', 'STD', 10.0, 'شال گره‌دار خراسانی', 1_450_000, 380, '90×200'],
        ['SH', '1L', 'B', '2N', 8.5, 'شال سبک دو سوزنه', 890_000, 260, '70×180'],
        ['TN', '1L', 'B', 'STD', 9.0, 'تن‌پوش سنتی', 1_250_000, 420, 'L'],
        ['MO', '1L', 'B', 'STD', 14.0, 'ملافهٔ پنبه‌ای یک‌لا', 1_180_000, 750, '180×240'],
        ['FB', '2L', 'B', 'NS', 12.5, 'پارچهٔ فرت طاقه‌ای', 2_350_000, 900, 'طاقه ۱۰ متری'],
        ['HC', '2L', 'B', 'R', 10.0, 'رومیزی ریلی‌دوزی', 780_000, 320, '100×150'],
    ];
    $modelIds = [];
    foreach ($models as [$type, $weave, $knot, $stitch, $chelle, $name, $price, $weight, $size]) {
        $code = CodeGenerator::productModelCode($type, $weave, $knot, $stitch, $chelle);
        $model = ProductModel::create([
            'code' => $code, 'type_id' => $typeId($type), 'weave' => $weave, 'knot' => $knot,
            'stitch' => $stitch, 'chelle' => $chelle, 'name_fa' => $name,
            'base_price' => $price, 'weight_gr' => $weight, 'size_cm' => $size,
            'description' => 'بافت دستی روی دارِ زمینی/عمودی با پنبهٔ خراسان و رنگ‌های طبیعی.',
            'is_active' => 1,
        ]);
        $modelIds[$code] = (int) $model->id();
    }

    /* ── واریانت‌های فروشگاهی (SKU) ── */
    $colorByCode = [];
    foreach (Color::query()->get() as $c) { $colorByCode[$c['code']] = (int) $c['id']; }
    $patternByCode = [];
    foreach (Pattern::query()->get() as $p) { $patternByCode[$p['code']] = (int) $p['id']; }

    $variants = [
        // [مدل, رنگ, طرح, قیمت, موجودی, ویژه]
        ['HT-2LBSTD-CH125', 'R01', 'P0001', 690_000, 8, 1],
        ['HT-2LBSTD-CH125', 'W01', 'P0003', 640_000, 12, 1],
        ['HT-2LBSTD-CH125', 'I01', 'P0006', 690_000, 5, 0],
        ['HT-3LBSTD-CH140', 'K01', 'P0002', 960_000, 4, 1],
        ['HT-3LBSTD-CH140', 'G01', 'P0005', 960_000, 2, 0],
        ['DS-1LBNS-CH80', 'Y01', 'P0004', 185_000, 24, 0],
        ['DS-1LBNS-CH80', 'R01', 'P0004', 185_000, 18, 0],
        ['DS-2LBSTD-CH100', 'I02', 'P0003', 265_000, 14, 1],
        ['SH-2LGSTD-CH100', 'R01', 'P0002', 1_480_000, 3, 1],
        ['SH-2LGSTD-CH100', 'I01', 'P0006', 1_480_000, 2, 0],
        ['SH-1LB2N-CH85', 'K02', 'P0005', 910_000, 6, 0],
        ['TN-1LBSTD-CH90', 'W01', 'P0001', 1_270_000, 3, 0],
        ['MO-1LBSTD-CH140', 'W01', 'P0004', 1_190_000, 4, 0],
        ['FB-2LBNS-CH125', 'R01', 'P0006', 2_380_000, 2, 1],
        ['FB-2LBNS-CH125', 'Y02', 'P0002', 2_380_000, 1, 0],
        ['HC-2LBR-CH100', 'G02', 'P0005', 795_000, 7, 0],
        ['HC-2LBR-CH100', 'R02', 'P0003', 795_000, 0, 0],
        ['DS-2LBSTD-CH100', 'Y01', 'P0001', 265_000, 9, 0],
    ];
    foreach ($variants as [$modelCode, $colorCode, $patternCode, $price, $stock, $featured]) {
        $sku = CodeGenerator::sku($modelCode, $colorCode, $patternCode);
        ProductVariant::create([
            'sku' => $sku,
            'model_id' => $modelIds[$modelCode],
            'color_id' => $colorByCode[$colorCode],
            'pattern_id' => $patternByCode[$patternCode],
            'price' => $price,
            'cost' => (int) round($price * 0.58),
            'stock' => $stock,
            'is_featured' => $featured,
            'is_active' => 1,
        ]);
    }

    echo '  → ' . fa((string) count($variants)) . " واریانت SKU، " . fa((string) count($models)) . " مدل فنی، " . fa((string) count($colors)) . " رنگ، " . fa((string) count($patterns)) . " طرح\n";
};
