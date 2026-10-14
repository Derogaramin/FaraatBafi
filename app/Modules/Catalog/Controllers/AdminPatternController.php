<?php
/**
 * کنترلر کتابخانهٔ طرح — کد P + چهار رقم صعودی (خودکار)
 */

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\Pattern;
use Core\CodeGenerator;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Upload;

final class AdminPatternController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('Catalog::admin.patterns', [
            'title'    => 'کتابخانهٔ طرح',
            'patterns' => Pattern::builder()->orderBy('code')->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('Catalog::admin.pattern-form', ['title' => 'طرح جدید', 'pattern' => null]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name_fa'     => 'required|min:2|max:120',
            'designer'    => 'max:120',
            'motif'       => 'max:60',
            'description' => 'max:2000',
        ], ['name_fa' => 'نام طرح', 'designer' => 'طراح', 'motif' => 'سبک نقش‌مایه']);

        $image = null;
        try {
            $image = Upload::image($request->file('image'), 'patterns');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Session::flashInput($data);
            return $this->redirect(url('admin/catalog/patterns/create'));
        }

        $pattern = Pattern::create([
            'code'        => CodeGenerator::nextPatternCode(),
            'name_fa'     => $data['name_fa'],
            'designer'    => $data['designer'] ?? null,
            'image'       => $image,
            'motif'       => $data['motif'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active'   => 1,
        ]);
        Session::flash('success', 'طرح «' . $pattern->name_fa . '» با کد ' . $pattern->code . ' ثبت شد. (id=' . $pattern->id() . ')');
        return $this->redirect(url('admin/catalog/patterns'));
    }

    public function edit(Request $request): Response
    {
        $pattern = Pattern::find($request->int('id'));
        if ($pattern === null) {
            throw new \Core\HttpException(404);
        }
        return $this->view('Catalog::admin.pattern-form', [
            'title'   => 'ویرایش طرح ' . $pattern->code,
            'pattern' => $pattern->attributes(),
        ]);
    }

    public function update(Request $request): Response
    {
        $pattern = Pattern::find($request->int('id'));
        if ($pattern === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'name_fa'     => 'required|min:2|max:120',
            'designer'    => 'max:120',
            'motif'       => 'max:60',
            'description' => 'max:2000',
        ], ['name_fa' => 'نام طرح']);

        $image = Upload::image($request->file('image'), 'patterns');
        if ($image !== null) {
            Upload::delete($pattern->image);
            $data['image'] = $image;
        }
        $pattern->update($data);
        Session::flash('success', 'طرح به‌روزرسانی شد.');
        return $this->redirect(url('admin/catalog/patterns'));
    }
}
