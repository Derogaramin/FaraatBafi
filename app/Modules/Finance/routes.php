<?php
/**
 * مسیرهای ماژول Finance — دستمزدها، پورسانت‌ها، اسناد و گزارش‌ها
 */

declare(strict_types=1);

use App\Modules\Finance\Controllers\AdminFinanceController;
use Core\Router;

return static function (Router $router): void {
    $router->group(['prefix' => '/admin/finance', 'middleware' => ['auth', 'can:payroll.view']], function (Router $r): void {
        $r->get('/payments', [AdminFinanceController::class, 'payments'], 'admin.finance.payments');
        $r->get('/payments/{id:int}/print', [AdminFinanceController::class, 'printPayment'], 'admin.finance.payments.print');
        $r->post('/payments', [AdminFinanceController::class, 'storePayment'], null, ['csrf']);
        $r->post('/payments/{id:int}/pay', [AdminFinanceController::class, 'markPaid'], null, ['csrf']);
        $r->get('/commissions', [AdminFinanceController::class, 'commissions'], 'admin.finance.commissions');
        $r->post('/commissions/{id:int}/status', [AdminFinanceController::class, 'commissionStatus'], null, ['csrf', '2fa']);
        $r->get('/reports', [AdminFinanceController::class, 'reports'], 'admin.finance.reports');
        $r->get('/documents', [AdminFinanceController::class, 'documents'], 'admin.finance.documents');
        $r->post('/documents', [AdminFinanceController::class, 'storeDocument'], null, ['csrf', 'can:finance.manage', '2fa']);
    });
};
