<?php
/** ویوی امنیت حساب — Admin::security */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>امنیت حساب</span></div>
    <h1>امنیت حساب کاربری</h1>
    <div class="page-header__sub">کد کاربری <span class="code-chip"><?= e(\Core\Auth::code()) ?></span> — نقش: <?= e(\Core\Auth::roleLabel()) ?></div>
  </div>
</div>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('quality', '', 18) ?> احراز هویت دو مرحله‌ای (TOTP)</h3>
    <?php if ($enabled): ?>
      <div class="card mb-3" style="background:var(--color-success-light);border:none">
        <b class="text-success"><?= icon('check', '', 16) ?> فعال است</b>
        <p class="text-sm m-0 mt-1">عملیات حساس (مالی، حاکمیت، تنظیمات) با رمز یک‌بارمصرف محافظت می‌شوند.</p>
      </div>
      <form method="post" action="<?= url('admin/security/disable') ?>" class="stack"
            data-confirm="احراز دو مرحله‌ای غیرفعال شود؟ سطح محافظت حساب کاهش می‌یابد." data-confirm-danger>
        <?= csrf_field() ?>
        <div class="form-group m-0">
          <label class="form-label" for="password">گذرواژهٔ فعلی (برای غیرفعال‌سازی)</label>
          <input class="form-control" id="password" name="password" type="password" required>
        </div>
        <button class="btn btn--danger btn--sm" type="submit"><?= icon('x', '', 14) ?> غیرفعال‌سازی</button>
      </form>
    <?php else: ?>
      <p class="text-sm text-secondary">
        ۱) این QR را با برنامهٔ احراز هویت (Google Authenticator / Authy) اسکن کنید
        یا کلید را دستی وارد کنید. ۲) رمز شش‌رقمی نمایش‌داده‌شده را در فرم زیر بزنید.
      </p>
      <div class="text-center mb-3">
        <div class="qr-box"><?= $qrSvg ?></div>
        <div class="mt-2"><span class="code-chip" style="font-size:.85rem;letter-spacing:2px"><?= e($secret) ?></span></div>
      </div>
      <form method="post" action="<?= url('admin/security/enable') ?>" class="flex gap-2">
        <?= csrf_field() ?>
        <input class="form-control ltr num text-center" name="code" inputmode="numeric" pattern="[0-9]{6}"
               maxlength="6" placeholder="------" required style="max-width:160px;font-size:1.2rem;letter-spacing:.4em">
        <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> فعال‌سازی</button>
      </form>
      <p class="form-hint mt-2 mb-0">پس از فعال‌سازی، برای عملیات حساس باید رمز یک‌بارمصرف را در ابتدای نشست تأیید کنید.</p>
    <?php endif; ?>
  </div>

  <div class="stack">
    <?php if ($mustChange): ?>
      <div class="card" style="border-color:var(--color-warning);background:var(--color-warning-light)">
        <b class="text-warning"><?= icon('alert', '', 16) ?> تغییر گذرواژهٔ الزامی</b>
        <p class="text-sm m-0 mt-1">حساب شما با گذرواژهٔ پیش‌فرض ساخته شده است. همین حالا از <a href="<?= url('my/profile') ?>">پروفایل</a> آن را تغییر دهید.</p>
      </div>
    <?php endif; ?>

    <div class="card">
      <h3 class="card__title mb-3"><?= icon('clock', '', 18) ?> آخرین تلاش‌های ورود</h3>
      <?php if ($logins === []): ?>
        <p class="text-sm text-muted m-0">تلاشی ثبت نشده است.</p>
      <?php else: ?>
        <table class="table" style="min-width:0">
          <thead><tr><th>شناسه</th><th>IP</th><th>نتیجه</th><th>زمان</th></tr></thead>
          <tbody>
            <?php foreach ($logins as $l): ?>
            <tr>
              <td class="mono-code text-sm"><?= e($l['identifier']) ?></td>
              <td class="ltr text-sm"><?= e($l['ip']) ?></td>
              <td><?= $l['success'] ? '<span class="badge badge--success">موفق</span>' : '<span class="badge badge--danger">ناموفق</span>' ?></td>
              <td class="text-sm"><?= jdt($l['attempted_at'], 'Y/m/d', true) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3 class="card__title mb-2"><?= icon('info', '', 18) ?> سیاست‌های فعال سامانه</h3>
      <ul class="text-sm text-secondary" style="line-height:2.2;padding-inline-start:var(--space-4);margin:0">
        <li>قفل ورود پس از ۵ تلاش ناموفق (۱۵ دقیقه)</li>
        <li>انقضای نشست پس از ۳۰ دقیقه بی‌فعالی</li>
        <li>تغییر گذرواژهٔ اجباری برای حساب‌های با گذرواژهٔ پیش‌فرض</li>
        <li>CSRF روی همهٔ فرم‌ها + هدرهای CSP/HSTS</li>
      </ul>
    </div>
  </div>
</div>
