<?php
/** ویوی پروفایل مشتری — Members::admin.customer-profile */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/customers') ?>">مشتریان</a> <span>/</span> <span><?= e($customer['code']) ?></span></div>
    <h1><?= e($customer['name']) ?> <span class="code-chip"><?= e($customer['code']) ?></span></h1>
    <div class="page-header__sub"><?= e((string) ($customer['city'] ?: '')) ?> • <span class="num ltr"><?= fa((string) $customer['phone']) ?></span></div>
  </div>
</div>

<div class="card mb-4">
  <h3 class="card__title mb-3"><?= icon('cart', '', 18) ?> تاریخچهٔ سفارش‌ها</h3>
  <?php if ($orders === []): ?>
    <p class="text-sm text-muted m-0">سفارشی ثبت نشده است.</p>
  <?php else: ?>
  <table class="table" style="min-width:0">
    <thead><tr><th>کد</th><th>وضعیت</th><th>مبلغ</th><th>کانال</th><th>تاریخ</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td><a class="mono-code" href="<?= url('admin/orders/' . $o['id']) ?>"><?= e($o['code']) ?></a></td>
        <td><?= component('status-pill', ['group' => 'order', 'status' => $o['status']]) ?></td>
        <td class="num"><?= toman((int) $o['total'], false) ?></td>
        <td class="text-sm"><?= e(\Core\Statuses::CHANNEL[$o['channel']] ?? $o['channel']) ?></td>
        <td class="text-sm"><?= jdt($o['placed_at'], 'Y/m/d', true) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<div class="grid grid-2">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('user', '', 18) ?> اطلاعات تماس</h3>
    <dl class="spec-list">
      <dt>رایانامه</dt><dd class="ltr"><?= e((string) ($customer['email'] ?: '—')) ?></dd>
      <dt>استان</dt><dd><?= e((string) ($customer['province'] ?: '—')) ?></dd>
      <dt>نشانی</dt><dd style="font-weight:400"><?= e((string) ($customer['address'] ?: '—')) ?></dd>
      <dt>کد پستی</dt><dd class="num ltr"><?= e((string) ($customer['postal_code'] ?: '—')) ?></dd>
    </dl>
  </div>
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('ticket', '', 18) ?> تیکت‌ها</h3>
    <?php if ($tickets === []): ?><p class="text-sm text-muted m-0">تیکتی ثبت نکرده است.</p>
    <?php else: foreach ($tickets as $t): ?>
      <div class="flex-between text-sm" style="padding:6px 0;border-bottom:1px dashed var(--color-border)">
        <a href="<?= url('admin/tickets/' . $t['id']) ?>"><?= e($t['subject']) ?></a>
        <?= component('status-pill', ['group' => 'ticket', 'status' => $t['status']]) ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
