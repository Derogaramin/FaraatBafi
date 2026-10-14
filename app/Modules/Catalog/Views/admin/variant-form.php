<?php
/** ویوی فرم واریانت — Catalog::admin.variant-form (با پیش‌نمایش زندهٔ SKU) */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$isEdit = $variant !== null;
$action = $isEdit ? url('admin/catalog/variants/' . $variant['id']) : url('admin/catalog/variants');
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/catalog/variants') ?>">محصولات</a> <span>/</span> <span><?= $isEdit ? 'ویرایش' : 'جدید' ?></span></div>
    <h1><?= $isEdit ? 'ویرایش واریانت' : 'واریانت فروشگاهی جدید' ?></h1>
    <div class="page-header__sub">SKU از ترکیب مدل فنی + رنگ + طرح ساخته می‌شود و یکتاست.</div>
  </div>
</div>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" style="max-width:720px"
      <?= $isEdit ? '' : 'data-sku-preview="' . url('admin/catalog/sku-preview') . '"' ?>>
  <?= csrf_field() ?>
  <div class="card stack">
    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label" for="model_id">مدل فنی <span class="required">*</span></label>
        <select class="form-control" id="model_id" name="model_id" <?= $isEdit ? 'disabled' : 'required' ?>>
          <option value="">انتخاب…</option>
          <?php foreach ($models as $m): ?>
            <option value="<?= $m->id() ?>" <?= ($isEdit && (int) $variant['model_id'] === (int) $m->id()) ? 'selected' : '' ?>>
              <?= e($m->name_fa) ?> — <?= e($m->code) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($isEdit): ?><input type="hidden" name="model_id" value="<?= (int) $variant['model_id'] ?>"><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label" for="color_id">رنگ <span class="required">*</span></label>
        <select class="form-control" id="color_id" name="color_id" <?= $isEdit ? 'disabled' : 'required' ?>>
          <option value="">انتخاب…</option>
          <?php foreach ($colors as $c): ?>
            <option value="<?= $c->id() ?>" <?= ($isEdit && (int) $variant['color_id'] === (int) $c->id()) ? 'selected' : '' ?>>
              <?= e($c->name_fa) ?> (<?= e($c->code) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($isEdit): ?><input type="hidden" name="color_id" value="<?= (int) $variant['color_id'] ?>"><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label" for="pattern_id">طرح <span class="required">*</span></label>
        <select class="form-control" id="pattern_id" name="pattern_id" <?= $isEdit ? 'disabled' : 'required' ?>>
          <option value="">انتخاب…</option>
          <?php foreach ($patterns as $p): ?>
            <option value="<?= $p->id() ?>" <?= ($isEdit && (int) $variant['pattern_id'] === (int) $p->id()) ? 'selected' : '' ?>>
              <?= e($p->name_fa) ?> (<?= e($p->code) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($isEdit): ?><input type="hidden" name="pattern_id" value="<?= (int) $variant['pattern_id'] ?>"><?php endif; ?>
      </div>
    </div>

    <div class="card" style="background:var(--color-action-light);border:none">
      <div class="text-sm">SKU تولیدشده:</div>
      <div class="mono-code text-lg fw-bold" data-sku-output><?= $isEdit ? e($variant['sku']) : '— انتخاب کنید —' ?></div>
      <div class="form-hint" data-sku-hint></div>
    </div>

    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label" for="price">قیمت فروش (تومان) <span class="required">*</span></label>
        <input class="form-control num" id="price" name="price" type="number" min="1000" step="1000" value="<?= e($isEdit ? (string) $variant['price'] : (string) old('price')) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="cost">بهای تمام‌شده</label>
        <input class="form-control num" id="cost" name="cost" type="number" min="0" step="1000" value="<?= e($isEdit ? (string) $variant['cost'] : (string) old('cost', '0')) ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="stock">موجودی انبار</label>
        <input class="form-control num" id="stock" name="stock" type="number" min="0" value="<?= e($isEdit ? (string) $variant['stock'] : (string) old('stock', '0')) ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="image">تصویر محصول</label>
      <input class="form-control" id="image" name="image" type="file" accept="image/*">
      <?php if ($isEdit && !empty($variant['image'])): ?><div class="form-hint">تصویر فعلی: <span class="ltr"><?= e($variant['image']) ?></span></div><?php endif; ?>
    </div>

    <label class="checkbox">
      <input type="checkbox" name="is_featured" value="1" <?= ($isEdit && (int) $variant['is_featured'] === 1) ? 'checked' : '' ?>>
      نمایش به‌عنوان محصول ویژه در صفحهٔ اصلی
    </label>

    <div class="flex gap-2">
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 16) ?> <?= $isEdit ? 'ذخیرهٔ تغییرات' : 'ثبت واریانت' ?></button>
      <a class="btn btn--outline" href="<?= url('admin/catalog/variants') ?>">انصراف</a>
    </div>
    <?php if ($isEdit): ?>
      <p class="form-hint m-0">طبق مستندات FUCS، تغییر مدل/رنگ/طرح کد SKU را عوض می‌کند؛ برای ترکیب جدید، واریانت تازه بسازید.</p>
    <?php endif; ?>
  </div>
</form>
