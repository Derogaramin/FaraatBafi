<?php
namespace App\Controllers;
use Core\Controller;
use Core\Application;
use App\Models\Order;

class DashboardController extends Controller
{
    public function index(): string
    {
        $user = Application::$app->auth->user();
        if (!$user) redirect('/login');
        return match ($user['role']) {
            'member', 'admin' => $this->adminDashboard($user),
            'weaver' => $this->render('production.weaver.my-orders', ['orders' => []]),
            'agent' => $this->render('agencies.agent-dashboard', []),
            default => $this->render('auth.login'),
        };
    }
    private function adminDashboard(array $user): string
    {
        // داده‌های KPI
        return $this->render('admin.dashboard', [
            'kpi' => [
                'orders_today' => 12,
                'monthly_income' => 12500000,
                'active_weavers' => 87,
                'pending_qc' => 5,
            ]
        ]);
    }
    public function products(): string { /* ... */ }
    public function orders(): string { /* ... */ }
}