<?php
/**
 * کلاس Request — کپسوله‌سازی درخواست HTTP
 */

declare(strict_types=1);

namespace Core;

final class Request
{
    /** @var array<string, mixed> */
    public array $query;

    /** @var array<string, mixed> */
    public array $post;

    /** @var array<string, mixed> */
    public array $files;

    /** @var array<string, string> پارامترهای مسیر (از Router) */
    public array $params = [];

    private string $method;

    private string $uri;

    public function __construct(?string $method = null, ?string $uri = null, array $query = [], array $post = [], array $files = [], array $server = [])
    {
        $this->method = strtoupper($method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $rawUri = $uri ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($rawUri, PHP_URL_PATH) ?: '/';
        // حذف BASE_URL از ابتدای مسیر (برای نصب در زیرپوشه)
        $base = rtrim((string) Config::get('app.base_path', ''), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $this->uri = '/' . trim(rawurldecode($path), '/');
        if ($this->uri !== '/') {
            $this->uri = rtrim($this->uri, '/');
        }
        if ($query !== []) {
            $this->query = $query;
        } else {
            $qs = parse_url($rawUri, PHP_URL_QUERY);
            if ($qs !== null && $qs !== false && $qs !== '') {
                parse_str($qs, $parsed);
                $this->query = $parsed;
            } else {
                $this->query = $_GET;
            }
        }
        $this->post = $post ?: $_POST;
        $this->files = $files ?: $_FILES;
    }

    /** ساخت درخواست مصنوعی (برای تست و HMVC داخلی) */
    public static function create(string $method, string $uri, array $post = [], array $query = []): self
    {
        return new self($method, $uri, $query, $post, [], []);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        return ltrim($this->uri, '/');
    }

    /** مقدار ورودی (post → query → پارامترهای مسیر) */
    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? $this->params[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function str(string $key, string $default = ''): string
    {
        return (string) $this->input($key, $default);
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = en_digits((string) $this->input($key, ''));
        return is_numeric($value) ? (float) $value : $default;
    }

    public function bool(string $key): bool
    {
        return in_array($this->input($key), ['1', 1, 'on', 'true', true], true);
    }

    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->query[$key]) || isset($this->params[$key]);
    }

    /** همهٔ ورودی‌ها ادغام‌شده (پارامترهای مسیر در اولویت پایین‌تر) */
    public function all(): array
    {
        return array_merge($this->params, $this->query, $this->post);
    }

    /** پارامتر مسیر */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With')) === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        return $this->isAjax() || str_contains($this->header('Accept'), 'application/json');
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function header(string $name, string $default = ''): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? $default;
    }
}
