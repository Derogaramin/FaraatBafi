<?php
/** ویوی مواد اولیهٔ دریافتی — Weaver::materials */
$this->extend('layouts.weaver');
?>
<h1 class="mb-4" style="font-size:1.15rem">مواد اولیهٔ دریافتی</h1>

<?php if ($totals !== []): ?>
<div class="card mb-4">
  <h3 class="card__title mb-3"><?= icon('thread', '', 18) ?> جمع کل بر اساس ماده</h3>
  <?php foreach ($totals as $t): ?>
    <div class="flex-between text-sm" style="padding:8px 0;border-bottom:1px dashed var(--color-border)">
      <span><?= e($t['name_fa']) ?></span>
      <b class="num"><?= fa(rtrim(rtrim(number_format((float) $t['total'], 2), '0'), '.')) ?> <?= e($t['unit']) ?></b>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($materials === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'thread', 'title' => 'موادی دریافت نکرده‌اید', 'message' => 'پس از تخصیص سفارش تولید، انبار تعاونی مواد لازم را ارسال می‌کند.']) ?></div>
<?php else: ?>
<div class="stack">
  <?php foreach ($materials as $m): ?>
    <div class="card">
      <div class="flex-between">
        <div>
          <b><?= e($m['material_name']) ?></b>
          <div class="text-sm text-muted">برای سفارش <a class="mono-code" href="<?= url('weaver/orders/' . $m['work_order_id']) ?>"><?= e($m['wo_code']) ?></a></div>
        </div>
        <div class="text-end">
          <b class="num text-primary-brand"><?= fa(rtrim(rtrim(number_format((float) $m['quantity'], 2), '0'), '.')) ?> <?= e($m['unit']) ?></b>
          <div class="text-sm text-muted"><?= jdt($m['distributed_at'], 'Y/m/d', true) ?></div>
        </div>
      </div>
      <?php if (!empty($m['note'])): ?><p class="text-sm text-secondary mt-2 mb-0"><?= e($m['note']) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
