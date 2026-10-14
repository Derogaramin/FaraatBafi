<?php
/**
 * کلاس Response — پاسخ HTTP
 */

declare(strict_types=1);

namespace Core;

final class Response
{
    /** @var array<string, string> */
    private array $headers = [];

    public function __construct(
        private string $body = '',
        private int $status = 200,
    ) {
        $this->headers['Content-Type'] = 'text/html; charset=UTF-8';
        $this->headers['X-Frame-Options'] = 'SAMEORIGIN';
        $this->headers['X-Content-Type-Options'] = 'nosniff';
        $this->headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
        $this->headers['X-XSS-Protection'] = '1; mode=block';
        // CSP سخت‌گیرانه: فقط منابع خودی + data: برای تصاویر/فونت (نقد S3)
        $this->headers['Content-Security-Policy'] =
            "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; "
            . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
        if (Config::get('app.env') === 'production') {
            $this->headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $response = new self((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), $status);
        $response->headers['Content-Type'] = 'application/json; charset=UTF-8';
        return $response;
    }

    public static function redirect(string $to, int $status = 302): self
    {
        $response = new self('', $status);
        $response->headers['Location'] = $to;
        return $response;
    }

    public static function svg(string $body): self
    {
        $response = new self($body);
        $response->headers['Content-Type'] = 'image/svg+xml; charset=UTF-8';
        return $response;
    }

    public static function download(string $body, string $filename, string $mime = 'application/octet-stream'): self
    {
        $response = new self($body);
        $response->headers['Content-Type'] = $mime;
        $response->headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        return $response;
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    /** ارسال پاسخ به مرورگر */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo $this->body;
    }
}
