<?php
/** ویوی انواع محصول — Catalog::admin.types */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$counts = array_column($counts, 'c', 'type_id');
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>انواع محصول</span></div>
    <h1>انواع محصول (TYPE)</h1>
    <div class="page-header__sub">بخش اول کد مدل فنی — مثل HT برای حوله</div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 2fr;gap:var(--space-5);align-items:start" id="types-grid">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> نوع جدید</h3>
    <form method="post" action="<?= url('admin/catalog/types') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0">
        <label class="form-label">کد (۲–۳ حرف لاتین بزرگ) <span class="required">*</span></label>
        <input class="form-control ltr" name="code" pattern="[A-Z]{2,3}" placeholder="HT" required>
      </div>
      <div class="form-group m-0">
        <label class="form-label">نام فارسی <span class="required">*</span></label>
        <input class="form-control" name="name_fa" placeholder="حولهٔ حمام" required>
      </div>
      <div class="form-row">
        <div class="form-group m-0">
          <label class="form-label">نام لاتین</label>
          <input class="form-control ltr" name="name_en" dir="ltr" placeholder="Hamam Towel">
        </div>
        <div class="form-group m-0">
          <label class="form-label">ترتیب</label>
          <input class="form-control num" type="number" name="sort_order" value="0" min="0">
        </div>
      </div>
      <div class="form-group m-0">
        <label class="form-label">توضیح</label>
        <textarea class="form-control" name="description" rows="2"></textarea>
      </div>
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ثبت نوع</button>
    </form>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>کد</th><th>نام</th><th>لاتین</th><th>مدل‌ها</th><th>وضعیت</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($types as $t): ?>
        <tr>
          <td><span class="code-chip"><?= e($t->code) ?></span></td>
          <td class="fw-bold"><?= e($t->name_fa) ?></td>
          <td class="ltr text-sm text-muted"><?= e((string) $t->name_en) ?></td>
          <td class="num"><?= fa((string) ($counts[$t->id()] ?? 0)) ?></td>
          <td><?= $t->is_active ? '<span class="badge badge--success">فعال</span>' : '<span class="badge badge--muted">غیرفعال</span>' ?></td>
          <td>
            <form method="post" action="<?= url('admin/catalog/types/' . $t->id() . '/toggle') ?>">
              <?= csrf_field() ?>
              <button class="btn btn--sm btn--ghost" type="submit" title="تغییر وضعیت"><?= icon('refresh', '', 13) ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<style>@media (max-width:900px){#types-grid{grid-template-columns:1fr !important}}</style>
