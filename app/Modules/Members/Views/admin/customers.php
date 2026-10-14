<?php
/** ویوی فهرست مشتریان — Members::admin.customers */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>مشتریان</span></div>
    <h1>مشتریان</h1>
    <div class="page-header__sub">مجموع <?= fa((string) $paginator->total) ?> مشتری — کدگذاری <span class="code-chip">C000215</span></div>
  </div>
</div>

<form class="filter-bar" method="get" action="<?= url('admin/customers') ?>">
  <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="جست‌وجو: نام / کد / موبایل">
  <button class="btn btn--sm btn--outline" type="submit"><?= icon('search', '', 15) ?></button>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>کد</th><th>نام</th><th>موبایل</th><th>شهر</th><th>سفارش‌ها</th><th>مجموع خرید</th><th>ثبت‌نام</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
      <tr>
        <td><span class="code-chip"><?= e($c['code']) ?></span></td>
        <td class="fw-bold"><?= e($c['name']) ?></td>
        <td class="num ltr"><?= fa((string) $c['phone']) ?></td>
        <td><?= e((string) ($c['city'] ?: '—')) ?></td>
        <td class="num"><?= fa((string) $c['orders_count']) ?></td>
        <td class="num"><?= toman((int) $c['total_purchase'], false) ?></td>
        <td class="text-sm"><?= jdt($c['created_at']) ?></td>
        <td><a class="btn btn--sm btn--outline" href="<?= url('admin/customers/' . $c['id']) ?>"><?= icon('eye', '', 13) ?></a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $paginator->links(url('admin/customers')) ?>
