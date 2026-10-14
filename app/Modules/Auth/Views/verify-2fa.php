<?php
/** ویوی تأیید رمز یک‌بارمصرف — Auth::verify-2fa */
$this->extend('layouts.auth', ['subtitle' => 'احراز هویت دو مرحله‌ای']);
?>
<form method="post" action="<?= url('verify-2fa') ?>">
  <?= csrf_field() ?>
  <div class="text-center mb-4">
    <div style="display:flex;justify-content:center;color:var(--color-secondary);margin-bottom:var(--space-2)"><?= icon('quality', '', 44) ?></div>
    <p class="text-sm text-secondary m-0">
      رمز شش‌رقمی نمایش‌داده‌شده در برنامهٔ احراز هویت خود
      (Google Authenticator / Authy) را وارد کنید.
    </p>
  </div>
  <div class="form-group">
    <label class="form-label" for="code">رمز یک‌بارمصرف <span class="required">*</span></label>
    <input class="form-control ltr text-center num" id="code" name="code" inputmode="numeric"
           pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus
           style="font-size:1.6rem;letter-spacing:.6em;font-weight:700">
  </div>
  <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('check', '', 18) ?> تأیید و ورود</button>
  <div class="auth-footer">
    <a href="<?= url('login') ?>"><?= icon('arrow-right', '', 14) ?> بازگشت</a>
    <span class="text-muted"> • </span>
    <span class="text-sm text-muted">دستگاه را در «امنیت حساب» از فهرست خارج کنید</span>
  </div>
</form>
