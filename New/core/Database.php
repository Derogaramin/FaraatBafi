<?php
namespace Core;
use PDO;
class Database {
    public PDO $pdo;
    public function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    public function getLock(string $name, int $timeout = 10): void {
        $this->pdo->exec("SELECT GET_LOCK('$name', $timeout)");
    }
    public function releaseLock(string $name): void {
        $this->pdo->exec("SELECT RELEASE_LOCK('$name')");
    }
}