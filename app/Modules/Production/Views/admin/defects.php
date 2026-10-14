<?php
/** ویوی انواع عیب بافت — Production::admin.defects */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$sevLabels = ['minor' => 'جزئی', 'major' => 'عمده', 'critical' => 'بحرانی'];
$sevColors = ['minor' => 'success', 'major' => 'warning', 'critical' => 'danger'];
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>انواع عیب</span></div>
    <h1>انواع عیب بافت</h1>
    <div class="page-header__sub">کدگذاری <span class="code-chip">DEF-CLR-01</span> — دسته + شمارهٔ ترتیبی</div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 2fr;gap:var(--space-5);align-items:start" id="def-grid">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> عیب جدید</h3>
    <form method="post" action="<?= url('admin/inventory/defects') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0">
        <label class="form-label">نام عیب <span class="required">*</span></label>
        <input class="form-control" name="name_fa" placeholder="پارگی پود" required>
      </div>
      <div class="form-row">
        <div class="form-group m-0">
          <label class="form-label">دسته (۳ حرف لاتین) <span class="required">*</span></label>
          <input class="form-control ltr" name="category" pattern="[A-Z]{3}" placeholder="CLR" required style="text-transform:uppercase">
          <div class="form-hint">CLR رنگ | WVP بافت | STC دوخت | DIM ابعاد</div>
        </div>
        <div class="form-group m-0">
          <label class="form-label">شدت <span class="required">*</span></label>
          <select class="form-control" name="severity" required>
            <?php foreach ($sevLabels as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ثبت عیب</button>
    </form>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>کد</th><th>نام</th><th>دسته</th><th>شدت</th></tr></thead>
      <tbody>
        <?php foreach ($defects as $d): ?>
        <tr>
          <td><span class="code-chip"><?= e($d->code) ?></span></td>
          <td class="fw-bold"><?= e($d->name_fa) ?></td>
          <td><?= e($d->category) ?></td>
          <td><span class="badge badge--<?= $sevColors[$d->severity] ?? 'muted' ?>"><?= e($sevLabels[$d->severity] ?? $d->severity) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<style>@media (max-width:900px){#def-grid{grid-template-columns:1fr !important}}</style>
