<?php
/**
 * لایهٔ پنل‌های داخلی (مدیر / نمایندگی / مشتری)
 *
 * متغیرها: $content, $title, $panel ('admin'|'agency'|'account'), $panelTitle
 * منوها به‌صورت ماژولار از همهٔ ماژول‌ها جمع‌آوری و با RBAC فیلتر می‌شوند.
 * ویجت اعلان‌ها نمونهٔ HMVC است: Modules::run() بدون HTTP از ماژول CRM زیردرخواست می‌گیرد.
 */

use Core\App;
use Core\Auth;
use Core\Jalali;
use Core\Modules;

$panel = $panel ?? 'admin';
$menuItems = Modules::collectMenus($panel);
$user = Auth::user();
$today = Jalali::format('l، j F Y');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(($title ?? 'پنل') . ' | فرت') ?></title>
<script>
(function(){try{var t=localStorage.getItem('faraat-theme');if(!t){t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
</script>
<link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
<link rel="stylesheet" href="<?= asset('css/main.css') ?>">
<link rel="stylesheet" href="<?= asset('css/components-extra.css') ?>">
<link rel="stylesheet" href="<?= asset('css/panel.css') ?>">
<link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
<?= $this->section('styles') ?>
</head>
<body>
<a class="skip-link" href="#panel-main">پرش به محتوای اصلی</a>
<div class="panel">

  <aside class="panel-sidebar">
    <div class="panel-sidebar__brand">
      <span class="brand__logo"><?= icon('shuttle', '', 20) ?></span>
      <span>
        <b>پنل <?= e($panelTitle ?? 'مدیریت') ?></b>
        <span>سامانهٔ جامع فرت‌بافی</span>
      </span>
    </div>

    <nav class="panel-menu">
      <div class="panel-menu__group">
        <?php foreach ($menuItems as $item): ?>
          <a href="<?= e($item['url']) ?>" class="<?= menu_match($item['match'] ?? '') ? 'active' : '' ?>">
            <?= icon($item['icon'] ?? 'box', '', 18) ?>
            <span><?= e($item['title']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </nav>

    <div class="panel-sidebar__footer">
      نسخهٔ <?= e((string) \Core\Config::get('app.version')) ?> — <?= e($today) ?>
    </div>
  </aside>
  <div class="sidebar-backdrop"></div>

  <div class="panel-main" id="panel-main">
    <header class="panel-topbar">
      <button class="theme-toggle sidebar-toggle" data-sidebar-toggle aria-label="منو"><?= icon('menu', '', 20) ?></button>
      <div>
        <div class="panel-topbar__title"><?= e($title ?? '') ?></div>
        <div class="panel-topbar__date"><?= e($today) ?></div>
      </div>

      <form class="header-search" action="<?= url('search') ?>" method="get" role="search">
        <?= icon('search', '', 16) ?>
        <input name="q" placeholder="جست‌وجوی کد یا نام… (ORD / WO / FB / SKU)" aria-label="جست‌وجوی جهانی">
      </form>

      <div class="panel-topbar__actions">
        <button class="theme-toggle" data-theme-toggle title="تم روز/شب" aria-label="تغییر تم">
          <?= icon('moon', 'icon-moon', 17) ?><?= icon('sun', 'icon-sun', 17) ?>
        </button>

        <?php if ($panel === 'admin'): ?>
          <!-- ویجت اعلان‌ها — فراخوانی HMVC از ماژول CRM -->
          <?= Modules::run('CRM', 'NotificationController', 'widget')->body() ?>
        <?php else: ?>
          <a class="theme-toggle" href="<?= url('notifications') ?>" title="اعلان‌ها"><?= icon('bell', '', 17) ?></a>
        <?php endif; ?>

        <a class="btn btn--ghost btn--sm btn--icon" href="<?= url('/') ?>" title="مشاهدهٔ سایت"><?= icon('eye', '', 18) ?></a>

        <div class="panel-user" data-dropdown="#user-dd">
          <span class="avatar"><?= e(mb_substr((string) $user['name'], 0, 1)) ?></span>
          <span class="panel-user__info">
            <span class="panel-user__name"><?= e($user['name']) ?></span>
            <span class="panel-user__role"><?= e(Auth::roleLabel()) ?> — <span class="mono-code"><?= e(Auth::code()) ?></span></span>
          </span>
          <?= icon('chevron-down', '', 14) ?>
          <div class="user-dropdown" id="user-dd">
            <a href="<?= e(App::panelHome()) ?>"><?= icon('dashboard', '', 16) ?> داشبورد</a>
            <a href="<?= url('my/profile') ?>"><?= icon('user', '', 16) ?> پروفایل من</a>
            <?php if (\Core\Auth::isAdmin()): ?>
              <a href="<?= url('admin/security') ?>"><?= icon('quality', '', 16) ?> امنیت حساب</a>
            <?php endif; ?>
            <a href="<?= url('notifications') ?>"><?= icon('bell', '', 16) ?> اعلان‌ها</a>
            <hr>
            <form method="post" action="<?= url('logout') ?>" class="m-0">
              <?= csrf_field() ?>
              <button type="submit"><?= icon('logout', '', 16) ?> خروج از سامانه</button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <main class="panel-content">
<?= $content ?>
    </main>
  </div>
</div>

<div class="toast-container" aria-live="polite" role="status"></div>
<?= component("toasts", ["flashes" => $flashes ?? [], "errors" => $session_errors ?? []]) ?>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/jalali-datepicker.js') ?>"></script>
<script>
// بررسی دوره‌ای اعلان‌های نخوانده (نقشهٔ راه ۱٫۵٫۵ — polling؛ SSE در فاز بعد)
(function () {
  var badge = document.querySelector('.notif-bell__badge');
  var url = '<?= url('notifications/unread-count') ?>';
  setInterval(function () {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (badge) { badge.textContent = toFa(d.count); badge.style.display = d.count > 0 ? 'flex' : 'none'; }
      }).catch(function () {});
  }, 30000);
})();
</script>
<?= $this->section('scripts') ?>
</body>
</html>
