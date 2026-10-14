<?php
/**
 * مدل TicketReply — ماژول CRM
 * جدول: ticket_replies
 */

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use Core\Model;

final class TicketReply extends Model
{
    protected static string $table = 'ticket_replies';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'ticket_id',
        'user_id',
        'body',
        'is_staff',
        'created_at',
    ];
    protected static array $casts = [];
}
