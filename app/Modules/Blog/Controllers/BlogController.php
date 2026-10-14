<?php
/**
 * کنترلر وبلاگ عمومی
 */

declare(strict_types=1);

namespace App\Modules\Blog\Controllers;

use App\Modules\Blog\Models\Post;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;

final class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->str('q');
        $where = ["p.status = 'published'"];
        $bindings = [];
        if ($q !== '') {
            $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.tags LIKE ?)';
            $like = '%' . $q . '%';
            array_push($bindings, $like, $like, $like);
        }
        $whereSql = implode(' AND ', $where);

        $perPage = 9;
        $page = max(1, $request->int('page', 1));
        $total = (int) (Database::selectOne("SELECT COUNT(*) AS c FROM posts p WHERE {$whereSql}", $bindings)['c'] ?? 0);
        $posts = Database::select(
            "SELECT p.*, u.name AS author_name
             FROM posts p LEFT JOIN users u ON u.id = p.author_id
             WHERE {$whereSql}
             ORDER BY p.published_at DESC
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $bindings
        );

        return $this->view('Blog::index', [
            'title'     => 'وبلاگ فرت — داستان بافت و رنگ',
            'posts'     => $posts,
            'q'         => $q,
            'paginator' => new \Core\Paginator($posts, $total, $perPage, $page),
        ]);
    }

    public function show(Request $request): Response
    {
        $slug = $request->params['slug'] ?? '';
        $post = Post::builder()->where('slug', $slug)->where('status', 'published')->first();
        if ($post === null) {
            throw new \Core\HttpException(404, 'نوشته یافت نشد.');
        }
        Database::update('UPDATE posts SET views = views + 1 WHERE id = ?', [$post->id()]);
        $authorId = $post->author_id;
        $author = $authorId ? Database::selectOne('SELECT name FROM users WHERE id = ?', [$authorId]) : null;

        $related = Database::select(
            "SELECT title, slug, image, excerpt, published_at FROM posts
             WHERE status='published' AND id != ? ORDER BY published_at DESC LIMIT 3",
            [$post->id()]
        );

        return $this->view('Blog::show', [
            'title'   => (string) $post->title,
            'post'    => $post->attributes(),
            'author'  => $author['name'] ?? 'تیم فرت',
            'related' => $related,
        ]);
    }
}
