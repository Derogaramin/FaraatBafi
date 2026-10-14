<?php
/**
 * کنترلر واریانت‌های فروشگاهی (SKU)
 *
 * SKU = [MODEL_CODE]-[COLOR_CODE]-[PATTERN_CODE]
 * مثال: HT-2LBSTD-CH125-R01-P0003
 */

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Pattern;
use App\Modules\Catalog\Models\ProductModel;
use App\Modules\Catalog\Models\ProductVariant;
use Core\CodeGenerator;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Upload;

final class AdminVariantController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->str('q');
        $where = '1=1';
        $bindings = [];
        if ($q !== '') {
            $where = '(pv.sku LIKE ? OR pm.name_fa LIKE ? OR c.name_fa LIKE ?)';
            $like = '%' . $q . '%';
            array_push($bindings, $like, $like, $like);
        }
        $variants = Database::select(
            "SELECT pv.*, pm.name_fa AS model_name, pm.code AS model_code,
                    c.name_fa AS color_name, c.hex, p.name_fa AS pattern_name
             FROM product_variants pv
             JOIN product_models pm ON pm.id = pv.model_id
             JOIN colors c ON c.id = pv.color_id
             JOIN patterns p ON p.id = pv.pattern_id
             WHERE {$where}
             ORDER BY pv.id DESC
             LIMIT 200",
            $bindings
        );
        return $this->view('Catalog::admin.variants', [
            'title'    => 'محصولات (SKU)',
            'variants' => $variants,
            'q'        => $q,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('Catalog::admin.variant-form', [
            'title'    => 'واریانت جدید (SKU)',
            'models'   => ProductModel::builder()->where('is_active', 1)->orderBy('code')->get(),
            'colors'   => Color::builder()->where('is_active', 1)->orderBy('code')->get(),
            'patterns' => Pattern::builder()->where('is_active', 1)->orderBy('code')->get(),
            'variant'  => null,
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'model_id'   => 'required|integer|exists:product_models,id',
            'color_id'   => 'required|integer|exists:colors,id',
            'pattern_id' => 'required|integer|exists:patterns,id',
            'price'      => 'required|numeric|min:1000',
            'cost'       => 'numeric|min:0',
            'stock'      => 'integer|min:0',
        ], [
            'model_id'   => 'مدل فنی',
            'color_id'   => 'رنگ',
            'pattern_id' => 'طرح',
            'price'      => 'قیمت فروش',
            'cost'       => 'بهای تمام‌شده',
            'stock'      => 'موجودی',
        ]);

        $model = ProductModel::find((int) $data['model_id']);
        $color = Color::find((int) $data['color_id']);
        $pattern = Pattern::find((int) $data['pattern_id']);

        try {
            $sku = CodeGenerator::sku((string) $model->code, (string) $color->code, (string) $pattern->code);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect(url('admin/catalog/variants/create'));
        }
        if (ProductVariant::findBy('sku', $sku) !== null) {
            Session::flash('error', 'واریانتی با SKU «' . $sku . '» پیش‌تر ثبت شده است.');
            return $this->redirect(url('admin/catalog/variants/create'));
        }

        $image = Upload::image($request->file('image'), 'products');
        $variant = ProductVariant::create([
            'sku'         => $sku,
            'model_id'    => (int) $data['model_id'],
            'color_id'    => (int) $data['color_id'],
            'pattern_id'  => (int) $data['pattern_id'],
            'price'       => (int) en_digits((string) $data['price']),
            'cost'        => (int) en_digits((string) ($data['cost'] ?? 0)),
            'stock'       => (int) ($data['stock'] ?? 0),
            'image'       => $image,
            'is_featured' => $request->bool('is_featured') ? 1 : 0,
            'is_active'   => 1,
        ]);

        // طبق مستندات: پاسخ ایجاد موجودیت باید id + کد تولیدشده را برگرداند
        Session::flash('success', 'واریانت ثبت شد — SKU: «' . $sku . '» (id=' . $variant->id() . ')');
        return $this->redirect(url('admin/catalog/variants'));
    }

    public function edit(Request $request): Response
    {
        $variant = ProductVariant::find($request->int('id'));
        if ($variant === null) {
            throw new \Core\HttpException(404);
        }
        return $this->view('Catalog::admin.variant-form', [
            'title'    => 'ویرایش ' . $variant->sku,
            'models'   => ProductModel::builder()->where('is_active', 1)->orderBy('code')->get(),
            'colors'   => Color::builder()->where('is_active', 1)->orderBy('code')->get(),
            'patterns' => Pattern::builder()->where('is_active', 1)->orderBy('code')->get(),
            'variant'  => $variant->attributes(),
        ]);
    }

    /** ویرایش: SKU ثابت است (تغییر طرح/رنگ = واریانت جدید) — فقط قیمت/موجودی/تصویر */
    public function update(Request $request): Response
    {
        $variant = ProductVariant::find($request->int('id'));
        if ($variant === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'price' => 'required|numeric|min:1000',
            'cost'  => 'numeric|min:0',
            'stock' => 'integer|min:0',
        ], ['price' => 'قیمت فروش']);

        $image = Upload::image($request->file('image'), 'products');
        if ($image !== null) {
            Upload::delete($variant->image);
            $data['image'] = $image;
        }
        $data['price'] = (int) en_digits((string) $data['price']);
        $data['cost'] = (int) en_digits((string) ($data['cost'] ?? 0));
        $data['is_featured'] = $request->bool('is_featured') ? 1 : 0;
        $before = $variant->attributes();
        $variant->update($data);
        \Core\Audit::diff('variant', (int) $variant->id(), (string) $variant->sku, $before, $variant->attributes(), ['price', 'cost', 'stock', 'is_featured']);

        Session::flash('success', 'واریانت «' . $variant->sku . '» به‌روزرسانی شد.');
        return $this->redirect(url('admin/catalog/variants'));
    }

    public function toggle(Request $request): Response
    {
        $variant = ProductVariant::find($request->int('id'));
        if ($variant !== null) {
            $newState = $variant->is_active ? 0 : 1;
            $variant->update(['is_active' => $newState]);
            \Core\Audit::log('variant', (int) $variant->id(), (string) $variant->sku, 'is_active', (string) (int) !$newState, (string) $newState);
        }
        return $this->redirect(url('admin/catalog/variants'));
    }

    /** پیش‌نمایش زندهٔ SKU (AJAX) برای فرم ساخت واریانت */
    public function skuPreview(Request $request): Response
    {
        $model = ProductModel::find($request->int('model_id'));
        $color = Color::find($request->int('color_id'));
        $pattern = Pattern::find($request->int('pattern_id'));
        if ($model === null || $color === null || $pattern === null) {
            return $this->json(['ok' => false, 'sku' => null, 'exists' => false]);
        }
        $sku = CodeGenerator::sku((string) $model->code, (string) $color->code, (string) $pattern->code);
        return $this->json([
            'ok'     => true,
            'sku'    => $sku,
            'exists' => ProductVariant::findBy('sku', $sku) !== null,
        ]);
    }
}
