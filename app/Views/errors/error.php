<?php
/**
 * صفحهٔ خطا — ۴۰۳/۴۰۴/۴۱۹/۵۰۰ با پوستهٔ برند
 * داده‌ها: status, message
 */
$this->extend('layouts.public');
$icons = [403 => 'alert', 404 => 'search', 419 => 'clock', 500 => 'alert'];
?>
<div class="error-page">
  <div>
    <div class="error-page__code"><?= fa((string) $status) ?></div>
    <div style="color:var(--color-primary);display:flex;justify-content:center;margin:var(--space-3) 0">
      <?= icon($icons[$status] ?? 'alert', '', 48) ?>
    </div>
    <h1><?= e($message) ?></h1>
    <p class="text-muted text-sm mb-4">
      <?php if ($status === 404): ?>
        شاید نشانی را اشتباه وارد کرده‌اید یا این صفحه جابه‌جا شده است.
      <?php elseif ($status === 403): ?>
        نقش کاربری شما اجازهٔ دسترسی به این بخش را ندارد. در صورت نیاز با مدیر سامانه تماس بگیرید.
      <?php elseif ($status === 419): ?>
        لطفاً فرم را مجدداً ارسال کنید.
      <?php else: ?>
        خطا در گزارش‌های سامانه ثبت شد. اگر تکرار شد با پشتیبانی تماس بگیرید.
      <?php endif; ?>
    </p>
    <div class="flex" style="justify-content:center">
      <a class="btn btn--primary" href="<?= url('/') ?>"><?= icon('arrow-right', '', 16) ?> صفحهٔ اصلی</a>
      <a class="btn btn--outline" href="<?= url('marketplace') ?>">بازارگاه</a>
    </div>
  </div>
</div>
