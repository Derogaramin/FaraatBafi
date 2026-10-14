<?php
/**
 * کلاس App — هستهٔ راه‌انداز و dispatch مرکزی سامانهٔ فرت‌بافی
 *
 * جریان یک درخواست:
 *   public/index.php → App::boot() → App::handle(Request)
 *   → Router::match → اجرای middlewareها → Controller@action → Response::send()
 *
 * میان‌افزارهای داخلی:
 *   auth            → ورود اجباری
 *   guest           → فقط کاربران واردنشده
 *   csrf            → بررسی توکن CSRF روی POST
 *   role:M,S        → محدودسازی نقش (پیشوند FUCS)
 *   can:orders.view → بررسی مجوز RBAC
 */

declare(strict_types=1);

namespace Core;

final class App
{
    private static string $basePath = '';

    private static ?Router $router = null;

    public static function boot(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');

        mb_internal_encoding('UTF-8');
        date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Tehran'));

        Config::boot(self::$basePath);

        // مدیریت خطاها
        error_reporting(E_ALL);
        ini_set('display_errors', Config::get('app.debug') ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', self::$basePath . '/storage/logs/php-error.log');
        set_exception_handler([self::class, 'handleException']);
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        Session::start();

        // دادهٔ اشتراکی همهٔ ویوها
        View::share('app_name', (string) Config::get('app.name', 'فرت‌بافی'));
        View::share('app_env', (string) Config::get('app.env', 'local'));
        View::share('flashes', Session::pullFlashes());
        View::share('session_errors', Session::pullErrors());
        View::share('cart_count', self::cartCount());

        // کشف ماژول‌ها و ثبت مسیرها و رویدادها (HMVC + ED)
        Modules::boot();
        $router = self::router();
        Modules::registerRoutes($router);
        Modules::registerEvents();

        // مسیرهای هسته (صفحات خطا)
        $router->onError(static fn (int $status) => self::errorResponse($status));
    }

    public static function basePath(string $append = ''): string
    {
        return self::$basePath . ($append !== '' ? '/' . ltrim($append, '/') : '');
    }

    /** هندلر سراسری استثنای ثبت‌نشده */
    public static function handleException(\Throwable $e): void
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "\n❌ " . $e->getMessage() . "\n  در " . $e->getFile() . ':' . $e->getLine() . "\n");
            exit(1);
        }
        self::exceptionResponse($e)->send();
        exit(1);
    }

    public static function router(): Router
    {
        if (self::$router === null) {
            self::$router = new Router();

            // کش مسیرهای کامپایل‌شده (ساخته‌شده با `php bin/faraat optimize`)
            $cacheFile = self::$basePath . '/storage/cache/routes.cache.php';
            if (is_file($cacheFile)) {
                $cache = require $cacheFile;
                if (is_array($cache) && isset($cache['routes'])) {
                    self::$router->loadCache($cache);
                    return self::$router;
                }
            }

            $coreRoutes = self::$basePath . '/config/routes.php';
            if (is_file($coreRoutes)) {
                (require $coreRoutes)(self::$router);
            }
        }
        return self::$router;
    }

    /** پردازش یک درخواست و تولید پاسخ */
    public static function handle(Request $request): Response
    {
        try {
            $match = self::router()->match($request);
            if (isset($match['error'])) {
                return self::errorResponse($match['error']);
            }

            $route = $match['route'];
            $request->params = $match['params'];

            // اجرای میان‌افزارها
            foreach ($route['middleware'] as $middleware) {
                $result = self::runMiddleware($middleware, $request);
                if ($result instanceof Response) {
                    return $result;
                }
            }

            $response = self::callAction($route['action'], $request);
            return self::applyEtag($response, $request);
        } catch (RedirectException $e) {
            return Response::redirect($e->to(), $e->statusCode());
        } catch (ValidationException $e) {
            return Response::json(['ok' => false, 'errors' => $e->errors()], 422);
        } catch (HttpException $e) {
            return self::errorResponse($e->statusCode(), $e->getMessage());
        } catch (\Throwable $e) {
            return self::exceptionResponse($e);
        }
    }

    /** ETag + پاسخ ۳۰۴ برای GETهای تغییریافته (نقشهٔ راه ۰٫۳٫۳) */
    private static function applyEtag(Response $response, Request $request): Response
    {
        if ($request->method() !== 'GET' || $response->status() !== 200 || $response->body() === '') {
            return $response;
        }
        $etag = '"' . md5($response->body()) . '"';
        $response->withHeader('ETag', $etag);
        if ($request->header('If-None-Match') === $etag) {
            return new Response('', 304);
        }
        return $response;
    }

    /** فراخوانی اکشن کنترلر یا closure */
    public static function callAction(mixed $action, Request $request): Response
    {
        if (is_callable($action)) {
            $result = $action($request);
        } else {
            [$class, $method] = is_array($action) ? $action : explode('@', (string) $action);
            if (!class_exists($class)) {
                throw new HttpException(500, "کنترلر یافت نشد: {$class}");
            }
            $controller = new $class($request);
            if (!method_exists($controller, $method)) {
                throw new HttpException(500, "اکشن یافت نشد: {$class}@{$method}");
            }
            $result = $controller->{$method}($request);
        }

        if ($result instanceof Response) {
            return $result;
        }
        if (is_string($result)) {
            return Response::html($result);
        }
        if (is_array($result)) {
            return Response::json($result);
        }
        return Response::html((string) $result);
    }

    /** اجرای یک میان‌افزار — خروجی null یعنی ادامه، Response یعنی توقف */
    private static function runMiddleware(string $middleware, Request $request): ?Response
    {
        [$name, $param] = array_pad(explode(':', $middleware, 2), 2, null);

        switch ($name) {
            case 'auth':
                if (Auth::guest()) {
                    Session::put('intended', $request->uri());
                    Session::flash('warning', 'برای ادامه ابتدا وارد سامانه شوید.');
                    return Response::redirect(url('login'));
                }
                // تغییر گذرواژهٔ اجباری (نقد S8)
                if ((int) (Auth::user()['must_change_password'] ?? 0) === 1) {
                    $allowed = ['/my/profile', '/my/password', '/logout'];
                    $uri = $request->uri();
                    $isOk = in_array($uri, $allowed, true) || str_starts_with($uri, '/my/profile');
                    if (!$isOk) {
                        Session::flash('warning', 'برای ادامهٔ کار باید گذرواژهٔ پیش‌فرض را تغییر دهید.');
                        return Response::redirect(url('my/profile') . '?force=1');
                    }
                }
                return null;

            case '2fa':
                // عملیات حساس برای نقش‌های دارای احراز دو مرحله‌ای (نقشهٔ راه ۰٫۱٫۵)
                if (!Auth::check()) {
                    return Response::redirect(url('login'));
                }
                $requireFor = (array) Config::get('auth.require_2fa_for', []);
                if (!in_array(Auth::role(), $requireFor, true)) {
                    return null; // نقش مشمول نیست
                }
                if (!empty(Auth::user()['totp_secret'])) {
                    if (!Auth::twoFactorCleared()) {
                        Session::put('intended', $request->uri());
                        return Response::redirect(url('verify-2fa'));
                    }
                    return null;
                }
                // هنوز ثبت‌نام نکرده → وادار به فعال‌سازی
                Session::flash('warning', 'این عملیات به احراز هویت دو مرحله‌ای نیاز دارد. ابتدا آن را در «امنیت حساب» فعال کنید.');
                return Response::redirect(url('admin/security'));

            case 'guest':
                if (Auth::check()) {
                    return Response::redirect(self::panelHome());
                }
                return null;

            case 'csrf':
                if ($request->isPost() && !Session::verifyCsrf($request->input('_token'))) {
                    throw new HttpException(419);
                }
                return null;

            case 'role':
                $roles = array_map('trim', explode(',', (string) $param));
                if (!Auth::check()) {
                    Session::put('intended', $request->uri());
                    return Response::redirect(url('login'));
                }
                // نقش‌ها با پیشوند کد (M,S,W,A,C) یا نام نقش (member,...) مقایسه می‌شوند
                $userRole = Auth::role();
                $prefix = strtoupper(substr((string) Auth::code(), 0, 1));
                $ok = in_array($userRole, $roles, true) || in_array($prefix, $roles, true) || Auth::isSuper();
                if (!$ok) {
                    throw new HttpException(403);
                }
                return null;

            case 'can':
                if (!Auth::check()) {
                    return Response::redirect(url('login'));
                }
                if (!Auth::can((string) $param)) {
                    throw new HttpException(403);
                }
                return null;

            default:
                throw new HttpException(500, "میان‌افزار ناشناخته: {$name}");
        }
    }

    /** خانهٔ پیش‌فرض هر نقش پس از ورود */
    public static function panelHome(): string
    {
        return match (Auth::role()) {
            'member', 'staff' => url('admin'),
            'weaver'          => url('weaver'),
            'agency'          => url('agency'),
            'customer'        => url('my'),
            default           => url('/'),
        };
    }

    /** صفحهٔ خطای HTML با پوستهٔ برند */
    public static function errorResponse(int $status, string $message = ''): Response
    {
        try {
            return View::render('errors/error', [
                'status'  => $status,
                'message' => $message !== '' ? $message : HttpException::defaultMessage($status),
            ], $status);
        } catch (\Throwable) {
            return Response::html("<h1>{$status}</h1><p>" . e($message) . '</p>', $status);
        }
    }

    private static function exceptionResponse(\Throwable $e): Response
    {
        $debug = (bool) Config::get('app.debug', false);
        error_log(sprintf('[%s] %s: %s in %s:%d', date('c'), $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));

        if ($debug) {
            $trace = e($e->getTraceAsString());
            $body = '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>خطای ۵۰۰</title>'
                . '<style>body{font-family:Tahoma;background:#FDFBF7;padding:32px;color:#3A2C24}'
                . 'pre{background:#221B16;color:#F3EBDD;padding:16px;border-radius:12px;overflow:auto;direction:ltr;text-align:left;font-size:12px}</style></head><body>'
                . '<h1>خطای داخلی سامانه (۵۰۰)</h1><h3>' . e($e->getMessage()) . '</h3>'
                . '<p style="direction:ltr">' . e($e->getFile()) . ':' . $e->getLine() . '</p><pre>' . $trace . '</pre></body></html>';
            return Response::html($body, 500);
        }
        return self::errorResponse(500);
    }

    /** تعداد اقلام سبد خرید (نشست) */
    public static function cartCount(): int
    {
        $cart = (array) Session::get('cart', []);
        return array_sum(array_map('intval', $cart));
    }

    /** لاگ ساده در storage/logs */
    public static function log(string $channel, string $message): void
    {
        $dir = self::$basePath . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
        file_put_contents($dir . "/{$channel}.log", $line, FILE_APPEND | LOCK_EX);
    }
}
