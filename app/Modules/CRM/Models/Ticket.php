<?php
/**
 * مدل Ticket — ماژول CRM
 * جدول: tickets
 */

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use Core\Model;

final class Ticket extends Model
{
    protected static string $table = 'tickets';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'user_id',
        'subject',
        'body',
        'category',
        'priority',
        'status',
        'order_code',
    ];
    protected static array $casts = [];

    public function replies(): array
    {
        return TicketReply::query()->where('ticket_id', $this->id())->orderBy('id', 'ASC')->get();
    }

    public function statusLabel(): string
    {
        return \Core\Statuses::label('ticket', (string) ($this->attributes['status'] ?? ''));
    }
}
