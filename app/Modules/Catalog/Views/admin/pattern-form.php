<?php
/** ویوی فرم طرح — Catalog::admin.pattern-form */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$isEdit = $pattern !== null;
$action = $isEdit ? url('admin/catalog/patterns/' . $pattern['id']) : url('admin/catalog/patterns');
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/catalog/patterns') ?>">طرح‌ها</a> <span>/</span> <span><?= $isEdit ? 'ویرایش' : 'جدید' ?></span></div>
    <h1><?= $isEdit ? 'ویرایش طرح ' . e($pattern['code']) : 'ثبت طرح جدید' ?></h1>
    <?php if (!$isEdit): ?><div class="page-header__sub">کد طرح (P####) به‌صورت خودکار و صعودی ساخته می‌شود.</div><?php endif; ?>
  </div>
</div>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" style="max-width:680px">
  <?= csrf_field() ?>
  <div class="card stack">
    <div class="form-group">
      <label class="form-label" for="name_fa">نام طرح <span class="required">*</span></label>
      <input class="form-control" id="name_fa" name="name_fa" value="<?= e($isEdit ? $pattern['name_fa'] : old('name_fa')) ?>" placeholder="ترنج خشتی" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="designer">طراح</label>
        <input class="form-control" id="designer" name="designer" value="<?= e($isEdit ? (string) ($pattern['designer'] ?? '') : '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="motif">سبک نقش‌مایه</label>
        <input class="form-control" id="motif" name="motif" value="<?= e($isEdit ? (string) ($pattern['motif'] ?? '') : '') ?>" placeholder="هندسی / گیاهی">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="image">تصویر طرح</label>
      <input class="form-control" id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml">
      <?php if ($isEdit && !empty($pattern['image'])): ?>
        <div class="form-hint">تصویر فعلی: <span class="ltr"><?= e($pattern['image']) ?></span></div>
      <?php endif; ?>
    </div>
    <div class="form-group">
      <label class="form-label" for="description">توضیحات</label>
      <textarea class="form-control" id="description" name="description" rows="4"><?= e($isEdit ? (string) ($pattern['description'] ?? '') : '') ?></textarea>
    </div>
    <div class="flex gap-2">
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 16) ?> <?= $isEdit ? 'ذخیره' : 'ثبت طرح' ?></button>
      <a class="btn btn--outline" href="<?= url('admin/catalog/patterns') ?>">انصراف</a>
    </div>
  </div>
</form>
