<?php
/**
 * کلاس Audit — ممیزی تغییرات فیلدهای حساس (نقد §۶٫۱)
 *
 * هر تغییر قیمت/موجودی/نرخ با before/after در field_audits ثبت می‌شود:
 *   Audit::log('variant', 12, 'HT-…', 'price', 690000, 720000);
 *   Audit::diff('weaver', 5, 'W001', $oldArray, $newArray, ['wage_rate','status']);
 */

declare(strict_types=1);

namespace Core;

final class Audit
{
    /** ثبت یک تغییر فیلد */
    public static function log(string $entity, int $entityId, ?string $entityCode, string $field, mixed $old, mixed $new, ?string $note = null): void
    {
        $oldStr = self::stringify($old);
        $newStr = self::stringify($new);
        if ($oldStr === $newStr) {
            return; // تغییر بی‌اثر ثبت نمی‌شود
        }
        try {
            Database::insert(
                'INSERT INTO field_audits (user_id, entity, entity_id, entity_code, field, old_value, new_value, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                [Auth::id(), $entity, $entityId, $entityCode, $field, $oldStr, $newStr]
            );
        } catch (\Throwable) {
            // در محیط CLI/تست بدون نشست یا قبل از مهاجرت، ممیزی مانع کار نشود
            App::log('audit', "missed {$entity}#{$entityId}.{$field}: {$oldStr} → {$newStr}");
        }
    }

    /**
     * مقایسهٔ دو آرایه و ثبت تغییرات فیلدهای مشخص.
     *
     * @param string[] $fields
     */
    public static function diff(string $entity, int $entityId, ?string $entityCode, array $old, array $new, array $fields): void
    {
        foreach ($fields as $field) {
            $oldValue = $old[$field] ?? null;
            $newValue = $new[$field] ?? null;
            if (self::stringify($oldValue) !== self::stringify($newValue)) {
                self::log($entity, $entityId, $entityCode, $field, $oldValue, $newValue);
            }
        }
    }

    /** آخرین تغییرات یک موجودیت */
    public static function forEntity(string $entity, int $entityId, int $limit = 20): array
    {
        try {
            return Database::select(
                'SELECT fa.*, u.name AS user_name FROM field_audits fa
                 LEFT JOIN users u ON u.id = fa.user_id
                 WHERE fa.entity = ? AND fa.entity_id = ?
                 ORDER BY fa.id DESC LIMIT ' . max(1, min(100, $limit)),
                [$entity, $entityId]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    private static function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }
}
