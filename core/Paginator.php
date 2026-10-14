<?php
/**
 * کلاس Paginator — صفحه‌بندی نتایج با لینک‌های فارسی
 */

declare(strict_types=1);

namespace Core;

final class Paginator
{
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $page,
    ) {
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    public function hasPages(): bool
    {
        return $this->lastPage() > 1;
    }

    public function from(): int
    {
        return $this->total === 0 ? 0 : (($this->page - 1) * $this->perPage) + 1;
    }

    public function to(): int
    {
        return min($this->total, $this->page * $this->perPage);
    }

    /** تولید HTML صفحه‌بندی (سبک فرت) */
    public function links(string $baseUrl = ''): string
    {
        if (!$this->hasPages()) {
            return '';
        }
        $baseUrl = $baseUrl !== '' ? $baseUrl : (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $query = $_GET;
        $last = $this->lastPage();

        $link = function (int $page, string $label, bool $active = false, bool $disabled = false) use ($baseUrl, $query): string {
            $query['page'] = $page;
            $href = $baseUrl . '?' . http_build_query($query);
            $class = 'page-link' . ($active ? ' active' : '') . ($disabled ? ' disabled' : '');
            $hrefAttr = $disabled ? '' : ' href="' . e($href) . '"';
            return '<a class="' . $class . '"' . $hrefAttr . '>' . $label . '</a>';
        };

        $html = '<nav class="pagination" dir="rtl" aria-label="صفحه‌بندی">';
        $html .= $link(max(1, $this->page - 1), '‹', false, $this->page <= 1);

        $window = 2;
        $start = max(1, $this->page - $window);
        $end = min($last, $this->page + $window);
        if ($start > 1) {
            $html .= $link(1, fa('۱'));
            if ($start > 2) {
                $html .= '<span class="page-dots">…</span>';
            }
        }
        for ($i = $start; $i <= $end; $i++) {
            $html .= $link($i, fa((string) $i), $i === $this->page);
        }
        if ($end < $last) {
            if ($end < $last - 1) {
                $html .= '<span class="page-dots">…</span>';
            }
            $html .= $link($last, fa((string) $last));
        }

        $html .= $link(min($last, $this->page + 1), '›', false, $this->page >= $last);
        $html .= '</nav>';
        return $html;
    }
}
