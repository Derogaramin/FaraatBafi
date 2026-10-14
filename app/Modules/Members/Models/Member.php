<?php
/**
 * مدل Member — ماژول Members
 * جدول: members
 */

declare(strict_types=1);

namespace App\Modules\Members\Models;

use Core\Model;

final class Member extends Model
{
    protected static string $table = 'members';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'user_id',
        'code',
        'national_id',
        'position',
        'shares',
        'share_value',
        'join_date',
        'note',
    ];
    protected static array $casts = [
        'shares' => 'int',
        'share_value' => 'int',
    ];
}
