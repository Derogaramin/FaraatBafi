<?php
/**
 * لایهٔ سایت عمومی — هدر + محتوا + فوتر
 * متغیرها: $content, $title
 */

use Core\App;
use Core\Auth;

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(($title ?? '') . ' | ' . 'فرت — تعاونی نقشینه بافت تربت جام') ?></title>
<meta name="description" content="سامانهٔ جامع تعاونی تولیدی صنایع دستی نقشینه بافت تربت جام — دست‌بافته‌های اصیل با کد اصالت QR">
<script>
// اعمال تم پیش از رندر CSS (جلوگیری از FOUC)
(function(){try{var t=localStorage.getItem('faraat-theme');if(!t){t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
</script>
<link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
<link rel="stylesheet" href="<?= asset('css/main.css') ?>">
<link rel="stylesheet" href="<?= asset('css/components-extra.css') ?>">
<link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
<?= $this->section('styles') ?>
</head>
<body>
<a class="skip-link" href="#main-content">پرش به محتوای اصلی</a>

<header class="site-header">
  <div class="container site-header__inner">
    <a class="brand" href="<?= url('/') ?>">
      <span class="brand__logo"><?= icon('shuttle', '', 22) ?></span>
      <span>
        <b class="brand__title">فرت</b>
        <span class="brand__subtitle">نقشینه بافت تربت جام</span>
      </span>
    </a>

    <form class="header-search" action="<?= url('search') ?>" method="get" role="search">
      <?= icon('search', '', 16) ?>
      <input name="q" placeholder="جست‌وجوی محصول، کد اصالت، سفارش…" aria-label="جست‌وجو">
    </form>

    <nav class="site-nav" id="site-nav">
      <a href="<?= url('/') ?>" class="<?= menu_active('/', '/') ?>">خانه</a>
      <a href="<?= url('marketplace') ?>" class="<?= menu_active('marketplace') ?>">بازارگاه</a>
      <a href="<?= url('blog') ?>" class="<?= menu_active('blog') ?>">وبلاگ</a>
      <a href="<?= url('authenticity') ?>" class="<?= menu_active('authenticity') ?>">استعلام اصالت</a>
      <a href="<?= url('orders/track') ?>" class="<?= menu_active('orders/track') ?>">پیگیری سفارش</a>
      <a href="<?= url('about') ?>" class="<?= menu_active('about') ?>">دربارهٔ ما</a>
    </nav>

    <div class="site-header__actions">
      <button class="theme-toggle" data-theme-toggle title="تغییر تم روز/شب" aria-label="تغییر تم">
        <?= icon('moon', 'icon-moon', 18) ?><?= icon('sun', 'icon-sun', 18) ?>
      </button>

      <a class="btn btn--outline btn--sm cart-btn" href="<?= url('cart') ?>" data-drawer-open title="سبد خرید">
        <?= icon('cart', '', 18) ?>
        <span data-cart-count><?= fa((string) ($cart_count ?? 0)) ?></span>
      </a>

      <?php if ($user): ?>
        <a class="btn btn--sm" href="<?= e(App::panelHome()) ?>"><?= icon('dashboard', '', 16) ?> پنل من</a>
      <?php else: ?>
        <a class="btn btn--sm" href="<?= url('login') ?>"><?= icon('login', '', 16) ?> ورود</a>
        <a class="btn btn--sm btn--outline" href="<?= url('register') ?>">ثبت‌نام</a>
      <?php endif; ?>

      <button class="theme-toggle mobile-menu-btn" data-mobile-menu="#site-nav" aria-label="منو"><?= icon('menu', '', 20) ?></button>
    </div>
  </div>
</header>

<main id="main-content">
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="container site-footer__grid">
    <div>
      <div class="brand mb-3">
        <span class="brand__logo"><?= icon('shuttle', '', 22) ?></span>
        <span><b class="brand__title">فرت</b><br><span class="brand__subtitle">تعاونی تولیدی صنایع دستی نقشینه بافت تربت جام</span></span>
      </div>
      <p class="text-sm">
        بیش از ۱۵ سال بافتِ روایتِ رنگ و نخ؛ از دستان صد بافندهٔ خراسان تا خانهٔ شما.
        هر محصول فرت دارای <b>کد اصالت یکتا (FB)</b> و QR شناسنامه است.
      </p>
    </div>
    <div>
      <h4>دسترسی سریع</h4>
      <ul>
        <li><a href="<?= url('marketplace') ?>">بازارگاه</a></li>
        <li><a href="<?= url('authenticity') ?>">استعلام اصالت</a></li>
        <li><a href="<?= url('orders/track') ?>">پیگیری سفارش</a></li>
        <li><a href="<?= url('blog') ?>">وبلاگ</a></li>
      </ul>
    </div>
    <div>
      <h4>سامانه</h4>
      <ul>
        <li><a href="<?= url('login') ?>">ورود اعضا و بافندگان</a></li>
        <li><a href="<?= url('register') ?>">ثبت‌نام مشتریان</a></li>
        <li><a href="<?= url('about') ?>">دربارهٔ تعاونی</a></li>
        <li><a href="<?= url('contact') ?>">تماس با ما</a></li>
      </ul>
    </div>
    <div>
      <h4>استعلام اصالت محصول</h4>
      <p class="text-sm">کد FB روی برچسب محصول را وارد کنید:</p>
      <form class="footer-check" onsubmit="if(this.q.value.trim()){location.href='<?= url('authenticity') ?>/'+encodeURIComponent(this.q.value.trim().toUpperCase());}return false;">
        <input name="q" placeholder="FB-1405-0012-W001" aria-label="کد اصالت">
        <button class="btn btn--sm btn--primary" type="submit"><?= icon('qr', '', 15) ?></button>
      </form>
      <ul class="mt-3">
        <li class="flex gap-2"><?= icon('map-pin', '', 16) ?> خراسان رضوی، تربت جام</li>
        <li class="flex gap-2"><?= icon('phone', '', 16) ?> <span class="num ltr">051-34567890</span></li>
      </ul>
    </div>
  </div>
  <div class="site-footer__bottom">
    <div class="container">
      © <?= fa((string) \Core\Jalali::currentYear()) ?> تعاونی نقشینه بافت تربت جام — سامانهٔ جامع مدیریت فرت‌بافی، نسخهٔ <?= e((string) \Core\Config::get('app.version')) ?>
    </div>
  </div>
</footer>

<!-- سبد خرید کشویی (طبق مستندات UI) -->
<div class="drawer-backdrop" id="drawer-backdrop" data-drawer-close></div>
<aside class="cart-drawer" id="cart-drawer" data-drawer-url="<?= url('cart/drawer') ?>" data-drawer-remove-url="<?= url('cart/remove') ?>" aria-label="سبد خرید">
  <div class="drawer__body">
    <div class="drawer__loading">در حال بارگذاری سبد…</div>
  </div>
</aside>

<div class="toast-container" aria-live="polite" role="status"></div>
<?= component("toasts", ["flashes" => $flashes ?? [], "errors" => $session_errors ?? []]) ?>
<script src="<?= asset('js/app.js') ?>"></script>
<?= $this->section('scripts') ?>
</body>
</html>
