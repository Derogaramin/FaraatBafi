<?php
namespace App\Models;
use Core\Model;
use Core\CodeGenerator;
use Core\Application;

class User extends Model
{
    protected static string $table = 'users';
    public static function findByMobile(string $mobile): ?static
    {
        $stmt = static::db()->prepare("SELECT * FROM users WHERE mobile = :m");
        $stmt->execute(['m' => $mobile]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, static::class);
        return $stmt->fetch() ?: null;
    }
    public static function createCustomer(array $data): static
    {
        $code = CodeGenerator::generateCustomerCode();
        $stmt = static::db()->prepare("INSERT INTO users (code, full_name, mobile, password, role) VALUES (:code, :name, :mobile, :pass, 'customer')");
        $stmt->execute([
            'code' => $code,
            'name' => $data['full_name'],
            'mobile' => $data['mobile'],
            'pass' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
        return static::find((int)static::db()->lastInsertId());
    }
}