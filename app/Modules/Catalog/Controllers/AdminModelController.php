<?php
/**
 * کنترلر مدیریت کاتالوگ — مدل‌های فنی (کد FUCS خودکار)
 */

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\ProductModel;
use App\Modules\Catalog\Models\ProductType;
use Core\CodeGenerator;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;

final class AdminModelController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->str('q');
        $where = '1=1';
        $bindings = [];
        if ($q !== '') {
            $where = '(pm.code LIKE ? OR pm.name_fa LIKE ?)';
            array_push($bindings, "%{$q}%", "%{$q}%");
        }
        $models = Database::select(
            "SELECT pm.*, pt.name_fa AS type_name, pt.code AS type_code,
                    (SELECT COUNT(*) FROM product_variants pv WHERE pv.model_id = pm.id) AS variants_count
             FROM product_models pm
             JOIN product_types pt ON pt.id = pm.type_id
             WHERE {$where}
             ORDER BY pm.id DESC",
            $bindings
        );
        return $this->view('Catalog::admin.models', [
            'title'  => 'مدل‌های فنی',
            'models' => $models,
            'q'      => $q,
            'weaves' => CodeGenerator::WEAVES,
            'knots'  => CodeGenerator::KNOTS,
            'stitches' => CodeGenerator::STITCHES,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('Catalog::admin.model-form', [
            'title'  => 'مدل فنی جدید',
            'types'  => ProductType::builder()->where('is_active', 1)->orderBy('sort_order')->get(),
            'model'  => null,
            'weaves' => CodeGenerator::WEAVES,
            'knots'  => CodeGenerator::KNOTS,
            'stitches' => CodeGenerator::STITCHES,
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validateModel($request);
        try {
            // کد مدل فنی بر اساس FUCS تولید می‌شود: [TYPE]-[WEAVE][KNOT][STITCH]-CH[XX]
            $code = CodeGenerator::productModelCode(
                (string) $data['type_code'],
                (string) $data['weave'],
                (string) $data['knot'],
                (string) $data['stitch'],
                (float) $data['chelle']
            );
        } catch (\Throwable $e) {
            return $this->backWith(url('admin/catalog/models/create'), 'error', $e->getMessage());
        }
        if (ProductModel::findBy('code', $code) !== null) {
            return $this->backWith(url('admin/catalog/models/create'), 'error', 'مدلی با همین مشخصات فنی و کد «' . $code . '» پیش‌تر ثبت شده است.');
        }
        unset($data['type_code']);
        $model = ProductModel::create($data + ['code' => $code, 'is_active' => 1]);

        return $this->backWith(url('admin/catalog/models'), 'success', 'مدل فنی با کد «' . $model->code . '» ثبت شد. (id=' . $model->id() . ')');
    }

    public function edit(Request $request): Response
    {
        $model = ProductModel::find($request->int('id'));
        if ($model === null) {
            throw new \Core\HttpException(404);
        }
        return $this->view('Catalog::admin.model-form', [
            'title'  => 'ویرایش مدل ' . $model->code,
            'types'  => ProductType::builder()->where('is_active', 1)->orderBy('sort_order')->get(),
            'model'  => $model->attributes(),
            'weaves' => CodeGenerator::WEAVES,
            'knots'  => CodeGenerator::KNOTS,
            'stitches' => CodeGenerator::STITCHES,
        ]);
    }

    public function update(Request $request): Response
    {
        $model = ProductModel::find($request->int('id'));
        if ($model === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validateModel($request, (int) $model->id());
        // طبق مستندات: کد مدل تابع ویژگی‌های فنی است — اگر ویژگی عوض شود، کد باید بازسازی شود
        $code = CodeGenerator::productModelCode(
            (string) $data['type_code'],
            (string) $data['weave'],
            (string) $data['knot'],
            (string) $data['stitch'],
            (float) $data['chelle']
        );
        $conflict = ProductModel::findBy('code', $code);
        if ($conflict !== null && $conflict->id() !== $model->id()) {
            return $this->backWith(url('admin/catalog/models/' . $model->id() . '/edit'), 'error', 'مدل دیگری با کد «' . $code . '» وجود دارد.');
        }
        unset($data['type_code']);
        $model->update($data + ['code' => $code]);

        return $this->backWith(url('admin/catalog/models'), 'success', 'مدل به‌روزرسانی شد — کد فعلی: ' . $code);
    }

    public function toggle(Request $request): Response
    {
        $model = ProductModel::find($request->int('id'));
        if ($model !== null) {
            $model->update(['is_active' => $model->is_active ? 0 : 1]);
        }
        return $this->backWith(url('admin/catalog/models'), 'info', 'وضعیت فعال‌بودن مدل تغییر کرد.');
    }

    /** @return array دادهٔ اعتبارسنجی‌شده به‌همراه type_code */
    private function validateModel(Request $request, ?int $ignoreId = null): array
    {
        $data = $this->validate($request->all(), [
            'type_id'     => 'required|integer|exists:product_types,id',
            'name_fa'     => 'required|min:3|max:160',
            'weave'       => 'required|in:1L,2L,3L,4L',
            'knot'        => 'required|in:B,G',
            'stitch'      => 'required|in:NS,STD,2N,R,CUST',
            'chelle'      => 'required|numeric|between:1,99',
            'base_price'  => 'required|numeric|min:0',
            'weight_gr'   => 'integer|min:0',
            'size_cm'     => 'max:60',
            'description' => 'max:5000',
        ], [
            'type_id'    => 'نوع محصول',
            'name_fa'    => 'نام مدل',
            'weave'      => 'پودگذاری',
            'knot'       => 'گره',
            'stitch'     => 'دوخت',
            'chelle'     => 'چله',
            'base_price' => 'قیمت پایه',
            'weight_gr'  => 'وزن',
            'size_cm'    => 'ابعاد',
        ]);
        $type = ProductType::find((int) $data['type_id']);
        $data['type_code'] = (string) $type->code;
        $data['chelle'] = en_digits((string) $data['chelle']);
        $data['base_price'] = (int) en_digits((string) $data['base_price']);
        $data['weight_gr'] = $data['weight_gr'] !== '' ? (int) $data['weight_gr'] : null;
        return $data;
    }

    private function backWith(string $to, string $type, string $message): Response
    {
        \Core\Session::flash($type, $message);
        return $this->redirect($to);
    }
}
