<?php
/** ویوی میز کار مشتری — Account::dashboard */
$this->extend('layouts.panel', ['panel' => 'account', 'panelTitle' => 'پنل مشتری']);
?>
<div class="page-header">
  <div>
    <h1>سلام، <?= e(\Core\Auth::user()['name']) ?> 👋</h1>
    <div class="page-header__sub">کد کاربری شما: <span class="code-chip"><?= e(\Core\Auth::code()) ?></span> — با آن سفارش‌ها و تیکت‌هایتان قابل پیگیری است.</div>
  </div>
  <a class="btn btn--primary btn--sm" href="<?= url('marketplace') ?>"><?= icon('cart', '', 15) ?> رفتن به بازارگاه</a>
</div>

<div class="kpi-grid mb-4">
  <?= component('kpi-card', ['label' => 'کل سفارش‌ها', 'value' => fa((string) count($orders)) . '+', 'icon' => 'cart', 'color' => 'action', 'url' => url('my/orders')]) ?>
  <?= component('kpi-card', ['label' => 'تیکت‌های باز', 'value' => fa((string) $openTickets), 'icon' => 'ticket', 'color' => 'warning', 'url' => url('my/tickets')]) ?>
  <?= component('kpi-card', ['label' => 'استعلام اصالت', 'value' => 'QR محصولات', 'icon' => 'qr', 'color' => 'primary', 'url' => url('authenticity')]) ?>
</div>

<div class="card">
  <div class="card__header">
    <h3 class="card__title"><?= icon('clock', '', 18) ?> آخرین سفارش‌ها</h3>
    <a class="text-sm" href="<?= url('my/orders') ?>">همه</a>
  </div>
  <?php if ($orders === []): ?>
    <?= component('empty-state', ['icon' => 'cart', 'title' => 'هنوز سفارشی ثبت نکرده‌اید', 'action_url' => url('marketplace'), 'action_label' => 'مشاهدهٔ بازارگاه']) ?>
  <?php else: ?>
  <table class="table" style="min-width:0">
    <thead><tr><th>کد سفارش</th><th>وضعیت</th><th>مبلغ</th><th>تاریخ</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td><span class="code-chip"><?= e($o['code']) ?></span></td>
        <td><?= component('status-pill', ['group' => 'order', 'status' => $o['status']]) ?></td>
        <td class="num"><?= toman((int) $o['total'], false) ?></td>
        <td class="text-sm"><?= jdt($o['placed_at'], 'Y/m/d', true) ?></td>
        <td><a class="btn btn--sm btn--outline" href="<?= url('my/orders/' . $o['id']) ?>"><?= icon('eye', '', 13) ?></a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
