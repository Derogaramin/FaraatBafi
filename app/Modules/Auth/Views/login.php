<?php
/** ویوی ورود — Auth::login */
$this->extend('layouts.auth', ['subtitle' => 'ورود اعضا، بافندگان، نمایندگی‌ها و مشتریان']);
?>
<form method="post" action="<?= url('login') ?>">
  <?= csrf_field() ?>
  <div class="form-group">
    <label class="form-label" for="identifier">کد کاربری / شمارهٔ موبایل / رایانامه <span class="required">*</span></label>
    <input class="form-control" id="identifier" name="identifier" value="<?= e(old('identifier')) ?>"
           placeholder="مثلاً W001 یا 09151234567" required autofocus autocomplete="username">
  </div>
  <div class="form-group">
    <label class="form-label" for="password">گذرواژه <span class="required">*</span></label>
    <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
  </div>
  <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('login', '', 18) ?> ورود به سامانه</button>
  <div class="auth-footer">
    <a href="<?= url('forgot-password') ?>">گذرواژهٔ خود را فراموش کرده‌اید؟</a>
    <span class="text-muted"> • </span>
    <a href="<?= url('register') ?>">ثبت‌نام مشتری جدید</a>
  </div>
</form>

<?php if ($demoHint ?? false): ?>
<div class="demo-accounts">
  <div class="demo-accounts__title"><?= icon('info', '', 14) ?> حساب‌های نمونه (گذرواژهٔ همه: <span class="ltr code">faraat1403</span>)</div>
  <table>
    <tr><td>عضو اصلی / مدیر</td><td class="ltr">M001</td><td><button type="button" class="fill-btn" data-fill-login="M001">انتخاب</button></td></tr>
    <tr><td>مدیر اجرایی</td><td class="ltr">S001</td><td><button type="button" class="fill-btn" data-fill-login="S001">انتخاب</button></td></tr>
    <tr><td>بافنده</td><td class="ltr">W001</td><td><button type="button" class="fill-btn" data-fill-login="W001">انتخاب</button></td></tr>
    <tr><td>نمایندگی</td><td class="ltr">A01</td><td><button type="button" class="fill-btn" data-fill-login="A01">انتخاب</button></td></tr>
    <tr><td>مشتری</td><td class="ltr">C000001</td><td><button type="button" class="fill-btn" data-fill-login="C000001">انتخاب</button></td></tr>
  </table>
</div>
<script>
document.querySelectorAll('[data-fill-login]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.getElementById('identifier').value = btn.getAttribute('data-fill-login');
    document.getElementById('password').value = 'faraat1403';
    document.getElementById('password').focus();
  });
});
</script>
<?php endif; ?>
