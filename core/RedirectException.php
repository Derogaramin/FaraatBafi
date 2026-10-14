<?php
/**
 * استثنای ریدایرکت اجباری از میانهٔ اجرا (مثلاً در اعتبارسنجی)
 */

declare(strict_types=1);

namespace Core;

class RedirectException extends \RuntimeException
{
    public function __construct(private string $to, private int $statusCode = 302)
    {
        parent::__construct('redirect:' . $to);
    }

    public function to(): string
    {
        return $this->to;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
