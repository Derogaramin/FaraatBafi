<?php
namespace App\Models;
use Core\Model;

class Order extends Model
{
    protected static string $table = 'orders';

    public static function findByCode(string $code): ?static
    {
        $stmt = static::db()->prepare("SELECT * FROM orders WHERE order_code = :code");
        $stmt->execute(['code' => $code]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, static::class);
        return $stmt->fetch() ?: null;
    }

    public static function getOrdersByCustomer(int $customerId): array
    {
        $stmt = static::db()->prepare("SELECT * FROM orders WHERE customer_id = :cid ORDER BY order_date DESC");
        $stmt->execute(['cid' => $customerId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
    }
}