<?php
/** ویوی دستمزدهای بافنده — Weaver::payments */
$this->extend('layouts.weaver');
?>
<h1 class="mb-4" style="font-size:1.15rem">دستمزدهای من</h1>

<div class="grid grid-2 mb-4">
  <div class="card text-center" style="background:var(--color-success-light);border-color:transparent">
    <div class="text-sm text-muted">پرداخت‌شده (کل)</div>
    <b class="num text-success text-lg"><?= toman((int) ($summary['paid_total'] ?? 0)) ?></b>
  </div>
  <div class="card text-center" style="background:var(--color-warning-light);border-color:transparent">
    <div class="text-sm text-muted">در انتظار پرداخت</div>
    <b class="num text-warning text-lg"><?= toman((int) ($summary['pending_total'] ?? 0)) ?></b>
  </div>
</div>

<?php if ($payments === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'coin', 'title' => 'فیشی صادر نشده است', 'message' => 'پس از تأیید کیفیت هر بافت، فیش دستمزد شما به‌صورت خودکار صادر می‌شود.']) ?></div>
<?php else: ?>
<div class="stack">
  <?php foreach ($payments as $p): ?>
    <div class="card">
      <div class="flex-between" style="flex-wrap:wrap;gap:8px">
        <div>
          <span class="code-chip"><?= e($p['code']) ?></span>
          <?php if ($p['wo_code']): ?>
            <a class="text-sm mono-code ms-2" href="<?= url('weaver/orders/' . $p['work_order_id']) ?>"><?= e($p['wo_code']) ?></a>
          <?php endif; ?>
        </div>
        <?= component('status-pill', ['group' => 'payment', 'status' => $p['status']]) ?>
      </div>
      <div class="flex-between mt-3">
        <span class="text-sm text-muted">
          دوره: <?= $p['period'] ? fa(str_replace('-', '/', (string) $p['period'])) : '—' ?>
          <?php if ($p['paid_at']): ?> • پرداخت: <?= jdt($p['paid_at']) ?><?php endif; ?>
        </span>
        <b class="num text-lg text-primary-brand"><?= toman((int) $p['amount'], false) ?></b>
      </div>
      <?php if (!empty($p['note'])): ?><p class="text-sm text-secondary mt-2 mb-0"><?= e($p['note']) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
