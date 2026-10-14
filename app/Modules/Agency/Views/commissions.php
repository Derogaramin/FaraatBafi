<?php
/** ویوی پورسانت‌های نمایندگی — Agency::commissions */
$this->extend('layouts.panel', ['panel' => 'agency', 'panelTitle' => 'نمایندگی ' . ($agency['code'] ?? '')]);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('agency') ?>">داشبورد</a> <span>/</span> <span>پورسانت‌ها</span></div>
    <h1>پورسانت‌های من</h1>
  </div>
</div>

<div class="grid grid-2 mb-4">
  <div class="card text-center" style="background:var(--color-warning-light);border-color:transparent">
    <div class="text-sm text-muted">در انتظار دریافت</div>
    <b class="num text-warning text-lg"><?= toman((int) ($summary['pending'] ?? 0)) ?></b>
  </div>
  <div class="card text-center" style="background:var(--color-success-light);border-color:transparent">
    <div class="text-sm text-muted">دریافت‌شده</div>
    <b class="num text-success text-lg"><?= toman((int) ($summary['paid'] ?? 0)) ?></b>
  </div>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>کد پورسانت</th><th>سفارش</th><th>دوره</th><th>مبنای محاسبه</th><th>نرخ</th><th>مبلغ</th><th>وضعیت</th></tr></thead>
    <tbody>
      <?php foreach ($commissions as $c): ?>
      <tr>
        <td><span class="code-chip"><?= e($c['code']) ?></span></td>
        <td class="mono-code text-sm"><?= e((string) ($c['order_code'] ?? '—')) ?></td>
        <td class="text-sm"><?= $c['period'] ? fa(str_replace('-', '/', (string) $c['period'])) : '—' ?></td>
        <td class="num text-sm"><?= toman((int) $c['base_amount'], false) ?></td>
        <td class="num"><?= fa((string) $c['rate']) ?>٪</td>
        <td class="num fw-bold"><?= toman((int) $c['amount'], false) ?></td>
        <td><?= component('status-pill', ['group' => 'commission', 'status' => $c['status']]) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
