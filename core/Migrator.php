<?php
/**
 * کلاس Migrator — اجرای مهاجرت‌های SQL (database/migrations/*.sql)
 *
 * مهاجرت‌ها به‌ترتیب نام اجرا و در جدول migrations ثبت می‌شوند.
 */

declare(strict_types=1);

namespace Core;

use RuntimeException;

final class Migrator
{
    public static function path(): string
    {
        return App::basePath('database/migrations');
    }

    /** اجرای مهاجرت‌های باقی‌مانده */
    public static function migrate(bool $quiet = false): array
    {
        self::ensureTable();
        $ran = array_column(Database::select('SELECT migration FROM migrations'), 'migration');
        $executed = [];

        foreach (self::files() as $file) {
            $name = basename($file);
            if (in_array($name, $ran, true)) {
                continue;
            }
            $sql = (string) file_get_contents($file);
            self::execMulti($sql);
            Database::insert('INSERT INTO migrations (migration, executed_at) VALUES (?, NOW())', [$name]);
            $executed[] = $name;
            if (!$quiet) {
                echo "✓ مهاجرت اجرا شد: {$name}\n";
            }
        }
        if (!$quiet && $executed === []) {
            echo "همهٔ مهاجرت‌ها پیش‌تر اجرا شده‌اند.\n";
        }
        return $executed;
    }

    /** حذف همهٔ جداول و اجرای مجدد مهاجرت‌ها */
    public static function fresh(): void
    {
        $tables = array_column(
            Database::select("SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()"),
            't'
        );
        Database::statement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            Database::statement("DROP TABLE IF EXISTS `{$table}`");
        }
        Database::statement('SET FOREIGN_KEY_CHECKS = 1');
        self::migrate();
    }

    /** @return string[] */
    private static function files(): array
    {
        $files = glob(self::path() . '/*.sql') ?: [];
        sort($files);
        return $files;
    }

    private static function ensureTable(): void
    {
        Database::statement(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(190) NOT NULL UNIQUE,
                executed_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** اجرای چند دستور SQL جداشده با ; (با آگاهی از رشته‌ها) */
    public static function execMulti(string $sql): void
    {
        // حذف کامنت‌های -- و /* */
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $statements = array_filter(array_map('trim', explode(";\n", $sql . "\n")), fn ($s) => $s !== '' && $s !== ';');
        foreach ($statements as $statement) {
            $statement = rtrim($statement, "; \n\t");
            if ($statement === '') {
                continue;
            }
            try {
                Database::statement($statement);
            } catch (\PDOException $e) {
                throw new RuntimeException(
                    "خطا در اجرای مهاجرت:\n" . mb_substr($statement, 0, 300) . "\n→ " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }
    }
}
