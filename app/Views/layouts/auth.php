<?php
/**
 * لایهٔ صفحات احراز هویت — کارت مرکزی
 */
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(($title ?? 'ورود') . ' | فرت') ?></title>
<script>
(function(){try{var t=localStorage.getItem('faraat-theme');if(!t){t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
</script>
<link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
<link rel="stylesheet" href="<?= asset('css/main.css') ?>">
<link rel="stylesheet" href="<?= asset('css/components-extra.css') ?>">
<link rel="stylesheet" href="<?= asset('css/auth.css') ?>">
<link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
</head>
<body>
<div class="auth-page">
  <!-- پنل برند (دسکتاپ) -->
  <aside class="auth-aside">
    <div class="brand" style="color:#F5E7C8">
      <span class="brand__logo" style="background:rgba(245,231,200,.14)"><?= icon('shuttle', '', 24) ?></span>
      <span><b class="brand__title" style="color:#F5E7C8">فرت</b><br><span style="font-size:.72rem;opacity:.8">نقشینه بافت تربت جام</span></span>
    </div>
    <blockquote>
      «هر تار و پودِ فرت، روایتِ دستان یک استادکار است؛
      و هر کد اصالت، امضای همین روایت.»
      <cite>— تعاونی صنایع دستی نقشینه بافت، از ۱۳۸۷</cite>
    </blockquote>
    <div class="aside-img">
      <img src="<?= e(weave_visual('#8B2E3A', 'hero-1')) ?>" alt="">
      <img src="<?= e(weave_visual('#1A3B4C', 'hero-2')) ?>" alt="">
      <img src="<?= e(weave_visual('#4A6B4A', 'hero-3')) ?>" alt="">
    </div>
    <div class="aside-stats">
      <div><b>۲۴</b><span>بافندهٔ فعال</span></div>
      <div><b>۵</b><span>نمایندگی</span></div>
      <div><b>FB</b><span>کد اصالت QR</span></div>
    </div>
  </aside>
  <div>
    <div class="auth-card">
      <div class="auth-card__brand">
        <a href="<?= url('/') ?>" class="brand" style="justify-content:center">
          <span class="brand__logo"><?= icon('shuttle', '', 26) ?></span>
        </a>
        <h1>فرت — نقشینه بافت</h1>
        <p><?= e($subtitle ?? 'سامانهٔ جامع مدیریت تعاونی فرت‌بافی') ?></p>
      </div>
<?= $content ?>
      </div>
    <div class="text-center mt-4">
      <a class="text-sm text-muted" href="<?= url('/') ?>"><?= icon('arrow-right', '', 14) ?> بازگشت به سایت</a>
      <button class="theme-toggle" data-theme-toggle style="width:34px;height:34px;margin-inline-start:8px" title="تغییر تم" aria-label="تغییر تم">
        <?= icon('moon', 'icon-moon', 15) ?><?= icon('sun', 'icon-sun', 15) ?>
      </button>
    </div>
  </div>
</div>
<div class="toast-container"></div>
<?= component("toasts", ["flashes" => $flashes ?? [], "errors" => $session_errors ?? []]) ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
