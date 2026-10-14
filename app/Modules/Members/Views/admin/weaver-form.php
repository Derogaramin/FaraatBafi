<?php
/** ویوی فرم بافنده — Members::admin.weaver-form */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$isEdit = $weaver !== null;
$action = $isEdit ? url('admin/weavers/' . $weaver['id']) : url('admin/weavers');
$val = fn (string $k, $d = '') => e((string) ($isEdit ? ($weaver[$k] ?? $d) : old($k, $d)));
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/weavers') ?>">بافندگان</a> <span>/</span> <span><?= $isEdit ? 'ویرایش' : 'جدید' ?></span></div>
    <h1><?= $isEdit ? 'ویرایش بافندهٔ ' . e($weaver['code']) : 'ثبت بافندهٔ جدید' ?></h1>
    <?php if (!$isEdit): ?><div class="page-header__sub">کد بافندگی (W###) خودکار ساخته می‌شود</div><?php endif; ?>
  </div>
</div>

<form method="post" action="<?= $action ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <div class="card stack">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="name">نام و نام خانوادگی <span class="required">*</span></label>
        <input class="form-control" id="name" name="name" value="<?= $val('name') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="phone">شمارهٔ موبایل <?= $isEdit ? '' : '<span class="required">*</span>' ?></label>
        <input class="form-control ltr" id="phone" name="phone" inputmode="tel" value="<?= $val('phone') ?>" placeholder="09xxxxxxxxx" <?= $isEdit ? '' : 'required' ?>>
        <?php if (!$isEdit): ?><div class="form-hint">برای ورود بافنده به پنل استفاده می‌شود.</div><?php endif; ?>
      </div>
    </div>
    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label" for="national_id">کد ملی</label>
        <input class="form-control ltr num" id="national_id" name="national_id" inputmode="numeric" maxlength="10" value="<?= $val('national_id') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="city">شهر</label>
        <input class="form-control" id="city" name="city" value="<?= $val('city', 'تربت جام') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="village">روستا</label>
        <input class="form-control" id="village" name="village" value="<?= $val('village') ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="address">نشانی</label>
      <input class="form-control" id="address" name="address" value="<?= $val('address') ?>">
    </div>
    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label" for="specialty">تخصص بافت</label>
        <input class="form-control" id="specialty" name="specialty" value="<?= $val('specialty') ?>" placeholder="حوله / شال / دستمال">
      </div>
      <div class="form-group">
        <label class="form-label" for="skill_level">سطح مهارت</label>
        <select class="form-control" id="skill_level" name="skill_level">
          <?php foreach ($skills as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= ($isEdit && $weaver['skill_level'] === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="loom_count">تعداد دستگاه بافت</label>
        <input class="form-control num" id="loom_count" name="loom_count" type="number" min="1" max="20" value="<?= $val('loom_count', '1') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="wage_rate">نرخ دستمزد هر واحد (تومان) <span class="required">*</span></label>
        <input class="form-control num" id="wage_rate" name="wage_rate" type="number" min="0" step="1000" value="<?= $val('wage_rate', '150000') ?>" required>
      </div>
      <?php if (!$isEdit): ?>
      <div class="form-group">
        <label class="form-label">پنل ورود بافنده</label>
        <label class="checkbox mt-2"><input type="checkbox" name="with_login" value="1" checked> ساخت حساب کاربری (گذرواژهٔ پیش‌فرض: <span class="ltr code">faraat1403</span>)</label>
      </div>
      <?php endif; ?>
    </div>
    <div class="form-group">
      <label class="form-label" for="note">یادداشت</label>
      <textarea class="form-control" id="note" name="note" rows="2"><?= $val('note') ?></textarea>
    </div>
    <div class="flex gap-2">
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 16) ?> <?= $isEdit ? 'ذخیرهٔ تغییرات' : 'ثبت بافنده' ?></button>
      <a class="btn btn--outline" href="<?= url('admin/weavers') ?>">انصراف</a>
    </div>
  </div>
</form>
