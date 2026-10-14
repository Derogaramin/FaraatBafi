<?php
/**
 * کلاس QueueWorker — پردازش صف رویدادها (event_queue)
 *
 * رویدادهای listed در config('app.queued_events') به‌جای اجرای همگام،
 * در جدول صف درج و توسط `php bin/faraat queue:work` پردازش می‌شوند.
 * شنونده‌ها باید payload سریال‌شدنی (بدون آبجکت) دریافت کنند.
 */

declare(strict_types=1);

namespace Core;

final class QueueWorker
{
    /** پردازش حداکثر $max رویداد — تعداد پردازش‌شده‌ها */
    public function runBatch(int $max = 50): int
    {
        $processed = 0;
        $rows = Database::select(
            'SELECT * FROM event_queue WHERE status = "pending" ORDER BY id LIMIT ' . max(1, min(500, $max))
        );
        foreach ($rows as $row) {
            // قفل ردیف (جلوگیری از پردازش موازی دو کارگر)
            $locked = Database::update(
                'UPDATE event_queue SET status = "processing", attempts = attempts + 1 WHERE id = ? AND status = "pending"',
                [$row['id']]
            );
            if ($locked === 0) {
                continue;
            }
            $payload = json_decode((string) $row['payload'], true) ?: [];
            try {
                Events::dispatch((string) $row['event'], $payload, true); // forceSync — از صف‌گذاری مجدد جلوگیری شود
                Database::update(
                    'UPDATE event_queue SET status = "done", processed_at = NOW(), last_error = NULL WHERE id = ?',
                    [$row['id']]
                );
                $processed++;
            } catch (\Throwable $e) {
                $attempts = (int) $row['attempts'] + 1;
                $status = $attempts >= 3 ? 'failed' : 'pending'; // ۳ تلاش سپس شکست
                Database::update(
                    'UPDATE event_queue SET status = ?, last_error = ? WHERE id = ?',
                    [$status, mb_substr($e->getMessage(), 0, 1000), $row['id']]
                );
                App::log('queue', "event {$row['event']}#{$row['id']} failed: " . $e->getMessage());
            }
        }
        return $processed;
    }

    /** آمار صف */
    public static function stats(): array
    {
        $rows = Database::select('SELECT status, COUNT(*) AS c FROM event_queue GROUP BY status');
        return array_column($rows, 'c', 'status');
    }
}
