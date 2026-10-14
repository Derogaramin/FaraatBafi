<?php
/**
 * کلاس Router — مسیریاب مرکزی با پشتیبانی از پارامتر، نام مسیر، گروه و میان‌افزار
 *
 * مسیرها توسط ماژول‌ها (app/Modules/<Name>/routes.php) و فایل config/routes.php ثبت می‌شوند.
 * الگو: /marketplace/product/{sku} — پارامترها به $request->params تزریق می‌شوند.
 */

declare(strict_types=1);

namespace Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, params:array, action:mixed, name:?string, middleware:array}> */
    private array $routes = [];

    /** @var array<string, int> نگاشت نام مسیر به ایندکس */
    private array $named = [];

    /** @var array{prefix:string, middleware:array} */
    private array $groupStack = ['prefix' => '', 'middleware' => []];

    /** @var array{handler:callable, status:int}|null */
    private ?array $errorHandler = null;

    public function get(string $pattern, mixed $action, ?string $name = null, array $middleware = []): void
    {
        $this->add('GET', $pattern, $action, $name, $middleware);
    }

    public function post(string $pattern, mixed $action, ?string $name = null, array $middleware = []): void
    {
        $this->add('POST', $pattern, $action, $name, $middleware);
    }

    public function any(string $pattern, mixed $action, ?string $name = null, array $middleware = []): void
    {
        $this->add('GET', $pattern, $action, $name, $middleware);
        $this->add('POST', $pattern, $action, $name, $middleware);
    }

    public function add(string $method, string $pattern, mixed $action, ?string $name = null, array $middleware = []): void
    {
        $full = $this->normalize($this->groupStack['prefix'] . '/' . ltrim($pattern, '/'));
        $allMiddleware = array_merge($this->groupStack['middleware'], $middleware);

        [$regex, $params] = $this->compile($full);
        $index = count($this->routes);
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $full,
            'regex'      => $regex,
            'params'     => $params,
            'action'     => $action,
            'name'       => $name,
            'middleware' => $allMiddleware,
        ];
        if ($name !== null) {
            $this->named[$name] = $index;
        }
    }

    /** گروه‌بندی مسیرها با پیشوند و میان‌افزار مشترک */
    public function group(array $attributes, callable $routes): void
    {
        $previous = $this->groupStack;
        $this->groupStack = [
            'prefix'     => $this->normalize(($previous['prefix'] ?? '') . '/' . ($attributes['prefix'] ?? '')),
            'middleware' => array_merge($previous['middleware'] ?? [], $attributes['middleware'] ?? []),
        ];
        $routes($this);
        $this->groupStack = $previous;
    }

    public function onError(callable $handler): void
    {
        $this->errorHandler = ['handler' => $handler, 'status' => 404];
    }

    /** یافتن آدرس از روی نام مسیر */
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->named[$name])) {
            throw new \InvalidArgumentException("مسیر نام‌گذاری‌شده یافت نشد: {$name}");
        }
        $pattern = $this->routes[$this->named[$name]]['pattern'];
        $url = preg_replace_callback('/\{(\w+)\}/', function ($m) use (&$params) {
            $key = $m[1];
            if (!array_key_exists($key, $params)) {
                throw new \InvalidArgumentException("پارامتر «{$key}» برای مسیر ارائه نشده است.");
            }
            $value = (string) $params[$key];
            unset($params[$key]);
            return rawurlencode($value);
        }, $pattern) ?? $pattern;

        if ($params !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        }
        return url(ltrim($url, '/'));
    }

    /** @return array<int, array> همهٔ مسیرهای ثبت‌شده (برای دیباگ/تست) */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * خروجی قابل‌ذخیرهٔ مسیرهای کامپایل‌شده (فقط مسیرهای غیر-closure).
     * با `php bin/faraat optimize` در storage/cache/routes.cache.php ذخیره می‌شود.
     */
    public function exportCache(): array
    {
        $exportable = [];
        foreach ($this->routes as $route) {
            if (is_array($route['action'])) {
                $exportable[] = $route;
            }
        }
        return ['routes' => $exportable, 'named' => $this->named];
    }

    /** بارگذاری از کش (جایگزین ثبت زندهٔ مسیرها) */
    public function loadCache(array $cache): void
    {
        $this->routes = $cache['routes'] ?? [];
        $this->named = $cache['named'] ?? [];
    }

    /**
     * تطبیق درخواست با مسیرها.
     *
     * @return array{route:array, params:array}|array{error:int}
     */
    public function match(Request $request): array
    {
        $pathMatched = false;
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->uri(), $matches)) {
                $pathMatched = true;
                if ($route['method'] !== $request->method()) {
                    continue;
                }
                $params = [];
                foreach ($route['params'] as $i => $name) {
                    $params[$name] = $matches[$i + 1] ?? '';
                }
                return ['route' => $route, 'params' => $params];
            }
        }
        return ['error' => $pathMatched ? 405 : 404];
    }

    private function compile(string $pattern): array
    {
        $params = [];
        $regex = preg_replace_callback('/\{(\w+)(:.*?)?\}/', function ($m) use (&$params) {
            $params[] = $m[1];
            $constraint = $m[2] ?? null;
            if ($constraint === ':int') {
                return '(\d+)';
            }
            return '([^/]+)';
        }, $pattern) ?? $pattern;
        return ['#^' . $regex . '$#u', $params];
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
