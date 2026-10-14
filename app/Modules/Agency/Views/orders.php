<?php
/** ویوی سفارش‌های نمایندگی — Agency::orders */
$this->extend('layouts.panel', ['panel' => 'agency', 'panelTitle' => 'نمایندگی ' . ($agency['code'] ?? '')]);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('agency') ?>">داشبورد</a> <span>/</span> <span>سفارش‌ها</span></div>
    <h1>سفارش‌های نمایندگی</h1>
    <div class="page-header__sub">مجموع <?= fa((string) count($orders)) ?> سفارش</div>
  </div>
</div>

<?php if ($orders === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'cart', 'title' => 'سفارشی ثبت نشده است']) ?></div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>کد سفارش</th><th>خریدار</th><th>وضعیت</th><th>مبلغ</th><th>تاریخ</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td><span class="code-chip"><?= e($o['code']) ?></span></td>
        <td><?= e((string) ($o['customer_name'] ?? $o['receiver'])) ?><div class="text-sm text-muted num ltr"><?= fa($o['phone']) ?></div></td>
        <td><?= component('status-pill', ['group' => 'order', 'status' => $o['status']]) ?></td>
        <td class="num"><?= toman((int) $o['total'], false) ?></td>
        <td class="text-sm"><?= jdt($o['placed_at'], 'Y/m/d', true) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
