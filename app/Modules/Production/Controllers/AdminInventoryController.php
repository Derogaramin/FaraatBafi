<?php
/**
 * کنترلر انبار — مواد اولیه، حرکت‌ها، انواع عیب
 */

declare(strict_types=1);

namespace App\Modules\Production\Controllers;

use App\Modules\Production\Models\DefectType;
use App\Modules\Production\Models\Material;
use App\Modules\Production\Services\ProductionService;
use Core\CodeGenerator;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

final class AdminInventoryController extends Controller
{
    public function materials(Request $request): Response
    {
        return $this->view('Production::admin.materials', [
            'title'     => 'انبار مواد اولیه',
            'materials' => Material::builder()->orderBy('name_fa')->get(),
        ]);
    }

    /** ثبت مادهٔ اولیهٔ جدید — کد RM-XXX-Ynn */
    public function storeMaterial(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name_fa'        => 'required|min:2|max:120',
            'category'       => 'required|in:yarn,dye,cotton,wool,silk,packaging,other',
            'unit'           => 'required|in:kg,gr,m,count,l',
            'family'         => 'required|regex:/^[A-Z]{3}$/',
            'color_letter'   => 'required|regex:/^[A-Z]$/',
            'color_number'   => 'required|integer|between:1,99',
            'price_per_unit' => 'required|numeric|min:0',
            'stock'          => 'numeric|min:0',
            'min_stock'      => 'numeric|min:0',
            'location_code'  => 'regex:/^LOC-[A-Z]+-[A-Z][0-9]-[0-9]{2}$/',
        ], [
            'name_fa'      => 'نام ماده',
            'family'       => 'خانواده (۳ حرف لاتین)',
            'color_letter' => 'حرف کد رنگ',
            'color_number' => 'رقم کد رنگ',
        ]);

        // کد FUCS: RM-COT-R01
        $code = sprintf('RM-%s-%s%02d', strtoupper((string) $data['family']), strtoupper((string) $data['color_letter']), (int) $data['color_number']);
        if (!CodeGenerator::validate($code, 'raw_material')) {
            Session::flash('error', 'کد تولیدشده نامعتبر است: ' . $code);
            return $this->redirect(url('admin/inventory/materials'));
        }
        if (Material::findBy('code', $code) !== null) {
            Session::flash('error', 'ماده‌ای با کد «' . $code . '» پیش‌تر ثبت شده است.');
            return $this->redirect(url('admin/inventory/materials'));
        }

        $material = Material::create([
            'code'           => $code,
            'name_fa'        => $data['name_fa'],
            'category'       => $data['category'],
            'unit'           => $data['unit'],
            'price_per_unit' => (int) en_digits((string) $data['price_per_unit']),
            'stock'          => (float) en_digits((string) ($data['stock'] ?? 0)),
            'min_stock'      => (float) en_digits((string) ($data['min_stock'] ?? 0)),
            'location_code'  => ($data['location_code'] ?? '') !== '' ? $data['location_code'] : null,
            'is_active'      => 1,
        ]);
        Session::flash('success', 'مادهٔ «' . $material->name_fa . '» با کد ' . $code . ' ثبت شد. (id=' . $material->id() . ')');
        return $this->redirect(url('admin/inventory/materials'));
    }

    /** اصلاح دستی موجودی */
    public function adjust(Request $request): Response
    {
        $material = Material::find($request->int('id'));
        if ($material === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'stock' => 'required|numeric|min:0',
            'note'  => 'max:255',
        ], ['stock' => 'موجودی جدید']);

        ProductionService::adjustMaterialStock($material, (float) en_digits((string) $data['stock']), $data['note'] ?? null);
        Session::flash('success', 'موجودی «' . $material->name_fa . '» به‌روزرسانی و در حرکت‌های انبار ثبت شد.');
        return $this->redirect(url('admin/inventory/materials'));
    }

    public function movements(Request $request): Response
    {
        $perPage = 20;
        $page = max(1, $request->int('page', 1));
        $total = (int) (Database::selectOne('SELECT COUNT(*) AS c FROM inventory_movements')['c'] ?? 0);
        $movements = Database::select(
            'SELECT im.*, m.name_fa AS material_name, m.code AS material_code, m.unit,
                    pv.sku, u.name AS user_name
             FROM inventory_movements im
             LEFT JOIN materials m ON m.id = im.material_id
             LEFT JOIN product_variants pv ON pv.id = im.variant_id
             LEFT JOIN users u ON u.id = im.user_id
             ORDER BY im.id DESC
             LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage)
        );
        return $this->view('Production::admin.movements', [
            'title'     => 'حرکت‌های انبار',
            'movements' => $movements,
            'paginator' => new \Core\Paginator($movements, $total, $perPage, $page),
        ]);
    }

    public function defects(Request $request): Response
    {
        return $this->view('Production::admin.defects', [
            'title'   => 'انواع عیب بافت',
            'defects' => DefectType::builder()->orderBy('code')->get(),
        ]);
    }

    /** ثبت نوع عیب — کد DEF-XXX-nn */
    public function storeDefect(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name_fa'  => 'required|min:2|max:120',
            'category' => 'required|regex:/^[A-Z]{3}$/',
            'severity' => 'required|in:minor,major,critical',
        ], ['name_fa' => 'نام عیب', 'category' => 'دسته (۳ حرف لاتین)']);

        $category = strtoupper((string) $data['category']);
        $row = Database::selectOne(
            "SELECT MAX(CAST(SUBSTRING_INDEX(code, '-', -1) AS UNSIGNED)) AS mx FROM defect_types WHERE code LIKE ?",
            ["DEF-{$category}-%"]
        );
        $code = CodeGenerator::defectCode($category, ((int) ($row['mx'] ?? 0)) + 1);
        if (DefectType::findBy('code', $code) !== null) {
            Session::flash('error', 'عیبی با کد «' . $code . '» وجود دارد.');
            return $this->redirect(url('admin/inventory/defects'));
        }
        $defect = DefectType::create([
            'code'     => $code,
            'name_fa'  => $data['name_fa'],
            'category' => $category,
            'severity' => $data['severity'],
        ]);
        Session::flash('success', 'عیب «' . $defect->name_fa . '» با کد ' . $code . ' ثبت شد.');
        return $this->redirect(url('admin/inventory/defects'));
    }
}
