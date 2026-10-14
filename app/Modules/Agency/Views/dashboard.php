<?php
/** ویوی داشبورد نمایندگی — Agency::dashboard */
$this->extend('layouts.panel', ['panel' => 'agency', 'panelTitle' => 'نمایندگی ' . ($agency['code'] ?? '')]);
?>
<div class="page-header">
  <div>
    <h1><?= e($agency['name']) ?> — خوش آمدید 👋</h1>
    <div class="page-header__sub"><?= e($today) ?> • کد نمایندگی <span class="code-chip"><?= e($agency['code']) ?></span> • <?= e((string) ($agency['city'] ?? '')) ?></div>
  </div>
  <a class="btn btn--primary" href="<?= url('agency/pos') ?>"><?= icon('barcode', '', 16) ?> فروش حضوری جدید</a>
</div>

<div class="kpi-grid mb-4">
  <?= component('kpi-card', ['label' => 'فروش ۳۰ روز', 'value' => fa((string) ($stats['orders_30d'] ?? 0)) . ' سفارش', 'icon' => 'cart', 'color' => 'action', 'hint' => toman((int) ($stats['revenue_30d'] ?? 0)), 'url' => url('agency/orders')]) ?>
  <?= component('kpi-card', ['label' => 'کالای امانی', 'value' => fa((string) ($stats['consignment_items'] ?? 0)) . ' قلم', 'icon' => 'warehouse', 'color' => 'secondary', 'hint' => 'ارزش: ' . toman_short($consignValue) . ' تومان', 'url' => url('agency/consignment')]) ?>
  <?= component('kpi-card', ['label' => 'پورسانت معوق', 'value' => toman((int) ($stats['pending_commission'] ?? 0)), 'icon' => 'coin', 'color' => 'warning', 'url' => url('agency/commissions')]) ?>
  <?= component('kpi-card', ['label' => 'پورسانت دریافتی', 'value' => toman((int) ($stats['paid_commission'] ?? 0)), 'icon' => 'check', 'color' => 'success']) ?>
</div>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <div class="card__header">
      <h3 class="card__title"><?= icon('cart', '', 18) ?> آخرین فروش‌ها</h3>
      <a class="text-sm" href="<?= url('agency/orders') ?>">همه</a>
    </div>
    <?php if ($recentOrders === []): ?>
      <p class="text-sm text-muted m-0">فروشی ثبت نشده است.</p>
    <?php else: ?>
      <?php foreach ($recentOrders as $o): ?>
        <div class="flex-between text-sm" style="padding:8px 0;border-bottom:1px dashed var(--color-border)">
          <span class="mono-code"><?= e($o['code']) ?></span>
          <?= component('status-pill', ['group' => 'order', 'status' => $o['status']]) ?>
          <b class="num"><?= toman((int) $o['total'], false) ?></b>
          <span class="text-muted"><?= jrelative($o['placed_at']) ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card" style="background:var(--color-action-light);border-color:transparent">
    <h3 class="card__title mb-3"><?= icon('barcode', '', 18) ?> راهنمای فروش حضوری</h3>
    <ol class="text-sm text-secondary" style="padding-inline-start:var(--space-4);line-height:2.4">
      <li>در صفحهٔ <b>فروش حضوری</b>، بارکد/SKU محصول را با اسکنر یا دستی وارد کنید.</li>
      <li>اقلام به سبد فروش اضافه می‌شوند؛ نام و موبایل خریدار را وارد کنید.</li>
      <li>با ثبت فروش: سفارش (تحویل‌شده)، فاکتور، رسید و <b>پورسانت شما (COM)</b> خودکار صادر می‌شود.</li>
      <li>موجودی از کالای امانی شما کسر می‌گردد.</li>
    </ol>
    <a class="btn btn--primary mt-2" href="<?= url('agency/pos') ?>"><?= icon('barcode', '', 16) ?> شروع فروش</a>
  </div>
</div>
