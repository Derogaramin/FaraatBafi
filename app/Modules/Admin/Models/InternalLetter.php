<?php
/**
 * مدل InternalLetter — ماژول Admin
 * جدول: internal_letters
 */

declare(strict_types=1);

namespace App\Modules\Admin\Models;

use Core\Model;

final class InternalLetter extends Model
{
    protected static string $table = 'internal_letters';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'from_user_id',
        'to_user_id',
        'subject',
        'body',
        'kind',
        'is_read',
        'sent_at',
    ];
    protected static array $casts = [];
}
