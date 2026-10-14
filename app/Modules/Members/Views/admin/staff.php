<?php
/** ویوی پرسنل — Members::admin.staff */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>پرسنل</span></div>
    <h1>پرسنل اجرایی</h1>
    <div class="page-header__sub">کدگذاری <span class="code-chip">S001</span></div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 2.2fr;gap:var(--space-5);align-items:start" id="staff-grid">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> پرسنل جدید</h3>
    <form method="post" action="<?= url('admin/hr/staff') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0"><label class="form-label">نام <span class="required">*</span></label><input class="form-control" name="name" required></div>
      <div class="form-group m-0"><label class="form-label">موبایل <span class="required">*</span></label><input class="form-control ltr" name="phone" inputmode="tel" required></div>
      <div class="form-group m-0"><label class="form-label">سمت</label><input class="form-control" name="position" placeholder="مدیر تولید / انباردار / حسابدار"></div>
      <div class="form-group m-0"><label class="form-label">حقوق پایه (تومان)</label><input class="form-control num" name="base_salary" type="number" min="0" step="100000" value="0"></div>
      <div class="form-group m-0"><label class="form-label">گذرواژه</label><input class="form-control" name="password" value="faraat1403" minlength="8"></div>
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ثبت پرسنل (کد S خودکار)</button>
    </form>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>کد</th><th>نام</th><th>سمت</th><th>حقوق پایه</th><th>موبایل</th><th>وضعیت</th></tr></thead>
      <tbody>
        <?php foreach ($staff as $s): ?>
        <tr>
          <td><span class="code-chip"><?= e($s['code']) ?></span></td>
          <td class="fw-bold"><?= e($s['name']) ?></td>
          <td><?= e((string) ($s['position'] ?: '—')) ?></td>
          <td class="num"><?= toman((int) $s['base_salary'], false) ?></td>
          <td class="num ltr text-sm"><?= fa((string) $s['phone']) ?></td>
          <td><?= $s['user_status'] === 'active' ? '<span class="badge badge--success badge-dot">فعال</span>' : '<span class="badge badge--muted badge-dot">معلق</span>' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<style>@media (max-width:900px){#staff-grid{grid-template-columns:1fr !important}}</style>
