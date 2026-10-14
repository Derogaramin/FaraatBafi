<?php
/**
 * مدل VoteBallot — ماژول Admin
 * جدول: vote_ballots
 */

declare(strict_types=1);

namespace App\Modules\Admin\Models;

use Core\Model;

final class VoteBallot extends Model
{
    protected static string $table = 'vote_ballots';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'vote_id',
        'user_id',
        'choice',
        'voted_at',
    ];
    protected static array $casts = [];
}
