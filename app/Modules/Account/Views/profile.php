<?php
/** ویوی پروفایل مشتری — Account::profile */
$this->extend('layouts.panel', ['panel' => 'account', 'panelTitle' => 'پنل مشتری']);
$u = $user;
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('my') ?>">میز کار</a> <span>/</span> <span>پروفایل</span></div>
    <h1>پروفایل من</h1>
    <div class="page-header__sub">کد کاربری: <span class="code-chip"><?= e($u['code']) ?></span> • نقش: <?= e(\Core\Auth::roleLabel()) ?></div>
  </div>
</div>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('user', '', 18) ?> اطلاعات حساب</h3>
    <form method="post" action="<?= url('my/profile') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0">
        <label class="form-label" for="name">نام و نام خانوادگی <span class="required">*</span></label>
        <input class="form-control" id="name" name="name" value="<?= e($u['name']) ?>" required>
      </div>
      <div class="form-group m-0">
        <label class="form-label" for="email">رایانامه</label>
        <input class="form-control ltr" id="email" name="email" type="email" dir="ltr" value="<?= e((string) ($u['email'] ?? '')) ?>">
      </div>
      <div class="form-group m-0">
        <label class="form-label">موبایل</label>
        <input class="form-control ltr num" value="<?= fa($u['phone']) ?>" disabled>
        <div class="form-hint">برای تغییر موبایل با پشتیبانی تماس بگیرید.</div>
      </div>
      <?php if ($customer): ?>
      <div class="form-row">
        <div class="form-group m-0"><label class="form-label" for="province">استان</label><input class="form-control" id="province" name="province" value="<?= e((string) ($customer['province'] ?? '')) ?>"></div>
        <div class="form-group m-0"><label class="form-label" for="city">شهر</label><input class="form-control" id="city" name="city" value="<?= e((string) ($customer['city'] ?? '')) ?>"></div>
      </div>
      <div class="form-group m-0">
        <label class="form-label" for="address">نشانی</label>
        <textarea class="form-control" id="address" name="address" rows="2"><?= e((string) ($customer['address'] ?? '')) ?></textarea>
      </div>
      <?php endif; ?>
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ذخیرهٔ تغییرات</button>
    </form>
  </div>

  <div class="card">
    <h3 class="card__title mb-3"><?= icon('settings', '', 18) ?> تغییر گذرواژه</h3>
    <form method="post" action="<?= url('my/password') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0">
        <label class="form-label" for="current_password">گذرواژهٔ فعلی <span class="required">*</span></label>
        <input class="form-control" id="current_password" name="current_password" type="password" required>
      </div>
      <div class="form-group m-0">
        <label class="form-label" for="password">گذرواژهٔ جدید <span class="required">*</span></label>
        <input class="form-control" id="password" name="password" type="password" minlength="8" required>
      </div>
      <div class="form-group m-0">
        <label class="form-label" for="password_confirmation">تکرار گذرواژهٔ جدید <span class="required">*</span></label>
        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required>
      </div>
      <button class="btn btn--secondary" type="submit"><?= icon('refresh', '', 15) ?> تغییر گذرواژه</button>
    </form>
  </div>
</div>
