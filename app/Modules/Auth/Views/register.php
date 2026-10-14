<?php
/** ویوی ثبت‌نام مشتری — Auth::register */
$this->extend('layouts.auth', ['subtitle' => 'با ثبت‌نام، کد کاربری C (مشتری) دریافت می‌کنید']);
?>
<form method="post" action="<?= url('register') ?>">
  <?= csrf_field() ?>
  <div class="form-group">
    <label class="form-label" for="name">نام و نام خانوادگی <span class="required">*</span></label>
    <input class="form-control" id="name" name="name" value="<?= e(old('name')) ?>" required autofocus>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label class="form-label" for="phone">شمارهٔ موبایل <span class="required">*</span></label>
      <input class="form-control ltr" id="phone" name="phone" inputmode="tel" placeholder="09xxxxxxxxx" value="<?= e(old('phone')) ?>" required>
    </div>
    <div class="form-group">
      <label class="form-label" for="email">رایانامه <span class="text-muted">(اختیاری)</span></label>
      <input class="form-control ltr" id="email" name="email" type="email" dir="ltr" value="<?= e(old('email')) ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label class="form-label" for="city">شهر <span class="text-muted">(اختیاری)</span></label>
      <input class="form-control" id="city" name="city" value="<?= e(old('city')) ?>">
    </div>
    <div class="form-group">
      <label class="form-label" for="province">استان <span class="text-muted">(اختیاری)</span></label>
      <input class="form-control" id="province" name="province" value="<?= e(old('province')) ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label class="form-label" for="password">گذرواژه <span class="required">*</span></label>
      <input class="form-control" id="password" name="password" type="password" minlength="8" required>
      <div class="form-hint">حداقل ۸ نویسه</div>
    </div>
    <div class="form-group">
      <label class="form-label" for="password_confirmation">تکرار گذرواژه <span class="required">*</span></label>
      <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required>
    </div>
  </div>
  <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('user', '', 18) ?> ثبت‌نام و دریافت کد مشتری</button>
  <div class="auth-footer">حساب دارید؟ <a href="<?= url('login') ?>">ورود</a></div>
</form>
