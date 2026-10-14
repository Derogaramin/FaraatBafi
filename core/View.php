<?php
/**
 * کلاس View — موتور قالب مبتنی بر PHP خالص با لایه، بخش‌ها و کامپوننت‌ها
 *
 * قراردادها:
 *  - ویوی ماژول:      "Marketplace::catalog.index"  → app/Modules/Marketplace/Views/catalog/index.php
 *  - ویوی سراسری:     "components.product-card"     → app/Views/components/product-card.php
 *  - لایه: در ویو    <?php $layout = 'layouts.public'; ?> (یا layouts.admin/weaver/agency/auth)
 *  - بخش‌ها:         $this->start('scripts') ... $this->end()   و در لایه  <?= $this->section('scripts') ?>
 */

declare(strict_types=1);

namespace Core;

use RuntimeException;

final class View
{
    /** @var array<string, mixed> دادهٔ اشتراکی همهٔ ویوها */
    private static array $shared = [];

    /** @var array<string, string> بافر بخش‌ها */
    private array $sections = [];

    /** @var array<int, string> پشتهٔ بخش در حال ضبط */
    private array $sectionStack = [];

    private ?string $layout = null;

    /** @var array<string, mixed> داده‌های ارسال‌شده به لایه (title, bodyClass, ...) */
    private array $layoutData = [];

    private string $currentModule = '';

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /** رندر کامل ویو (شامل لایه) و بازگرداندن Response */
    public static function render(string $name, array $data = [], int $status = 200): Response
    {
        return Response::html(self::capture($name, $data), $status);
    }

    /** رندر ویو و بازگرداندن رشتهٔ HTML (نهایی با لایه) */
    public static function capture(string $name, array $data = []): string
    {
        $view = new self();
        return $view->renderInternal($name, $data);
    }

    private function renderInternal(string $name, array $data): string
    {
        [$file, $module] = $this->resolve($name);
        $this->currentModule = $module;

        $content = $this->evaluate($file, array_merge(self::$shared, $data));

        // اگر ویو لایه تعیین کرده باشد، لایه رندر می‌شود
        if ($this->layout !== null) {
            [$layoutFile] = $this->resolve($this->layout);
            // دادهٔ ویو (مثل title) نیز در دسترس لایه قرار می‌گیرد
            $layoutData = array_merge(self::$shared, $data, $this->layoutData, [
                'content' => $content,
                'view'    => $this,
            ]);
            return $this->evaluate($layoutFile, $layoutData);
        }

        return $content;
    }

    /** تعیین لایه از درون ویو */
    public function extend(string $layout, array $data = []): void
    {
        $this->layout = $layout;
        $this->layoutData = array_merge($this->layoutData, $data);
    }

    /** شروع ضبط یک بخش */
    public function start(string $section): void
    {
        $this->sectionStack[] = $section;
        ob_start();
    }

    /** پایان ضبط بخش جاری */
    public function end(): void
    {
        if ($this->sectionStack === []) {
            throw new RuntimeException('end() بدون start() فراخوانی شد.');
        }
        $section = array_pop($this->sectionStack);
        $this->sections[$section] = ($this->sections[$section] ?? '') . ob_get_clean();
    }

    /** خروجی بخش (برای استفاده در لایه) */
    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]) && trim($this->sections[$name]) !== '';
    }

    /** افزودن اسکریپت/استایل به انتهای صفحه */
    public function pushScript(string $html): void
    {
        $this->sections['scripts'] = ($this->sections['scripts'] ?? '') . $html;
    }

    public function pushStyle(string $html): void
    {
        $this->sections['styles'] = ($this->sections['styles'] ?? '') . $html;
    }

    /** رندر کامپوننت/پارشیال از درون ویو (بدون لایه) */
    public function include(string $name, array $data = []): string
    {
        [$file, $module] = $this->resolve($name);
        $previous = $this->currentModule;
        $this->currentModule = $module;
        $result = $this->evaluate($file, array_merge(self::$shared, $data));
        $this->currentModule = $previous;
        return $result;
    }

    /**
     * یافتن فایل ویو.
     *
     * @return array{0:string, 1:string} [مسیر فایل, نام ماژول]
     */
    private function resolve(string $name): array
    {
        // نام ماژول::مسیر.ویو
        if (str_contains($name, '::')) {
            [$module, $path] = explode('::', $name, 2);
            $file = App::basePath("app/Modules/{$module}/Views/" . str_replace('.', '/', $path) . '.php');
            if (is_file($file)) {
                return [$file, $module];
            }
            throw new RuntimeException("ویوی ماژول یافت نشد: {$name} ({$file})");
        }

        // ویوی ماژول جاری (اگر از ویوی ماژول فراخوانی شود)
        if ($this->currentModule !== '') {
            $file = App::basePath("app/Modules/{$this->currentModule}/Views/" . str_replace('.', '/', $name) . '.php');
            if (is_file($file)) {
                return [$file, $this->currentModule];
            }
        }

        // ویوی سراسری
        $file = App::basePath('app/Views/' . str_replace('.', '/', $name) . '.php');
        if (is_file($file)) {
            return [$file, ''];
        }

        throw new RuntimeException("ویو یافت نشد: {$name}");
    }

    /** اجرای فایل قالب با داده‌های استخراج‌شده — درون قالب $this همان View است */
    private function evaluate(string $__file, array $__data): string
    {
        extract($__data, EXTR_SKIP);
        /** @var View $view نمونهٔ ویو برای دسترسی صریح */
        $view = $this;
        ob_start();
        try {
            include $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
