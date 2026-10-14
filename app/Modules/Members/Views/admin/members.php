<?php
/** ویوی اعضای تعاونی — Members::admin.members */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>اعضا</span></div>
    <h1>اعضای اصلی تعاونی</h1>
    <div class="page-header__sub"><?= fa((string) count($members)) ?> عضو — مجموع <?= fa((string) $totalShares) ?> سهم</div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 2.2fr;gap:var(--space-5);align-items:start" id="mem-grid">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> عضو جدید</h3>
    <form method="post" action="<?= url('admin/hr/members') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0"><label class="form-label">نام <span class="required">*</span></label><input class="form-control" name="name" required></div>
      <div class="form-group m-0"><label class="form-label">موبایل <span class="required">*</span></label><input class="form-control ltr" name="phone" inputmode="tel" required></div>
      <div class="form-group m-0"><label class="form-label">کد ملی</label><input class="form-control ltr num" name="national_id" maxlength="10"></div>
      <div class="form-group m-0"><label class="form-label">سمت</label><input class="form-control" name="position" placeholder="رئیس هیئت‌مدیره / عضو / بازرس"></div>
      <div class="form-row">
        <div class="form-group m-0"><label class="form-label">تعداد سهام <span class="required">*</span></label><input class="form-control num" name="shares" type="number" min="1" value="1" required></div>
        <div class="form-group m-0"><label class="form-label">ارزش سهم (تومان)</label><input class="form-control num" name="share_value" type="number" min="0" value="0"></div>
      </div>
      <div class="form-group m-0"><label class="form-label">گذرواژه</label><input class="form-control" name="password" value="faraat1403" minlength="8"></div>
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ثبت عضو (کد M خودکار)</button>
    </form>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>کد</th><th>نام</th><th>سمت</th><th>سهام</th><th>موبایل</th><th>آخرین ورود</th><th>وضعیت</th></tr></thead>
      <tbody>
        <?php foreach ($members as $m): ?>
        <tr>
          <td><span class="code-chip"><?= e($m['code']) ?></span></td>
          <td class="fw-bold"><?= e($m['name']) ?></td>
          <td><?= e((string) ($m['position'] ?: 'عضو')) ?></td>
          <td class="num"><?= fa((string) $m['shares']) ?></td>
          <td class="num ltr text-sm"><?= fa((string) $m['phone']) ?></td>
          <td class="text-sm"><?= $m['last_login_at'] ? jrelative($m['last_login_at']) : '—' ?></td>
          <td><?= $m['user_status'] === 'active' ? '<span class="badge badge--success badge-dot">فعال</span>' : '<span class="badge badge--muted badge-dot">معلق</span>' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<style>@media (max-width:900px){#mem-grid{grid-template-columns:1fr !important}}</style>
