<?php
/**
 * کلاس Model — مدل پایهٔ تمام موجودیت‌ها (Active Record سبک)
 *
 * هر مدل جدول، کلید اصلی و فیلدهای پرشدنی خود را معرفی می‌کند:
 *
 *   final class Order extends Model {
 *       protected static string $table = 'orders';
 *       protected static array $fillable = ['code', 'customer_id', 'status', 'total'];
 *   }
 *
 * دسترسی روان:
 *   Order::where('status', 'pending')->orderBy('id','DESC')->get()
 *   Order::find(5)  |  Order::create([...])  |  $order->update([...])  |  $order->delete()
 */

declare(strict_types=1);

namespace Core;

abstract class Model
{
    protected static string $table = '';

    protected static string $primaryKey = 'id';

    /** @var string[] */
    protected static array $fillable = [];

    /** @var array<string, string> casts: int|float|bool|json */
    protected static array $casts = [];

    /** آیا جدول created_at/updated_at دارد؟ */
    protected static bool $timestamps = true;

    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @var array<string, mixed> */
    protected array $original = [];

    public ?bool $exists = null;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
        $this->exists = isset($this->attributes[static::$primaryKey]);
    }

    /* ============ کوئری‌های ایستا ============ */

    /** کوئری‌ساز ساده (نتایج: آرایه) */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::$table);
    }

    /** کوئری‌ساز مدل (نتایج: نمونهٔ مدل — hydrated) */
    public static function builder(): ModelQuery
    {
        return new ModelQuery(static::$table, static::class);
    }

    /** @return static[] */
    public static function all(string $orderColumn = 'id', string $direction = 'ASC'): array
    {
        return static::builder()->orderBy($orderColumn, $direction)->get();
    }

    public static function find(int|string $id): ?static
    {
        return static::builder()->where(static::$primaryKey, $id)->first();
    }

    public static function findBy(string $column, mixed $value): ?static
    {
        return static::builder()->where($column, $value)->first();
    }

    public static function create(array $attributes): static
    {
        $model = new static();
        $model->fill($attributes);
        $model->save();
        return $model;
    }

    public static function count(): int
    {
        return static::query()->count();
    }

    public static function tableName(): string
    {
        return static::$table;
    }

    /* ============ نمونه ============ */

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (static::$fillable === [] || in_array($key, static::$fillable, true) || $key === static::$primaryKey) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function save(): bool
    {
        $data = $this->attributes;
        unset($data[static::$primaryKey]);

        if ($this->exists) {
            $changed = [];
            foreach ($data as $k => $v) {
                if (!array_key_exists($k, $this->original) || $this->original[$k] != $v) {
                    $changed[$k] = $v;
                }
            }
            if (static::$timestamps && in_array('updated_at', $this->columns(), true)) {
                $changed['updated_at'] = date('Y-m-d H:i:s');
            }
            if ($changed === []) {
                return true;
            }
            $sets = implode(', ', array_map(fn ($c) => "`{$c}` = ?", array_keys($changed)));
            Database::update(
                'UPDATE `' . static::$table . "` SET {$sets} WHERE `" . static::$primaryKey . '` = ?',
                [...array_values($changed), $this->attributes[static::$primaryKey]]
            );
            $this->original = $this->attributes;
            return true;
        }

        if (static::$timestamps) {
            if (in_array('created_at', $this->columns(), true)) {
                $data['created_at'] ??= date('Y-m-d H:i:s');
            }
            if (in_array('updated_at', $this->columns(), true)) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
        }

        if ($data === []) {
            return false;
        }

        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));
        $id = Database::insert(
            'INSERT INTO `' . static::$table . "` ({$columnList}) VALUES ({$placeholders})",
            array_values($data)
        );
        $this->attributes[static::$primaryKey] = $id;
        $this->original = $this->attributes;
        $this->exists = true;
        return true;
    }

    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        Database::delete(
            'DELETE FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ?',
            [$this->attributes[static::$primaryKey]]
        );
        $this->exists = false;
        return true;
    }

    public function id(): ?int
    {
        return isset($this->attributes[static::$primaryKey]) ? (int) $this->attributes[static::$primaryKey] : null;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __get(string $name): mixed
    {
        $value = $this->attributes[$name] ?? null;
        return $this->castAttribute($name, $value);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function offsetGet(string $name): mixed
    {
        return $this->__get($name);
    }

    /* ============ داخلی ============ */

    public static function hydrate(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->original = $row;
        $model->exists = true;
        return $model;
    }

    /** @return static[] */
    public static function hydrateMany(array $rows): array
    {
        return array_map(fn ($row) => static::hydrate($row), $rows);
    }

    private function castAttribute(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        return match (static::$casts[$key] ?? '') {
            'int'   => (int) $value,
            'float' => (float) $value,
            'bool'  => (bool) $value,
            'json'  => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /** نام ستون‌های جدول (کش به تفکیک جدول) */
    private static array $columnsCache = [];

    private function columns(): array
    {
        $table = static::$table;
        if (!isset(self::$columnsCache[$table])) {
            try {
                $cols = Database::select('SHOW COLUMNS FROM `' . $table . '`');
                self::$columnsCache[$table] = array_column($cols, 'Field');
            } catch (\Throwable) {
                self::$columnsCache[$table] = [];
            }
        }
        return self::$columnsCache[$table];
    }

    private function fillableOrColumns(): array
    {
        return $this->columns();
    }
}
