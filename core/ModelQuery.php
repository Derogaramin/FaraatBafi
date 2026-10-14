<?php
/**
 * کلاس ModelQuery — کوئری‌ساز متصل به یک مدل
 *
 * نتایج get()/first()/paginate() به‌جای آرایه، نمونهٔ مدل (hydrated) برمی‌گردانند.
 */

declare(strict_types=1);

namespace Core;

final class ModelQuery extends QueryBuilder
{
    /** @param class-string<Model> $modelClass */
    public function __construct(string $table, private string $modelClass)
    {
        parent::__construct($table);
    }

    /** @return Model[] */
    public function get(): array
    {
        $rows = parent::get();
        return array_map(fn (array $row) => $this->modelClass::hydrate($row), $rows);
    }

    public function first(): mixed
    {
        $this->limit(1);
        $rows = parent::get(); // ردیف‌های خام (آرایه)
        $row = $rows[0] ?? null;
        return $row === null ? null : $this->modelClass::hydrate($row);
    }

    public function paginate(int $perPage = 15, ?int $page = null): Paginator
    {
        $page = max(1, $page ?? (int) ($_GET['page'] ?? 1));
        $total = $this->count();
        $items = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();
        return new Paginator($items, $total, $perPage, $page);
    }
}
