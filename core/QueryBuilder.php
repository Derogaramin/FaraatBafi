<?php
/**
 * کلاس QueryBuilder — سازندهٔ پرس‌وجوی روان (Fluent) روی PDO
 *
 * مثال:
 *   $rows = (new QueryBuilder('orders'))->where('status', 'pending')->orderBy('id', 'DESC')->limit(10)->get();
 */

declare(strict_types=1);

namespace Core;

class QueryBuilder
{
    private array $wheres = [];

    private array $bindings = [];

    private array $joins = [];

    private array $orders = [];

    private ?int $limit = null;

    private ?int $offset = null;

    /** @var string[]|null */
    private ?array $columns = null;

    private array $groups = [];

    public function __construct(private string $table)
    {
    }

    public function select(string ...$columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /** where('col', 'op', value) یا where('col', value) */
    public function where(string $column, mixed $operator, mixed $value = null, string $boolean = 'AND'): self
    {
        if (func_num_args() === 2 || (func_num_args() === 3 && $value === null && !in_array((string) $operator, ['IS', 'IS NOT'], true))) {
            $value = $operator;
            $operator = '=';
        }
        if ($value === null && in_array(strtoupper((string) $operator), ['=', 'IS'], true)) {
            $this->wheres[] = "({$boolean} `{$column}` IS NULL)";
            return $this;
        }
        if ($value === null && strtoupper((string) $operator) === '!=' ) {
            $this->wheres[] = "({$boolean} `{$column}` IS NOT NULL)";
            return $this;
        }
        $this->wheres[] = "({$boolean} `{$column}` {$operator} ?)";
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            $this->wheres[] = '(AND 1 = 0)';
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "(AND `{$column}` IN ({$placeholders}))";
        foreach ($values as $v) {
            $this->bindings[] = $v;
        }
        return $this;
    }

    public function whereLike(string $column, string $value): self
    {
        $this->wheres[] = "(AND `{$column}` LIKE ?)";
        $this->bindings[] = '%' . $value . '%';
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = '(AND ' . $sql . ')';
        foreach ($bindings as $b) {
            $this->bindings[] = $b;
        }
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = "{$type} JOIN `{$table}` ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = strpos($column, '.') !== false || strpos($column, '(') !== false
            ? "{$column} {$direction}"
            : "`{$column}` {$direction}";
        return $this;
    }

    public function groupBy(string $column): self
    {
        $this->groups[] = "`{$column}`";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function toSql(): string
    {
        $columns = $this->columns ? implode(', ', array_map(
            fn ($c) => str_contains($c, '(') || str_contains($c, '.') || $c === '*' ? $c : "`{$c}`",
            $this->columns
        )) : '*';

        $sql = "SELECT {$columns} FROM `{$this->table}`";
        $sql .= $this->joinSql();
        $sql .= $this->whereSql();
        $sql .= $this->groupSql();
        $sql .= $this->orderSql();
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        return $sql;
    }

    public function bindings(): array
    {
        return $this->bindings;
    }

    public function get(): array
    {
        return Database::select($this->toSql(), $this->bindings);
    }

    public function first(): mixed
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function count(string $column = '*'): int
    {
        $sql = 'SELECT COUNT(' . ($column === '*' ? '*' : "`{$column}`") . ') AS aggregate FROM `' . $this->table . '`'
            . $this->joinSql() . $this->whereSql() . $this->groupSql();
        $row = Database::selectOne($sql, $this->bindings);
        return (int) ($row['aggregate'] ?? 0);
    }

    public function sum(string $column): float
    {
        $sql = "SELECT COALESCE(SUM(`{$column}`), 0) AS aggregate FROM `{$this->table}`" . $this->joinSql() . $this->whereSql();
        $row = Database::selectOne($sql, $this->bindings);
        return (float) ($row['aggregate'] ?? 0);
    }

    public function paginate(int $perPage = 15, ?int $page = null): Paginator
    {
        $page = max(1, $page ?? (int) ($_GET['page'] ?? 1));
        $total = $this->count();
        $items = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();
        return new Paginator($items, $total, $perPage, $page);
    }

    public function delete(): int
    {
        $sql = 'DELETE FROM `' . $this->table . '`' . $this->whereSql();
        return Database::delete($sql, $this->bindings);
    }

    private function joinSql(): string
    {
        return $this->joins === [] ? '' : ' ' . implode(' ', $this->joins);
    }

    private function whereSql(): string
    {
        if ($this->wheres === []) {
            return '';
        }
        $sql = '';
        foreach ($this->wheres as $i => $clause) {
            $clause = preg_replace('/^\((AND|OR) /', '(', $clause, 1) ?? $clause;
            if ($i === 0) {
                $sql .= ' WHERE ' . $clause;
            } else {
                $boolean = str_starts_with($this->wheres[$i], '(OR') ? 'OR' : 'AND';
                $sql .= ' ' . $boolean . ' ' . $clause;
            }
        }
        return $sql;
    }

    private function groupSql(): string
    {
        return $this->groups === [] ? '' : ' GROUP BY ' . implode(', ', $this->groups);
    }

    private function orderSql(): string
    {
        return $this->orders === [] ? '' : ' ORDER BY ' . implode(', ', $this->orders);
    }
}
