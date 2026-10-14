<?php
/**
 * لایهٔ پنل بافنده — Mobile-first با ناوبری پایین
 */

use Core\Auth;
use Core\Jalali;
use Core\Modules;

$user = Auth::user();
$menuItems = Modules::collectMenus('weaver');
$currentPath = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(($title ?? 'پنل بافنده') . ' | فرت') ?></title>
<script>
(function(){try{var t=localStorage.getItem('faraat-theme');if(!t){t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
</script>
<link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
<link rel="stylesheet" href="<?= asset('css/main.css') ?>">
<link rel="stylesheet" href="<?= asset('css/components-extra.css') ?>">
<link rel="stylesheet" href="<?= asset('css/weaver.css') ?>">
<link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
<?= $this->section('styles') ?>
</head>
<body>
<div class="weaver-shell">

  <header class="weaver-topbar">
    <span class="brand__logo" style="width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--color-primary),#5E1F28);color:#F5E7C8">
      <?= icon('shuttle', '', 18) ?>
    </span>
    <span class="weaver-topbar__title"><?= e($title ?? 'پنل بافنده') ?></span>
    <button class="theme-toggle" data-theme-toggle style="width:36px;height:36px" aria-label="تغییر تم">
      <?= icon('moon', 'icon-moon', 16) ?><?= icon('sun', 'icon-sun', 16) ?>
    </button>
    <a class="theme-toggle" href="<?= url('notifications') ?>" style="width:36px;height:36px;text-decoration:none" title="اعلان‌ها"><?= icon('bell', '', 16) ?></a>
    <form method="post" action="<?= url('logout') ?>" class="m-0">
      <?= csrf_field() ?>
      <button class="theme-toggle" type="submit" style="width:36px;height:36px" title="خروج"><?= icon('logout', '', 16) ?></button>
    </form>
  </header>

  <main class="weaver-content">
<?= $content ?>
  </main>

  <nav class="bottom-nav">
    <div class="bottom-nav__inner">
      <?php foreach ($menuItems as $item): ?>
        <a href="<?= e($item['url']) ?>" class="<?= menu_match($item['match'] ?? '') ? 'active' : '' ?>">
          <?= icon($item['icon'] ?? 'box', '', 22) ?>
          <span><?= e($item['title']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </nav>
</div>

<div class="toast-container" aria-live="polite" role="status"></div>
<?= component("toasts", ["flashes" => $flashes ?? [], "errors" => $session_errors ?? []]) ?>
<script src="<?= asset('js/app.js') ?>"></script>
<?= $this->section('scripts') ?>
</body>
</html>
