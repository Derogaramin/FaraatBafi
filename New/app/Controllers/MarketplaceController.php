<?php

namespace App\Controllers;

use Core\Controller;
use Core\Application;
use App\Models\ProductVariant;
use App\Models\Order;
use Core\CodeGenerator;

class MarketplaceController extends Controller
{
    public function index(): string
    {
        $variants = ProductVariant::activeList();
        return $this->render('marketplace.catalog', ['products' => $variants]);
    }
    public function show(string $sku): string
    {
        $variant = ProductVariant::findBySku($sku);
        if (!$variant) { http_response_code(404); return $this->render('errors.404'); }
        return $this->render('marketplace.product', ['product' => $variant]);
    }
    public function cart(): string
    {
        // سبد خرید از session
        return $this->render('marketplace.cart');
    }
    public function addToCart(): never
    {
        // منطق اضافه کردن
    }
    public function checkout(): string
    {
        // ...
    }
    public function placeOrder(): never
    {
        $db = Application::$app->db;
        $user = Application::$app->auth->user();
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) redirect('/cart');
        $db->pdo->beginTransaction();
        try {
            $orderCode = CodeGenerator::generateOrderCode();
            $stmt = $db->pdo->prepare("INSERT INTO orders (order_code, customer_id, total_amount) VALUES (:code, :cid, :total)");
            $stmt->execute(['code' => $orderCode, 'cid' => $user['id'], 'total' => 0]); // total calculated
            $orderId = $db->pdo->lastInsertId();
            // آیتم‌ها و تخصیص به تولید ...
            $db->pdo->commit();
            unset($_SESSION['cart']);
            redirect('/order/' . $orderCode);
        } catch (\Throwable $e) {
            $db->pdo->rollBack();
            throw $e;
        }
    }
}