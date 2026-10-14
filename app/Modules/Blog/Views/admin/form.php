<?php
/** ویوی فرم نوشته — Blog::admin.form */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$isEdit = $post !== null;
$action = $isEdit ? url('admin/blog/' . $post['id']) : url('admin/blog');
$val = fn (string $k, $d = '') => e((string) ($isEdit ? ($post[$k] ?? $d) : old($k, $d)));
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/blog') ?>">وبلاگ</a> <span>/</span> <span><?= $isEdit ? 'ویرایش' : 'جدید' ?></span></div>
    <h1><?= $isEdit ? 'ویرایش نوشته' : 'نوشتهٔ جدید' ?></h1>
  </div>
</div>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" style="max-width:820px">
  <?= csrf_field() ?>
  <div class="card stack">
    <div class="form-group">
      <label class="form-label" for="title">عنوان <span class="required">*</span></label>
      <input class="form-control" id="title" name="title" value="<?= $val('title') ?>" required>
    </div>
    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label" for="slug">نامک (لاتین/فارسی)</label>
        <input class="form-control" id="slug" name="slug" value="<?= $val('slug') ?>" placeholder="خالی = ساخته می‌شود">
      </div>
      <div class="form-group">
        <label class="form-label" for="category">دسته</label>
        <input class="form-control" id="category" name="category" value="<?= $val('category', 'blog') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="status">وضعیت</label>
        <select class="form-control" id="status" name="status">
          <option value="draft" <?= ($isEdit && $post['status'] === 'draft') ? 'selected' : '' ?>>پیش‌نویس</option>
          <option value="published" <?= ($isEdit && $post['status'] === 'published') ? 'selected' : '' ?>>منتشرشده</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="excerpt">خلاصه</label>
      <textarea class="form-control" id="excerpt" name="excerpt" rows="2" maxlength="400"><?= $val('excerpt') ?></textarea>
    </div>
    <div class="form-group">
      <label class="form-label" for="body">متن <span class="required">*</span></label>
      <textarea class="form-control" id="body" name="body" rows="14" style="line-height:2.2" required><?= $val('body') ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="image">تصویر شاخص</label>
        <input class="form-control" id="image" name="image" type="file" accept="image/*">
      </div>
      <div class="form-group">
        <label class="form-label" for="tags">برچسب‌ها (با کاما)</label>
        <input class="form-control" id="tags" name="tags" value="<?= $val('tags') ?>" placeholder="بافت، رنگ طبیعی، تربت جام">
      </div>
    </div>
    <div class="flex gap-2">
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 16) ?> <?= $isEdit ? 'ذخیره' : 'انتشار' ?></button>
      <a class="btn btn--outline" href="<?= url('admin/blog') ?>">انصراف</a>
    </div>
  </div>
</form>
