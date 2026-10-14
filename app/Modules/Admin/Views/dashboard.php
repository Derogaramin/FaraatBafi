<?php
/**
 * ویوی داشبورد مدیر — Admin::dashboard
 * ۴ کارت KPI طبق مستندات + نمودار فروش + وضعیت تولید + هشدارها
 */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$chartData = array_map(fn ($c) => [
    'label' => $c['label'],
    'value' => (int) $c['revenue'],
    'labelFull' => $c['label'] . ': ' . toman((int) $c['revenue']) . ' — ' . fa((string) $c['count']) . ' سفارش',
], $chart);
$woCounts = array_column($woStatus, 'c', 'status');
?>
<div class="page-header">
  <div>
    <h1>سلام، <?= e(\Core\Auth::user()['name']) ?> 👋</h1>
    <div class="page-header__sub"><?= e($today) ?> — نمای کلی تعاونی نقشینه بافت</div>
  </div>
  <div class="flex gap-2">
    <a class="btn btn--sm btn--outline" href="<?= url('admin/production/create') ?>"><?= icon('plus', '', 14) ?> سفارش تولید</a>
    <a class="btn btn--sm btn--primary" href="<?= url('admin/catalog/variants/create') ?>"><?= icon('plus', '', 14) ?> محصول جدید</a>
  </div>
</div>

<!-- ۴ کارت KPI -->
<div class="kpi-grid mb-4">
  <?= component('kpi-card', [
      'label' => 'سفارش‌های امروز', 'icon' => 'cart', 'color' => 'action',
      'value' => fa((string) ($kpi['orders_today'] ?? 0)),
      'url' => url('admin/orders'),
      'hint' => 'همهٔ کانال‌های فروش',
  ]) ?>
  <?= component('kpi-card', [
      'label' => 'درآمد ۳۰ روز', 'icon' => 'coin', 'color' => 'success',
      'value' => toman((int) ($kpi['revenue_month'] ?? 0)),
      'url' => url('admin/finance/reports'),
      'hint' => 'سفارش‌های قطعی‌شده',
  ]) ?>
  <?= component('kpi-card', [
      'label' => 'بافندگان فعال', 'icon' => 'shuttle', 'color' => 'primary',
      'value' => fa((string) ($kpi['active_weavers'] ?? 0)),
      'url' => url('admin/weavers?status=active'),
      'hint' => 'در حال همکاری با تعاونی',
  ]) ?>
  <?= component('kpi-card', [
      'label' => 'بازرسی‌های معوق', 'icon' => 'quality', 'color' => 'warning',
      'value' => fa((string) ($kpi['pending_qc'] ?? 0)),
      'url' => url('admin/production?status=delivered'),
      'hint' => 'بافت تحویل‌شده در انتظار QC',
  ]) ?>
</div>

<div class="grid" style="grid-template-columns:1.5fr 1fr;gap:var(--space-5);align-items:start;margin-bottom:var(--space-5)" id="dash-grid">
  <!-- نمودار فروش -->
  <div class="card">
    <div class="card__header">
      <h3 class="card__title"><?= icon('chart', '', 18) ?> روند فروش (ماه شمسی)</h3>
      <a class="text-sm" href="<?= url('admin/finance/reports') ?>">گزارش کامل</a>
    </div>
    <?php if ($chartData === []): ?>
      <p class="text-sm text-muted">هنوز فروشی ثبت نشده است.</p>
    <?php else: ?>
      <div class="bar-chart" data-bar-chart='<?= e(json_encode($chartData, JSON_UNESCAPED_UNICODE)) ?>'></div>
    <?php endif; ?>
  </div>

  <!-- وضعیت تولید -->
  <div class="card">
    <div class="card__header">
      <h3 class="card__title"><?= icon('loom', '', 18) ?> وضعیت تولید</h3>
      <a class="text-sm" href="<?= url('admin/production') ?>">همه</a>
    </div>
    <?php
    // دونات وضعیت‌ها (conic-gradient خالص CSS با مرزهای تجمعی)
    $donutKeys = ['assigned', 'materials_received', 'weaving', 'delivered', 'qc_passed'];
    $donutTotal = 0;
    foreach ($woStatus as $s) { $donutTotal += (int) $s['c']; }
    $cum = 0.0;
    $edges = [];
    foreach ($donutKeys as $k) {
        $cum += $donutTotal > 0 ? ((int) ($woCounts[$k] ?? 0)) / $donutTotal : 0;
        $edges[$k] = round($cum, 4);
    }
    ?>
    <div class="donut-wrap">
      <div class="donut" style="--e1:<?= $edges['assigned'] ?>;--e2:<?= $edges['materials_received'] ?>;--e3:<?= $edges['weaving'] ?>;--e4:<?= $edges['delivered'] ?>;--e5:<?= $edges['qc_passed'] ?>">
        <div class="donut__center">
          <b><?= fa((string) $donutTotal) ?></b>
          <span>سفارش فعال</span>
        </div>
      </div>
      <div class="donut-legend">
        <?php
        $legend = [
            'assigned'           => 'var(--color-info)',
            'materials_received' => 'var(--color-secondary)',
            'weaving'            => 'var(--color-warning)',
            'delivered'          => 'var(--color-primary)',
            'qc_passed'          => 'var(--color-success)',
        ];
        foreach ($legend as $key => $color): ?>
          <a href="<?= url('admin/production?status=' . $key) ?>">
            <span class="dot" style="background:<?= $color ?>"></span>
            <?= e(\Core\Statuses::label('work_order', $key)) ?>
            <b><?= fa((string) ($woCounts[$key] ?? 0)) ?></b>
          </a>
        <?php endforeach; ?>
        <a href="<?= url('admin/production?status=completed') ?>" style="opacity:.75">
          <span class="dot" style="background:var(--color-border-strong)"></span>
          تکمیل/سایر
          <b><?= fa((string) (($woCounts['completed'] ?? 0))) ?></b>
        </a>
      </div>
    </div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1.5fr 1fr;gap:var(--space-5);align-items:start" id="dash-grid2">
  <!-- آخرین سفارش‌ها -->
  <div class="card">
    <div class="card__header">
      <h3 class="card__title"><?= icon('cart', '', 18) ?> آخرین سفارش‌ها</h3>
      <a class="text-sm" href="<?= url('admin/orders') ?>">همهٔ سفارش‌ها</a>
    </div>
    <table class="table" style="min-width:0">
      <thead><tr><th>کد</th><th>مشتری</th><th>وضعیت</th><th>مبلغ</th><th>تاریخ</th></tr></thead>
      <tbody>
        <?php foreach ($recentOrders as $o): ?>
        <tr>
          <td><a class="mono-code text-sm" href="<?= url('admin/orders/' . $o['id']) ?>"><?= e($o['code']) ?></a></td>
          <td class="text-sm"><?= e((string) ($o['customer_name'] ?: '—')) ?></td>
          <td><?= component('status-pill', ['group' => 'order', 'status' => $o['status']]) ?></td>
          <td class="num text-sm"><?= toman((int) $o['total'], false) ?></td>
          <td class="text-sm text-muted"><?= jrelative($o['placed_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="stack">
    <!-- بازرسی معوق -->
    <?php if ($pendingQc !== []): ?>
    <div class="card" style="border-color:var(--color-warning)">
      <h3 class="card__title mb-3"><?= icon('quality', '', 18) ?> در انتظار بازرسی</h3>
      <?php foreach ($pendingQc as $wo): ?>
        <a class="flex-between text-sm" href="<?= url('admin/production/' . $wo['id']) ?>" style="padding:8px 0;border-bottom:1px dashed var(--color-border);color:inherit">
          <span><span class="mono-code"><?= e($wo['code']) ?></span><br><span class="text-muted"><?= e($wo['sku']) ?> — <?= e($wo['weaver_name'] ?? 'بدون بافنده') ?></span></span>
          <span class="badge badge--warning"><?= $wo['delivered_at'] ? jrelative($wo['delivered_at']) : '—' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- هشدار موجودی -->
    <?php if ($lowStock !== []): ?>
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('alert', '', 18) ?> مواد زیر حد سفارش</h3>
      <?php foreach ($lowStock as $m): ?>
        <div class="flex-between text-sm" style="padding:6px 0;border-bottom:1px dashed var(--color-border)">
          <span><?= e($m['name_fa']) ?> <span class="code-chip" style="font-size:.6rem"><?= e($m['code']) ?></span></span>
          <span class="badge badge--danger"><?= fa(rtrim(rtrim(number_format((float) $m['stock'], 1), '0'), '.')) ?> <?= e($m['unit']) ?></span>
        </div>
      <?php endforeach; ?>
      <a class="btn btn--sm btn--outline mt-3" href="<?= url('admin/inventory/materials') ?>">مدیریت انبار</a>
    </div>
    <?php endif; ?>

    <?php if ($lowVariants !== []): ?>
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('box', '', 18) ?> محصولات کم‌موجود</h3>
      <?php foreach ($lowVariants as $v): ?>
        <div class="flex-between text-sm" style="padding:6px 0;border-bottom:1px dashed var(--color-border)">
          <span><?= e($v['name_fa']) ?> <span class="code-chip" style="font-size:.6rem"><?= e($v['sku']) ?></span></span>
          <span class="badge badge--warning"><?= fa((string) $v['stock']) ?> عدد</span>
        </div>
      <?php endforeach; ?>
      <a class="btn btn--sm btn--outline mt-3" href="<?= url('admin/production/create') ?>">سفارش تولید جدید</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<style>
@media (max-width:992px){#dash-grid,#dash-grid2{grid-template-columns:1fr !important}}
</style>
