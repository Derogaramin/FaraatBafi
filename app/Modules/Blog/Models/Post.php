<?php
/**
 * مدل Post — ماژول Blog
 * جدول: posts
 */

declare(strict_types=1);

namespace App\Modules\Blog\Models;

use Core\Model;

final class Post extends Model
{
    protected static string $table = 'posts';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'image',
        'category',
        'tags',
        'author_id',
        'status',
        'views',
        'published_at',
    ];
    protected static array $casts = [
        'views' => 'int',
    ];
}
