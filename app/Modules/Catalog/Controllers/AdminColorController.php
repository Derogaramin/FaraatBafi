<?php
/**
 * کنترلر کتابخانهٔ رنگ — کد سه‌نویسه‌ای (حرف خانواده + دو رقم)
 * کد به‌صورت خودکار بر اساس خانواده تولید می‌شود (R01, R02, I01, …)
 */

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\Color;
use Core\CodeGenerator;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

final class AdminColorController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('Catalog::admin.colors', [
            'title'  => 'کتابخانهٔ رنگ',
            'colors' => Color::builder()->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'family_letter' => 'required|regex:/^[A-Za-z]$/',
            'name_fa'       => 'required|min:2|max:80',
            'hex'           => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'family'        => 'max:40',
        ], ['family_letter' => 'حرف خانوادهٔ رنگ', 'name_fa' => 'نام رنگ', 'hex' => 'کد هگز', 'family' => 'خانواده']);

        $code = CodeGenerator::nextColorCode((string) $data['family_letter']);
        if (Color::findBy('code', $code) !== null) {
            Session::flash('error', 'رنگی با کد ' . $code . ' وجود دارد.');
            return $this->redirect(url('admin/catalog/colors'));
        }
        $color = Color::create([
            'code'       => $code,
            'name_fa'    => $data['name_fa'],
            'hex'        => $data['hex'],
            'family'     => $data['family'] ?? null,
            'is_natural' => $request->bool('is_natural') ? 1 : 0,
            'is_active'  => 1,
        ]);
        Session::flash('success', 'رنگ «' . $color->name_fa . '» با کد ' . $code . ' ثبت شد. (id=' . $color->id() . ')');
        return $this->redirect(url('admin/catalog/colors'));
    }

    public function toggle(Request $request): Response
    {
        $color = Color::find($request->int('id'));
        if ($color !== null) {
            $color->update(['is_active' => $color->is_active ? 0 : 1]);
        }
        return $this->redirect(url('admin/catalog/colors'));
    }
}
