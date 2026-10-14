<?php
/** ویوی فهرست سفارش‌های بافنده — Weaver::orders */
$this->extend('layouts.weaver');
$steps = \Core\Statuses::WORK_ORDER_STEPS;
?>
<h1 class="mb-2" style="font-size:1.15rem">سفارش‌های بافت من</h1>
<div class="chip-row mb-4">
  <a class="chip <?= $filter === '' || $filter === 'all' ? 'active' : '' ?>" href="<?= url('weaver/orders') ?>">همه</a>
  <?php foreach (['assigned', 'materials_received', 'weaving', 'delivered', 'qc_passed', 'completed'] as $s): ?>
    <a class="chip <?= $filter === $s ? 'active' : '' ?>" href="<?= url('weaver/orders?status=' . $s) ?>"><?= e(\Core\Statuses::label('work_order', $s)) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($orders === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'loom', 'title' => 'سفارشی در این وضعیت نیست']) ?></div>
<?php else: ?>
<div class="wo-list">
  <?php foreach ($orders as $wo): ?>
    <?php $idx = array_search($wo['status'], $steps, true); ?>
    <a class="card card--hover wo-card" style="text-decoration:none;color:inherit" href="<?= url('weaver/orders/' . $wo['id']) ?>">
      <div class="wo-card__head">
        <div>
          <b><?= e($wo['product_name']) ?></b>
          <div class="text-sm text-muted"><span class="mono-code"><?= e($wo['code']) ?></span> • <?= fa((string) $wo['quantity']) ?> عدد</div>
        </div>
        <?= component('status-pill', ['group' => 'work_order', 'status' => $wo['status']]) ?>
      </div>
      <?php if ($wo['due_date']): ?>
        <div class="text-sm mt-2">
          <span class="countdown" data-countdown="<?= (string) (int) ceil((strtotime($wo['due_date'] . ' 23:59:59') - time()) / 86400) ?>">
            <?= icon('clock', '', 13) ?> موعد: <?= jdt($wo['due_date']) ?> — <?= \Core\Jalali::countdown($wo['due_date']) ?>
          </span>
        </div>
      <?php endif; ?>
      <div class="mini-steps">
        <?php foreach ($steps as $i => $s): ?>
          <span class="<?= $wo['status'] === 'qc_rejected' && $i === 4 ? 'rejected' : ($idx !== false && $i < $idx ? 'done' : ($i === $idx ? 'current' : '')) ?>"></span>
        <?php endforeach; ?>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
