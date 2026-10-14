<?php
/**
 * استثنای اعتبارسنجی — برای درخواست‌های JSON/AJAX
 */

declare(strict_types=1);

namespace Core;

class ValidationException extends \RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(private array $errors)
    {
        parent::__construct(reset($errors) ?: 'خطای اعتبارسنجی');
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
