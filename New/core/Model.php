<?php

namespace Core;

use Core\Application;

abstract class Model {
    protected static string $table;
    protected static string $primaryKey = 'id';
    public static function db(): \PDO {
        return Application::$app->db->pdo;
    }
    public static function find(int|string $id): ?static {
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchObject(static::class) ?: null;
    }
    public static function all(): array {
        $stmt = static::db()->query("SELECT * FROM " . static::$table);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
    }
    // more CRUD methods to be added
}