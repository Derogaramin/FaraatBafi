<?php
/** ویوی گزارش‌های مالی — Finance::admin.reports */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
// آماده‌سازی دادهٔ نمودار فروش
$chartData = [];
foreach ($sales as $key => $row) {
    $chartData[] = ['label' => $row['label'], 'value' => (int) $row['revenue'], 'labelFull' => $row['label'] . ': ' . toman($row['revenue']) . ' — ' . fa((string) $row['cnt']) . ' سفارش'];
}
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>گزارش‌های مالی</span></div>
    <h1>گزارش‌های مالی</h1>
    <div class="page-header__sub">فروش، دستمزد و پورسانت به تفکیک ماه شمسی</div>
  </div>
</div>

<div class="kpi-grid mb-4">
  <?= component('kpi-card', ['label' => 'درآمد کل فروش', 'value' => toman((int) ($totals['revenue_total'] ?? 0)), 'icon' => 'chart', 'color' => 'success']) ?>
  <?= component('kpi-card', ['label' => 'دستمزد پرداخت‌شده', 'value' => toman((int) ($totals['wages_paid'] ?? 0)), 'icon' => 'coin', 'color' => 'primary']) ?>
  <?= component('kpi-card', ['label' => 'پورسانت پرداخت‌شده', 'value' => toman((int) ($totals['commissions_paid'] ?? 0)), 'icon' => 'money', 'color' => 'warning']) ?>
  <?= component('kpi-card', ['label' => 'ماندهٔ بافندگان', 'value' => toman((int) ($totals['weavers_balance'] ?? 0)), 'icon' => 'handshake', 'color' => 'action']) ?>
</div>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('chart', '', 18) ?> نمودار فروش ماهانه</h3>
    <?php if ($chartData === []): ?>
      <p class="text-sm text-muted">داده‌ای برای نمایش نیست.</p>
    <?php else: ?>
      <div class="bar-chart" data-bar-chart='<?= e(json_encode($chartData, JSON_UNESCAPED_UNICODE)) ?>'></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card__title mb-3"><?= icon('coin', '', 18) ?> جدول ماهانه</h3>
    <table class="table" style="min-width:0">
      <thead><tr><th>ماه</th><th>سفارش</th><th>درآمد فروش</th><th>دستمزد</th></tr></thead>
      <tbody>
        <?php foreach (array_reverse($sales, true) as $key => $row): ?>
        <tr>
          <td><?= e($row['label']) ?></td>
          <td class="num"><?= fa((string) $row['cnt']) ?></td>
          <td class="num"><?= toman((int) $row['revenue'], false) ?></td>
          <td class="num"><?= isset($wages[$key]) ? toman((int) $wages[$key], false) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
