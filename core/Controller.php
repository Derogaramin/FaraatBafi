<?php
/**
 * کلاس Controller — کنترلر پایه با امکانات مشترک
 *
 * تمام کنترلرهای ماژول‌ها از این کلاس ارث می‌برند:
 *  - رندر ویو با لایه
 *  - پاسخ JSON
 *  - ریدایرکت با فلش
 *  - اعتبارسنجی و برگشت خطا به فرم
 *  - محافظت نقش‌محور (RBAC)
 */

declare(strict_types=1);

namespace Core;

abstract class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /** رندر ویو */
    protected function view(string $name, array $data = [], int $status = 200): Response
    {
        return View::render($name, $data, $status);
    }

    /** پاسخ JSON (برای AJAX و API) */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /** ریدایرکت با پیام فلش */
    protected function redirect(string $to, ?string $flashType = null, ?string $message = null): Response
    {
        if ($flashType !== null && $message !== null) {
            Session::flash($flashType, $message);
        }
        return Response::redirect($to);
    }

    protected function back(?string $fallback = '/'): Response
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        return Response::redirect($referer ?: url(ltrim($fallback, '/')));
    }

    /**
     * اعتبارسنجی ورودی‌ها — در صورت خطا، ریدایرکت به فرم قبلی با خطاها و old.
     *
     * @return array<string, mixed> دادهٔ اعتبارسنجی‌شده
     */
    protected function validate(array $data, array $rules, array $labels = [], ?string $redirectBack = null): array
    {
        $validator = Validator::make($data, $rules, $labels);
        if ($validator->fails()) {
            Session::flashErrors($validator->errors());
            Session::flashInput($data);
            if ($this->request->wantsJson()) {
                throw new ValidationException($validator->errors());
            }
            $to = $redirectBack ?? ($_SERVER['HTTP_REFERER'] ?? url(''));
            throw new RedirectException($to);
        }
        return $validator->validated();
    }

    /** دسترسی فقط برای نقش‌های مشخص — در غیر این صورت ریدایرکت/۴۰۳ */
    protected function authorize(string ...$roles): void
    {
        if (!Auth::check()) {
            Session::put('intended', $_SERVER['REQUEST_URI'] ?? '/');
            throw new RedirectException(url('login'));
        }
        if (!in_array(Auth::role(), $roles, true) && !Auth::isSuper()) {
            throw new HttpException(403, 'شما به این بخش دسترسی ندارید.');
        }
    }

    /** بررسی یک مجوز RBAC */
    protected function authorizeCan(string $permission): void
    {
        if (!Auth::check()) {
            throw new RedirectException(url('login'));
        }
        if (!Auth::can($permission)) {
            throw new HttpException(403, 'شما به این بخش دسترسی ندارید.');
        }
    }
}
