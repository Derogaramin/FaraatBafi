<?php
namespace Core;
class CodeGenerator {
    public static function generateWeaverCode(): string {
        $db = Application::$app->db;
        $db->getLock('weaver_code');
        $stmt = $db->pdo->query("SELECT MAX(CAST(SUBSTRING(code,2) AS UNSIGNED)) FROM users WHERE role='weaver'");
        $max = $stmt->fetchColumn();
        $next = ($max ?? 0) + 1;
        $code = 'W' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
        $db->releaseLock('weaver_code');
        return $code;
    }
    public static function generateCustomerCode(): string {
        // similar logic, prefix C, 5 digits
        return 'C' . str_pad((string)self::getNextSeq('users', "role='customer'", 'C', 5), 5, '0', STR_PAD_LEFT);
    }
    public static function generateOrderCode(): string {
        $year = jdate('Y');
        $lockName = "order_code_{$year}";
        Application::$app->db->getLock($lockName);
        $stmt = Application::$app->db->pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(code,'-',-1) AS UNSIGNED)) FROM orders WHERE code LIKE :prefix");
        $stmt->execute(['prefix' => "ORD-$year-%"]);
        $max = $stmt->fetchColumn();
        $next = ($max ?? 0) + 1;
        $code = "ORD-$year-" . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
        Application::$app->db->releaseLock($lockName);
        return $code;
    }
    public static function generateWorkOrderCode(): string {
        $year = jdate('Y');
        $lockName = "wo_code_{$year}";
        Application::$app->db->getLock($lockName);
        $stmt = Application::$app->db->pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(code,'-',-1) AS UNSIGNED)) FROM work_orders WHERE code LIKE :prefix");
        $stmt->execute(['prefix' => "WO-$year-%"]);
        $max = $stmt->fetchColumn();
        $next = ($max ?? 0) + 1;
        $code = "WO-$year-" . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
        Application::$app->db->releaseLock($lockName);
        return $code;
    }
    // more methods: generateSKU, generateAuthenticityCode etc.
    private static function getNextSeq(string $table, string $condition, string $prefix, int $digits): int {
        $db = Application::$app->db;
        $db->getLock('seq_' . $prefix);
        $stmt = $db->pdo->query("SELECT MAX(CAST(SUBSTRING(code,2) AS UNSIGNED)) FROM $table WHERE $condition");
        $max = $stmt->fetchColumn();
        $next = ($max ?? 0) + 1;
        $db->releaseLock('seq_' . $prefix);
        return $next;
    }
}