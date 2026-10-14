<?php
/**
 * کنترلر انواع محصول (HT, DS, SH, …)
 */

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\ProductType;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

final class AdminTypeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('Catalog::admin.types', [
            'title' => 'انواع محصول',
            'types' => ProductType::builder()->orderBy('sort_order')->get(),
            'counts' => \Core\Database::select('SELECT type_id, COUNT(*) AS c FROM product_models GROUP BY type_id'),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'code'        => 'required|regex:/^[A-Z]{2,3}$/|unique:product_types,code',
            'name_fa'     => 'required|min:2|max:80',
            'name_en'     => 'max:80',
            'description' => 'max:1000',
            'sort_order'  => 'integer|min:0',
        ], ['code' => 'کد نوع', 'name_fa' => 'نام فارسی']);

        $type = ProductType::create([
            'code'        => strtoupper((string) $data['code']),
            'name_fa'     => $data['name_fa'],
            'name_en'     => $data['name_en'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'is_active'   => 1,
        ]);
        Session::flash('success', 'نوع «' . $type->name_fa . '» با کد ' . $type->code . ' ثبت شد. (id=' . $type->id() . ')');
        return $this->redirect(url('admin/catalog/types'));
    }

    public function toggle(Request $request): Response
    {
        $type = ProductType::find($request->int('id'));
        if ($type !== null) {
            $type->update(['is_active' => $type->is_active ? 0 : 1]);
        }
        return $this->redirect(url('admin/catalog/types'));
    }
}
