<?php
/**
 * مسیرهای ماژول Admin — داشبورد، هوش تجاری، حاکمیت، تنظیمات
 */

declare(strict_types=1);

use App\Modules\Admin\Controllers\AuditsController;
use App\Modules\Admin\Controllers\BiController;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\GovernanceController;
use App\Modules\Admin\Controllers\SecurityController;
use App\Modules\Admin\Controllers\SettingsController;
use Core\Router;

return static function (Router $router): void {
    $router->group(['prefix' => '/admin', 'middleware' => ['auth', 'can:admin.access']], function (Router $r): void {
        $r->get('', [DashboardController::class, 'index'], 'admin.dashboard');

        // هوش تجاری
        $r->get('/bi', [BiController::class, 'index'], 'admin.bi');
        $r->get('/bi/weavers', [BiController::class, 'weavers'], 'admin.bi.weavers');

        // حاکمیت: جلسات، مصوبات، رأی‌گیری، مکاتبات
        $r->get('/governance/meetings', [GovernanceController::class, 'meetings'], 'admin.meetings');
        $r->post('/governance/meetings', [GovernanceController::class, 'storeMeeting'], null, ['csrf', '2fa']);
        $r->get('/governance/meetings/{id:int}', [GovernanceController::class, 'meetingShow'], 'admin.meetings.show');
        $r->post('/governance/meetings/{id:int}/minutes', [GovernanceController::class, 'saveMinutes'], null, ['csrf', '2fa']);
        $r->post('/governance/meetings/{id:int}/resolution', [GovernanceController::class, 'addResolution'], null, ['csrf', '2fa']);
        $r->get('/governance/votes', [GovernanceController::class, 'votes'], 'admin.votes');
        $r->post('/governance/votes', [GovernanceController::class, 'storeVote'], null, ['csrf', '2fa']);
        $r->post('/governance/votes/{id:int}/ballot', [GovernanceController::class, 'castBallot'], null, ['csrf']);
        $r->get('/governance/letters', [GovernanceController::class, 'letters'], 'admin.letters');
        $r->post('/governance/letters', [GovernanceController::class, 'storeLetter'], null, ['csrf', '2fa']);

        // امنیت حساب (2FA) و ممیزی
        $r->get('/security', [SecurityController::class, 'index'], 'admin.security');
        $r->post('/security/enable', [SecurityController::class, 'enable'], null, ['csrf']);
        $r->post('/security/disable', [SecurityController::class, 'disable'], null, ['csrf']);
        $r->get('/audits', [AuditsController::class, 'index'], 'admin.audits');

        // تنظیمات — تغییرات با 2FA محافظت می‌شود
        $r->get('/settings', [SettingsController::class, 'edit'], 'admin.settings');
        $r->post('/settings', [SettingsController::class, 'update'], null, ['csrf', '2fa']);
    });
};
