<?php
/** ویوی فهرست سفارش‌های تولید — Production::admin.index */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>تولید</span></div>
    <h1>سفارش‌های تولید (WO)</h1>
    <div class="page-header__sub">چرخه: تخصیص ← دریافت مواد ← بافت ← تحویل ← بازرسی کیفیت ← تسویه</div>
  </div>
  <a class="btn btn--primary" href="<?= url('admin/production/create') ?>"><?= icon('plus', '', 16) ?> سفارش تولید جدید</a>
</div>

<div class="kpi-grid mb-4">
  <?php
  $kpiDefs = [
      ['assigned', 'تخصیص‌یافته', 'action'], ['materials_received', 'مواد تحویل‌شده', 'secondary'],
      ['weaving', 'در حال بافت', 'warning'], ['delivered', 'منتظر بازرسی', 'primary'],
      ['qc_passed', 'تأیید کیفیت', 'success'], ['completed', 'تکمیل‌شده', 'gold'],
  ];
  foreach ($kpiDefs as [$key, $label, $color]): ?>
    <a class="kpi kpi--<?= $color ?>" href="<?= url('admin/production?status=' . $key) ?>" style="text-decoration:none;color:inherit">
      <div class="kpi__label"><?= icon('shuttle', '', 15) ?> <?= $label ?></div>
      <div class="kpi__value"><?= fa((string) ($statusCounts[$key] ?? 0)) ?></div>
    </a>
  <?php endforeach; ?>
</div>

<form class="filter-bar" method="get" action="<?= url('admin/production') ?>">
  <input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="جست‌وجو: کد WO / بافنده / SKU">
  <select class="form-control" name="status" data-auto-submit>
    <option value="">همهٔ وضعیت‌ها</option>
    <?php foreach ($statuses as $key => $s): ?>
      <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($s['label']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn--sm btn--outline" type="submit"><?= icon('search', '', 15) ?></button>
</form>

<?php if ($orders === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'loom', 'title' => 'سفارش تولیدی یافت نشد', 'action_url' => url('admin/production/create'), 'action_label' => 'ایجاد سفارش تولید']) ?></div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>کد WO</th><th>محصول</th><th>بافنده</th><th>تعداد</th><th>اولویت</th><th>موعد</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($orders as $wo): ?>
      <tr>
        <td><span class="code-chip"><?= e($wo['code']) ?></span></td>
        <td><?= e($wo['product_name']) ?><div class="text-sm text-muted mono-code"><?= e($wo['sku']) ?></div></td>
        <td><?= $wo['weaver_name'] ? e($wo['weaver_name']) . ' <span class="code-chip" style="font-size:.64rem">' . e($wo['weaver_code']) . '</span>' : '<span class="badge badge--warning">تخصیص‌نیافته</span>' ?></td>
        <td class="num"><?= fa((string) $wo['quantity']) ?></td>
        <td><?= component('status-pill', ['group' => 'priority', 'status' => $wo['priority']]) ?></td>
        <td class="text-sm"><?= $wo['due_date'] ? jdt($wo['due_date']) : '—' ?></td>
        <td><?= component('status-pill', ['group' => 'work_order', 'status' => $wo['status']]) ?></td>
        <td><a class="btn btn--sm btn--outline" href="<?= url('admin/production/' . $wo['id']) ?>"><?= icon('eye', '', 13) ?> جزئیات</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $paginator->links(url('admin/production')) ?>
<?php endif; ?>
