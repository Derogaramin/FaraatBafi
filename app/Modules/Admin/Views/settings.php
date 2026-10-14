<?php
/** ویوی تنظیمات — Admin::settings */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>تنظیمات</span></div>
    <h1>تنظیمات سامانه</h1>
  </div>
</div>

<form method="post" action="<?= url('admin/settings') ?>" style="max-width:640px">
  <?= csrf_field() ?>
  <div class="card stack">
    <div class="form-group">
      <label class="form-label" for="coop_name">نام تعاونی <span class="required">*</span></label>
      <input class="form-control" id="coop_name" name="coop_name" value="<?= e($settings['coop_name']) ?>" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="brand_name">نام برند <span class="required">*</span></label>
        <input class="form-control" id="brand_name" name="brand_name" value="<?= e($settings['brand_name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="phone">تلفن تماس</label>
        <input class="form-control ltr" id="phone" name="phone" value="<?= e($settings['phone']) ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="address">نشانی دفتر مرکزی</label>
      <input class="form-control" id="address" name="address" value="<?= e($settings['address']) ?>">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="shipping_flat">هزینهٔ ارسال پایه (تومان)</label>
        <input class="form-control num" id="shipping_flat" name="shipping_flat" type="number" min="0" step="5000" value="<?= e($settings['shipping_flat']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="free_shipping">حد ارسال رایگان (تومان)</label>
        <input class="form-control num" id="free_shipping" name="free_shipping" type="number" min="0" step="100000" value="<?= e($settings['free_shipping']) ?>">
      </div>
    </div>
    <button class="btn btn--primary" type="submit"><?= icon('check', '', 16) ?> ذخیرهٔ تنظیمات</button>
  </div>
</form>

<div class="card mt-4" style="max-width:640px">
  <h3 class="card__title mb-3"><?= icon('info', '', 18) ?> دربارهٔ سامانه</h3>
  <dl class="spec-list">
    <dt>نام</dt><dd><?= e((string) \Core\Config::get('app.name')) ?></dd>
    <dt>نسخه</dt><dd><?= e((string) \Core\Config::get('app.version')) ?></dd>
    <dt>PHP</dt><dd class="ltr"><?= e(PHP_VERSION) ?></dd>
    <dt>معماری</dt><dd>ماژولار HMVC + رویدادمحور (ED) + MVC</dd>
    <dt>پایگاه داده</dt><dd>MySQL/MariaDB — <?= fa('42') ?> جدول</dd>
  </dl>
</div>
