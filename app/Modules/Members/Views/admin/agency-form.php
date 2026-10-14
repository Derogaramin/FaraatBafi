<?php
/** ویوی فرم نمایندگی — Members::admin.agency-form */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$isEdit = $agency !== null;
$action = $isEdit ? url('admin/agencies/' . $agency['id']) : url('admin/agencies');
$val = fn (string $k, $d = '') => e((string) ($isEdit ? ($agency[$k] ?? $d) : old($k, $d)));
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/agencies') ?>">نمایندگی‌ها</a> <span>/</span> <span><?= $isEdit ? 'ویرایش' : 'جدید' ?></span></div>
    <h1><?= $isEdit ? 'ویرایش نمایندگی ' . e($agency['code']) : 'ثبت نمایندگی جدید' ?></h1>
    <?php if (!$isEdit): ?><div class="page-header__sub">کد نمایندگی (A##) خودکار ساخته می‌شود</div><?php endif; ?>
  </div>
</div>

<form method="post" action="<?= $action ?>" style="max-width:720px">
  <?= csrf_field() ?>
  <div class="card stack">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="name">نام نمایندگی <span class="required">*</span></label>
        <input class="form-control" id="name" name="name" value="<?= $val('name') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="manager_name">نام مدیر نمایندگی</label>
        <input class="form-control" id="manager_name" name="manager_name" value="<?= $val('manager_name') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="phone">شمارهٔ تماس <?= $isEdit ? '' : '<span class="required">*</span>' ?></label>
        <input class="form-control ltr" id="phone" name="phone" inputmode="tel" value="<?= $val('phone') ?>" placeholder="09xxxxxxxxx" <?= $isEdit ? '' : 'required' ?>>
      </div>
      <div class="form-group">
        <label class="form-label" for="commission_rate">نرخ پورسانت (٪) <span class="required">*</span></label>
        <input class="form-control num" id="commission_rate" name="commission_rate" type="number" step="0.5" min="0" max="50" value="<?= $val('commission_rate', '10') ?>" required>
      </div>
    </div>
    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label" for="province">استان</label>
        <input class="form-control" id="province" name="province" value="<?= $val('province', 'خراسان رضوی') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="city">شهر <span class="required">*</span></label>
        <input class="form-control" id="city" name="city" value="<?= $val('city') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="credit_limit">سقف اعتبار (تومان)</label>
        <input class="form-control num" id="credit_limit" name="credit_limit" type="number" min="0" step="100000" value="<?= $val('credit_limit', '0') ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="address">نشانی</label>
      <input class="form-control" id="address" name="address" value="<?= $val('address') ?>">
    </div>
    <?php if (!$isEdit): ?>
    <div class="form-group">
      <label class="form-label" for="password">گذرواژهٔ پنل نمایندگی</label>
      <input class="form-control" id="password" name="password" value="faraat1403" minlength="8">
    </div>
    <?php endif; ?>
    <div class="flex gap-2">
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 16) ?> <?= $isEdit ? 'ذخیره' : 'ثبت نمایندگی' ?></button>
      <a class="btn btn--outline" href="<?= url('admin/agencies') ?>">انصراف</a>
    </div>
  </div>
</form>
