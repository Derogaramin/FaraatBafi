<?php
/**
 * کلاس Events — گذرگاه رویداد (Event-Driven)
 *
 * معماری ED: جریان‌های کسب‌وکار به‌جای فراخوانی مستقیم، رویداد منتشر می‌کنند
 * و شنونده‌ها (در ماژول‌ها) واکنش نشان می‌دهند. مثال:
 *
 *   Events::dispatch('order.placed', ['order' => $order]);
 *   → شنوندهٔ ماژول Production یک سفارش تولید (WO) ایجاد می‌کند
 *   → شنوندهٔ ماژول Members اطلاعیهٔ کاربر می‌سازد
 */

declare(strict_types=1);

namespace Core;

final class Events
{
    /** @var array<string, array<int, callable>> */
    private static array $listeners = [];

    /** @var array<int, array{event:string, payload:array, at:string}> رویدادهای منتشرشده (برای تست/لاگ) */
    private static array $dispatched = [];

    public static function on(string $event, callable $listener): void
    {
        self::$listeners[$event][] = $listener;
    }

    /** ثبت گروهی شنونده‌ها از آرایهٔ ['event' => callable] */
    public static function register(array $map): void
    {
        foreach ($map as $event => $listener) {
            self::on($event, $listener);
        }
    }

    /**
     * صف‌گذاری رویداد برای پردازش ناهمگام (نقشهٔ راه ۰٫۳٫۱).
     * payload باید فقط دادهٔ سریال‌شدنی باشد (id/code — نه آبجکت).
     */
    public static function queue(string $event, array $payload = []): void
    {
        Database::insert(
            'INSERT INTO event_queue (event, payload, status, created_at) VALUES (?, ?, "pending", NOW())',
            [$event, json_encode($payload, JSON_UNESCAPED_UNICODE)]
        );
    }

    public static function dispatch(string $event, array $payload = [], bool $forceSync = false): void
    {
        self::$dispatched[] = ['event' => $event, 'payload' => $payload, 'at' => date('Y-m-d H:i:s')];

        // رویدادهای پیکربندی‌شده به صف می‌روند (پردازش توسط bin/faraat queue:work)
        $queued = (array) Config::get('app.queued_events', []);
        if (!$forceSync && in_array($event, $queued, true)) {
            self::queue($event, $payload);
            return;
        }

        foreach (self::$listeners[$event] ?? [] as $listener) {
            $listener($payload, $event);
        }
        // ثبت در لاگ فعالیت‌ها (اختیاری)
        if (Config::get('app.log_events', false)) {
            try {
                Database::insert(
                    'INSERT INTO activity_logs (user_id, action, entity, entity_id, ip, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
                    [Auth::id(), $event, $payload['_entity'] ?? null, $payload['_entity_id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? 'cli']
                );
            } catch (\Throwable) {
                // جدول ممکن است هنوز موجود نباشد (هنگام مهاجرت)
            }
        }
    }

    public static function hasListeners(string $event): bool
    {
        return !empty(self::$listeners[$event]);
    }

    /** @return array<int, array> رویدادهای منتشرشده — برای تست */
    public static function dispatched(?string $event = null): array
    {
        if ($event === null) {
            return self::$dispatched;
        }
        return array_values(array_filter(self::$dispatched, fn ($d) => $d['event'] === $event));
    }

    public static function flush(): void
    {
        self::$dispatched = [];
    }

    public static function reset(): void
    {
        self::$listeners = [];
        self::$dispatched = [];
    }
}
