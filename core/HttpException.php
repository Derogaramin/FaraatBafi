<?php
/**
 * استثنای HTTP (۴۰۳، ۴۰۴، ۴۱۹، ۵۰۰ و…)
 */

declare(strict_types=1);

namespace Core;

class HttpException extends \RuntimeException
{
    public function __construct(private int $statusCode, string $message = '')
    {
        parent::__construct($message !== '' ? $message : self::defaultMessage($statusCode), $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public static function defaultMessage(int $code): string
    {
        return match ($code) {
            403     => 'دسترسی غیرمجاز',
            404     => 'صفحهٔ مورد نظر یافت نشد',
            405     => 'روش درخواست مجاز نیست',
            419     => 'نشست شما منقضی شده است؛ لطفاً دوباره تلاش کنید',
            500     => 'خطای داخلی سامانه',
            default => 'خطای نامشخص',
        };
    }
}
