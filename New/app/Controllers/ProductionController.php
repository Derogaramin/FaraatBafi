<?php

namespace App\Controllers;

use Core\Controller;
use Core\Application;
use App\Models\WorkOrder;
use Core\CodeGenerator;

class ProductionController extends Controller
{
    // پنل بافنده: لیست سفارش‌های من
    public function weaverOrders(): string
    {
        $user = Application::$app->auth->user();
        if ($user['role'] !== 'weaver') {
            return $this->render('errors.403');
        }
        $workOrders = WorkOrder::findByWeaver($user['id']);
        return $this->render('production.weaver.my-orders', ['orders' => $workOrders]);
    }

    // تغییر وضعیت توسط بافنده
    public function updateStatus(): never
    {
        $user = Application::$app->auth->user();
        if ($user['role'] !== 'weaver') {
            http_response_code(403); exit;
        }
        $id = $_POST['work_order_id'] ?? null;
        $newStatus = $_POST['status'] ?? null;
        $allowedTransitions = [
            'pending' => 'materials_delivered',
            'materials_delivered' => 'weaving',
            'weaving' => 'completed',
        ];

        $db = Application::$app->db;
        $wo = $db->pdo->prepare("SELECT * FROM work_orders WHERE id = :id AND weaver_id = :wid");
        $wo->execute(['id' => $id, 'wid' => $user['id']]);
        $wo = $wo->fetch();
        if (!$wo || !isset($allowedTransitions[$wo['status']]) || $allowedTransitions[$wo['status']] !== $newStatus) {
            http_response_code(400); exit;
        }

        $db->pdo->prepare("UPDATE work_orders SET status = :status, " . ($newStatus == 'materials_delivered' ? "materials_delivered_date = CURDATE(), " : "") . ($newStatus == 'weaving' ? "weaving_start_date = CURDATE(), " : "") . ($newStatus == 'completed' ? "completed_date = CURDATE(), " : "") . "updated_at = NOW() WHERE id = :id")
              ->execute(['status' => $newStatus, 'id' => $id]);

        if ($newStatus == 'completed') {
            // تولید کد اصالت
            $this->generateAuthenticity($wo);
        }

        $this->json(['status' => 'success']);
    }

    private function generateAuthenticity(array $wo): void
    {
        $db = Application::$app->db;
        $year = jdate('Y');
        $series = jdate('md');
        $weaverCode = $db->pdo->prepare("SELECT code FROM users WHERE id = :id")->execute(['id' => $wo['weaver_id']])->fetchColumn();
        $code = "FB-{$year}-{$series}-{$weaverCode}";
        $qrUrl = "https://faraat.ir/a/{$code}";
        $db->pdo->prepare("INSERT INTO authenticity_codes (code, work_order_id, sku, weaver_id, produced_date, qr_url) VALUES (:code, :wo_id, :sku, :wid, CURDATE(), :qr)")
              ->execute(['code' => $code, 'wo_id' => $wo['id'], 'sku' => $wo['sku'], 'wid' => $wo['weaver_id'], 'qr' => $qrUrl]);
    }
}