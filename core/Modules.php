<?php
/**
 * کلاس Modules — مدیر ماژول‌ها (معماری HMVC)
 *
 * هر ماژول در app/Modules/[Name]/ یک واحد خودبسنده است:
 *   module.json    → شناسنامهٔ ماژول (نام فارسی، آیکون، ترتیب، وضعیت)
 *   routes.php     → ثبت مسیرها (function(Router $router))
 *   events.php     → شنونده‌های رویدادها (function())
 *   menu.php       → آیتم‌های منوی پنل (function(): array)
 *   Controllers/   → کنترلرها
 *   Models/        → مدل‌ها
 *   Services/      → منطق کسب‌وکار
 *   Views/         → ویوهای ماژول
 *
 * HMVC: ماژول‌ها می‌توانند از طریق Modules::run() زیردرخواست داخلی
 * به کنترلر ماژول دیگری بدهند (بدون HTTP) — مثلاً ویجت اعلان‌ها.
 */

declare(strict_types=1);

namespace Core;

use RuntimeException;

final class Modules
{
    /** @var array<string, array> شناسنامهٔ ماژول‌ها */
    private static array $modules = [];

    private static bool $booted = false;

    /** کشف و بارگذاری همهٔ ماژول‌ها */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        $dir = App::basePath('app/Modules');
        foreach (glob($dir . '/*', GLOB_ONLYDIR) ?: [] as $path) {
            $name = basename($path);
            $metaFile = $path . '/module.json';
            $meta = is_file($metaFile)
                ? (json_decode((string) file_get_contents($metaFile), true) ?: [])
                : [];
            self::$modules[$name] = array_merge([
                'name'     => $name,
                'title_fa' => $name,
                'icon'     => 'box',
                'order'    => 100,
                'enabled'  => true,
                'phase'    => 1,
            ], $meta, ['path' => $path]);
        }
        self::$booted = true;
    }

    /** @return array<string, array> */
    public static function all(): array
    {
        self::boot();
        return self::$modules;
    }

    public static function get(string $name): ?array
    {
        self::boot();
        return self::$modules[$name] ?? null;
    }

    public static function enabled(): array
    {
        self::boot();
        return array_filter(self::$modules, fn ($m) => (bool) $m['enabled']);
    }

    /** ثبت مسیرهای همهٔ ماژول‌های فعال */
    public static function registerRoutes(Router $router): void
    {
        self::boot();
        foreach (self::enabled() as $name => $meta) {
            $file = $meta['path'] . '/routes.php';
            if (is_file($file)) {
                $callback = require $file;
                if (!is_callable($callback)) {
                    throw new RuntimeException("فایل routes.php ماژول «{$name}» باید closure بازگرداند.");
                }
                $callback($router, $name);
            }
        }
    }

    /** ثبت شنونده‌های رویداد همهٔ ماژول‌ها */
    public static function registerEvents(): void
    {
        self::boot();
        foreach (self::enabled() as $meta) {
            $file = $meta['path'] . '/events.php';
            if (is_file($file)) {
                // فایل events.php می‌تواند مستقیماً شنونده‌ها را ثبت کند
                // یا closure بازگرداند؛ هر دو حالت پشتیبانی می‌شود.
                $callback = require $file;
                if (is_callable($callback)) {
                    $callback();
                }
            }
        }
    }

    /**
     * اجرای داخلی یک اکشن ماژول (HMVC) و بازگرداندن Response.
     * مثال: Modules::run('Members', 'NotificationController', 'widget')
     */
    public static function run(string $module, string $controller, string $action = 'index', array $params = []): Response
    {
        self::boot();
        if (!isset(self::$modules[$module])) {
            throw new RuntimeException("ماژول «{$module}» یافت نشد.");
        }
        $class = "App\\Modules\\{$module}\\Controllers\\{$controller}";
        if (!class_exists($class)) {
            throw new RuntimeException("کنترلر «{$class}» یافت نشد.");
        }
        $request = new Request('GET', '/', [], [], [], []);
        $request->params = $params;
        $instance = new $class($request);
        if (!method_exists($instance, $action)) {
            throw new RuntimeException("اکشن «{$action}» در «{$class}» وجود ندارد.");
        }
        return $instance->{$action}($request);
    }

    /** منوی تجمیعی پنل‌ها از همهٔ ماژول‌ها (فیلترشده با RBAC) */
    public static function collectMenus(string $panel): array
    {
        self::boot();
        $items = [];
        foreach (self::enabled() as $meta) {
            $file = $meta['path'] . '/menu.php';
            if (is_file($file)) {
                $menu = (require $file)($panel) ?? [];
                foreach ($menu as $item) {
                    // فیلتر مجوز
                    if (isset($item['permission']) && !Auth::can($item['permission'])) {
                        continue;
                    }
                    if (isset($item['roles']) && !Auth::is(...(array) $item['roles']) && !Auth::isSuper()) {
                        continue;
                    }
                    $items[] = $item;
                }
            }
        }
        usort($items, fn ($a, $b) => ($a['order'] ?? 100) <=> ($b['order'] ?? 100));
        return $items;
    }
}
