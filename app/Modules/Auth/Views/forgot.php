<?php
/** ویوی بازیابی گذرواژه — Auth::forgot */
$this->extend('layouts.auth', ['subtitle' => 'بازیابی گذرواژه با شمارهٔ موبایل']);
?>
<form method="post" action="<?= url('forgot-password') ?>">
  <?= csrf_field() ?>
  <p class="text-sm text-secondary">
    شمارهٔ موبایلی که با آن در سامانه ثبت‌نام کرده‌اید وارد کنید تا راهنمای بازنشانی ارسال شود.
  </p>
  <div class="form-group">
    <label class="form-label" for="phone">شمارهٔ موبایل <span class="required">*</span></label>
    <input class="form-control ltr" id="phone" name="phone" inputmode="tel" placeholder="09xxxxxxxxx" required autofocus>
  </div>
  <button class="btn btn--primary btn--block" type="submit"><?= icon('refresh', '', 18) ?> ارسال راهنمای بازیابی</button>
  <div class="auth-footer"><a href="<?= url('login') ?>"><?= icon('arrow-right', '', 14) ?> بازگشت به ورود</a></div>
</form>
