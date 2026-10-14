<?php
/** ویوی فرم مدل فنی — Catalog::admin.model-form (داده: model|null, types, weaves, knots, stitches) */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$isEdit = $model !== null;
$action = $isEdit ? url('admin/catalog/models/' . $model['id']) : url('admin/catalog/models');
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/catalog/models') ?>">مدل‌های فنی</a> <span>/</span> <span><?= $isEdit ? 'ویرایش' : 'جدید' ?></span></div>
    <h1><?= $isEdit ? 'ویرایش مدل ' . e($model['code']) : 'مدل فنی جدید' ?></h1>
  </div>
</div>

<form method="post" action="<?= $action ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <div class="card stack">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="type_id">نوع محصول <span class="required">*</span></label>
        <select class="form-control" id="type_id" name="type_id" required>
          <option value="">انتخاب کنید…</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= $t->id() ?>" <?= ($isEdit && (int) $model['type_id'] === (int) $t->id()) ? 'selected' : '' ?>>
              <?= e($t->name_fa) ?> (<?= e($t->code) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="name_fa">نام نمایشی مدل <span class="required">*</span></label>
        <input class="form-control" id="name_fa" name="name_fa" value="<?= e($isEdit ? $model['name_fa'] : old('name_fa')) ?>" placeholder="حولهٔ حمام دولا" required>
      </div>
    </div>

    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label" for="weave">پودگذاری <span class="required">*</span></label>
        <select class="form-control" id="weave" name="weave" required>
          <?php foreach ($weaves as $code => $label): ?>
            <option value="<?= e($code) ?>" <?= ($isEdit && $model['weave'] === $code) ? 'selected' : '' ?>><?= e($label) ?> (<?= e($code) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="knot">گره <span class="required">*</span></label>
        <select class="form-control" id="knot" name="knot" required>
          <?php foreach ($knots as $code => $label): ?>
            <option value="<?= e($code) ?>" <?= ($isEdit && $model['knot'] === $code) ? 'selected' : '' ?>><?= e($label) ?> (<?= e($code) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="stitch">دوخت <span class="required">*</span></label>
        <select class="form-control" id="stitch" name="stitch" required>
          <?php foreach ($stitches as $code => $label): ?>
            <option value="<?= e($code) ?>" <?= ($isEdit && $model['stitch'] === $code) ? 'selected' : '' ?>><?= e($label) ?> (<?= e($code) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label" for="chelle">چله <span class="required">*</span></label>
        <input class="form-control num" id="chelle" name="chelle" type="number" step="0.1" min="1" max="99"
               value="<?= e($isEdit ? (string) $model['chelle'] : (string) old('chelle', '12.5')) ?>" required>
        <div class="form-hint">در کد مدل در ۱۰ ضرب می‌شود: ۱۲٫۵ → CH125</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="base_price">قیمت پایه (تومان) <span class="required">*</span></label>
        <input class="form-control num" id="base_price" name="base_price" type="number" min="0" step="1000"
               value="<?= e($isEdit ? (string) $model['base_price'] : (string) old('base_price')) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="weight_gr">وزن (گرم)</label>
        <input class="form-control num" id="weight_gr" name="weight_gr" type="number" min="0"
               value="<?= e($isEdit ? (string) ($model['weight_gr'] ?? '') : '') ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="size_cm">ابعاد (سانتی‌متر)</label>
      <input class="form-control ltr" id="size_cm" name="size_cm" placeholder="70×140" value="<?= e($isEdit ? (string) ($model['size_cm'] ?? '') : '') ?>">
    </div>

    <div class="form-group">
      <label class="form-label" for="description">توضیحات فنی</label>
      <textarea class="form-control" id="description" name="description" rows="3"><?= e($isEdit ? (string) ($model['description'] ?? '') : '') ?></textarea>
    </div>

    <div class="card" style="background:var(--color-action-light);border:none">
      <div class="text-sm">کد مدل به‌صورت خودکار ساخته می‌شود:</div>
      <div class="mono-code text-lg fw-bold" id="model-code-preview">[TYPE]-[WEAVE][KNOT][STITCH]-CH[XX]</div>
    </div>

    <div class="flex gap-2">
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 16) ?> <?= $isEdit ? 'ذخیرهٔ تغییرات' : 'ثبت مدل فنی' ?></button>
      <a class="btn btn--outline" href="<?= url('admin/catalog/models') ?>">انصراف</a>
    </div>
  </div>
</form>

<script>
// پیش‌نمایش زندهٔ کد مدل
(function () {
  const typeEl = document.getElementById('type_id');
  const weaveEl = document.getElementById('weave');
  const knotEl = document.getElementById('knot');
  const stitchEl = document.getElementById('stitch');
  const chelleEl = document.getElementById('chelle');
  const out = document.getElementById('model-code-preview');
  function update() {
    const t = typeEl.selectedOptions[0] ? (typeEl.selectedOptions[0].textContent.match(/\(([A-Z]{2,3})\)/) || [])[1] : '';
    const ch = Math.round((parseFloat(chelleEl.value) || 0) * 10);
    out.textContent = (t || 'TYPE') + '-' + weaveEl.value + knotEl.value + stitchEl.value + '-CH' + (ch || 'XX');
  }
  [typeEl, weaveEl, knotEl, stitchEl, chelleEl].forEach(el => el.addEventListener('input', update));
  update();
})();
</script>
