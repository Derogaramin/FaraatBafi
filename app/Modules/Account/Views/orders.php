<?php
/** ویوی سفارش‌های مشتری — Account::orders */
$this->extend('layouts.panel', ['panel' => 'account', 'panelTitle' => 'پنل مشتری']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('my') ?>">میز کار</a> <span>/</span> <span>سفارش‌ها</span></div>
    <h1>سفارش‌های من</h1>
  </div>
</div>

<?php if ($orders === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'cart', 'title' => 'سفارشی ندارید', 'action_url' => url('marketplace'), 'action_label' => 'خرید از بازارگاه']) ?></div>
<?php else: ?>
<div class="stack">
  <?php foreach ($orders as $o): ?>
    <a class="card card--hover flex-between" href="<?= url('my/orders/' . $o['id']) ?>" style="text-decoration:none;color:inherit;flex-wrap:wrap;gap:8px">
      <div>
        <span class="code-chip"><?= e($o['code']) ?></span>
        <div class="text-sm text-muted mt-1"><?= jdt($o['placed_at'], 'Y/m/d', true) ?></div>
      </div>
      <?= component('status-pill', ['group' => 'order', 'status' => $o['status'], 'size' => 'lg']) ?>
      <b class="num"><?= toman((int) $o['total']) ?></b>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
