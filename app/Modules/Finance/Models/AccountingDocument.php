<?php
/**
 * مدل AccountingDocument — ماژول Finance
 * جدول: accounting_documents
 */

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use Core\Model;

final class AccountingDocument extends Model
{
    protected static string $table = 'accounting_documents';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static array $fillable = [
        'code',
        'title',
        'kind',
        'amount',
        'doc_date',
        'ref_type',
        'ref_id',
        'created_by',
        'note',
    ];
    protected static array $casts = [
        'amount' => 'int',
    ];
}
