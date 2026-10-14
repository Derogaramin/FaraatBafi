<?php
/**
 * کنترلر مدیریت وبلاگ
 */

declare(strict_types=1);

namespace App\Modules\Blog\Controllers;

use App\Modules\Blog\Models\Post;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Upload;

final class AdminPostController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('Blog::admin.index', [
            'title' => 'مدیریت وبلاگ',
            'posts' => Post::builder()->orderBy('id', 'DESC')->limit(100)->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('Blog::admin.form', ['title' => 'نوشتهٔ جدید', 'post' => null]);
    }

    public function store(Request $request): Response
    {
        $data = $this->rules($request);
        $image = Upload::image($request->file('image'), 'blog');

        $slug = str_slug((string) ($data['slug'] !== '' ? $data['slug'] : $data['title']));
        if (Post::findBy('slug', $slug) !== null) {
            $slug .= '-' . bin2hex(random_bytes(2));
        }

        $post = Post::create([
            'title'        => $data['title'],
            'slug'         => $slug,
            'excerpt'      => $data['excerpt'] ?? null,
            'body'         => $data['body'],
            'image'        => $image,
            'category'     => ($data['category'] ?? '') !== '' ? $data['category'] : 'blog',
            'tags'         => $data['tags'] ?? null,
            'author_id'    => Auth::id(),
            'status'       => $data['status'],
            'published_at' => $data['status'] === 'published' ? date('Y-m-d H:i:s') : null,
        ]);
        Session::flash('success', 'نوشتهٔ «' . $post->title . '» ذخیره شد.');
        return $this->redirect(url('admin/blog'));
    }

    public function edit(Request $request): Response
    {
        $post = Post::find($request->int('id'));
        if ($post === null) {
            throw new \Core\HttpException(404);
        }
        return $this->view('Blog::admin.form', ['title' => 'ویرایش نوشته', 'post' => $post->attributes()]);
    }

    public function update(Request $request): Response
    {
        $post = Post::find($request->int('id'));
        if ($post === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->rules($request, (int) $post->id());
        $image = Upload::image($request->file('image'), 'blog');
        if ($image !== null) {
            Upload::delete($post->image);
            $data['image'] = $image;
        }
        if ($data['status'] === 'published' && empty($post->published_at)) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        $post->update($data);
        Session::flash('success', 'نوشته به‌روزرسانی شد.');
        return $this->redirect(url('admin/blog'));
    }

    public function delete(Request $request): Response
    {
        $post = Post::find($request->int('id'));
        if ($post !== null) {
            Upload::delete($post->image);
            $post->delete();
            Session::flash('info', 'نوشته حذف شد.');
        }
        return $this->redirect(url('admin/blog'));
    }

    private function rules(Request $request, ?int $ignoreId = null): array
    {
        $data = $this->validate($request->all(), [
            'title'    => 'required|min:3|max:200',
            'slug'     => 'slug|max:220',
            'excerpt'  => 'max:400',
            'body'     => 'required|min:20',
            'category' => 'max:60',
            'tags'     => 'max:255',
            'status'   => 'required|in:draft,published',
        ], ['title' => 'عنوان', 'body' => 'متن', 'slug' => 'نامک']);
        $data['slug'] = (string) ($data['slug'] ?? '');
        return $data;
    }
}
