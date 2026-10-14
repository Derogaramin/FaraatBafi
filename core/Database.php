<?php
/**
 * کلاس Database — لایهٔ اتصال PDO به MySQL/MariaDB
 *
 * تمام پرس‌وجوها با Prepared Statement انجام می‌شود (محافظت در برابر SQL Injection).
 */

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    /** @var array{host:string,port:int,name:string,user:string,pass:string,charset:string}|null */
    private static ?array $override = null;

    /** دریافت اتصال یکتا (Lazy Singleton) */
    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = self::$override ?? [
            'host'    => (string) Config::get('database.host', '127.0.0.1'),
            'port'    => (int) Config::get('database.port', 3306),
            'name'    => (string) Config::get('database.name', 'faraatbafi'),
            'user'    => (string) Config::get('database.user', 'root'),
            'pass'    => (string) Config::get('database.pass', ''),
            'charset' => (string) Config::get('database.charset', 'utf8mb4'),
        ];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['name'],
            $cfg['charset']
        );

        try {
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // هم‌ترازی منطقهٔ زمانی DB با PHP — تا NOW() و date() یکسان باشند
            // (رفع باگ اختلاف ۳٫۵ ساعته در مقایسه‌های زمانی مثل Throttle و KPIها)
            $offsetSeconds = (new \DateTimeZone(date_default_timezone_get()))->getOffset(new \DateTime());
            $sign = $offsetSeconds < 0 ? '-' : '+';
            $abs = abs($offsetSeconds);
            $tz = sprintf('%s%02d:%02d', $sign, intdiv($abs, 3600), intdiv($abs % 3600, 60));
            self::$pdo->exec("SET time_zone = '{$tz}'");
        } catch (PDOException $e) {
            throw new RuntimeException('خطا در اتصال به پایگاه داده: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        return self::$pdo;
    }

    /** جایگزینی تنظیمات اتصال (برای تست) */
    public static function setConfig(array $config): void
    {
        self::$override = $config;
        self::$pdo = null;
    }

    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function disconnect(): void
    {
        self::$pdo = null;
    }

    /** اجرای پرس‌وجو و دریافت همهٔ ردیف‌ها */
    public static function select(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** اجرای پرس‌وجو و دریافت اولین ردیف */
    public static function selectOne(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** درج ردیف و برگرداندن شناسهٔ درج‌شده */
    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::connection()->lastInsertId();
    }

    public static function update(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    public static function delete(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    public static function statement(string $sql, array $params = []): PDOStatement
    {
        return self::run($sql, $params);
    }

    /** اجرای تراکنش — در صورت خطا Rollback می‌شود */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        if ($pdo->inTransaction()) {
            return $callback($pdo);
        }
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
