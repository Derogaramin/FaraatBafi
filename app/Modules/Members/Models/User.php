<?php
/**
 * مدل User — ماژول Members
 * جدول: users
 */

declare(strict_types=1);

namespace App\Modules\Members\Models;

use Core\Model;

final class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'name',
        'username',
        'phone',
        'email',
        'password_hash',
        'avatar',
        'status',
    ];
    protected static array $casts = [];

    /** نقش کاربر بر اساس پیشوند کد FUCS */
    public function role(): string
    {
        $prefix = strtoupper(substr((string) ($this->attributes['code'] ?? ''), 0, 1));
        return \Core\Auth::PREFIX_ROLE[$prefix] ?? 'guest';
    }

    public function roleLabel(): string
    {
        return \Core\Auth::ROLE_LABELS[$this->role()] ?? 'مهمان';
    }

    public function is_active(): bool
    {
        return ($this->attributes['status'] ?? '') === 'active';
    }
}
