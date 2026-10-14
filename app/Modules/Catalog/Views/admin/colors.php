<?php
/** ویوی کتابخانهٔ رنگ — Catalog::admin.colors */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>رنگ‌ها</span></div>
    <h1>کتابخانهٔ رنگ</h1>
    <div class="page-header__sub">کد سه‌نویسه‌ای: حرف خانواده + دو رقم — مثل <span class="code-chip">R01</span> قرمز روناس</div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 2fr;gap:var(--space-5);align-items:start" id="colors-grid">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> رنگ جدید</h3>
    <form method="post" action="<?= url('admin/catalog/colors') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group m-0">
          <label class="form-label">حرف خانواده <span class="required">*</span></label>
          <input class="form-control ltr text-center" name="family_letter" pattern="[A-Za-z]" maxlength="1" placeholder="R" required style="text-transform:uppercase">
          <div class="form-hint">R قرمز | I نیلی | G سبز | Y زرد | B آبی…</div>
        </div>
        <div class="form-group m-0">
          <label class="form-label">کد هگز <span class="required">*</span></label>
          <input class="form-control ltr" name="hex" type="color" value="#8B2E3A" style="padding:4px;height:48px">
        </div>
      </div>
      <div class="form-group m-0">
        <label class="form-label">نام سنتی رنگ <span class="required">*</span></label>
        <input class="form-control" name="name_fa" placeholder="قرمز روناس" required>
      </div>
      <div class="form-group m-0">
        <label class="form-label">خانواده</label>
        <input class="form-control" name="family" placeholder="گرم">
      </div>
      <label class="checkbox"><input type="checkbox" name="is_natural" value="1" checked> رنگرزی طبیعی (گیاهی/معدنی)</label>
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ثبت رنگ</button>
      <p class="form-hint m-0">شمارهٔ رنگ به‌صورت خودکار و صعودی در خانواده تولید می‌شود.</p>
    </form>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>کد</th><th>نمونه</th><th>نام</th><th>هگز</th><th>خانواده</th><th>طبیعی</th><th>وضعیت</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($colors as $c): ?>
        <tr>
          <td><span class="code-chip"><?= e($c->code) ?></span></td>
          <td><span class="color-dot" style="width:26px;height:26px;background:<?= e($c->hex) ?>"></span></td>
          <td class="fw-bold"><?= e($c->name_fa) ?></td>
          <td class="ltr text-sm"><?= e($c->hex) ?></td>
          <td><?= e((string) $c->family) ?></td>
          <td><?= $c->is_natural ? '🌿' : '—' ?></td>
          <td><?= $c->is_active ? '<span class="badge badge--success">فعال</span>' : '<span class="badge badge--muted">غیرفعال</span>' ?></td>
          <td>
            <form method="post" action="<?= url('admin/catalog/colors/' . $c->id() . '/toggle') ?>">
              <?= csrf_field() ?>
              <button class="btn btn--sm btn--ghost" type="submit"><?= icon('refresh', '', 13) ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<style>@media (max-width:900px){#colors-grid{grid-template-columns:1fr !important}}</style>
