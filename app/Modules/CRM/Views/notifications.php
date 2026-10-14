<?php
/** ویوی فهرست اعلان‌ها — CRM::notifications */
$this->extend('layouts.panel', ['panel' => 'account', 'panelTitle' => 'پنل کاربر']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('my') ?>">میز کار</a> <span>/</span> <span>اعلان‌ها</span></div>
    <h1>اعلان‌های من</h1>
  </div>
  <form method="post" action="<?= url('notifications/read-all') ?>">
    <?= csrf_field() ?>
    <button class="btn btn--sm btn--outline" type="submit"><?= icon('check', '', 14) ?> خواندن همه</button>
  </form>
</div>

<?php if (count($notifications) === 0): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'bell', 'title' => 'اعلانی وجود ندارد']) ?></div>
<?php else: ?>
<div class="stack">
  <?php foreach ($notifications as $n): ?>
    <div class="card flex gap-3" style="align-items:flex-start;<?= $n->is_read ? 'opacity:.65' : 'border-inline-start:3px solid var(--color-primary)' ?>">
      <span class="notif-item__icon notif-item--<?= e($n->kind) ?>" style="width:40px;height:40px">
        <?= icon(match ($n->kind) { 'success' => 'check', 'warning' => 'alert', 'danger' => 'x', default => 'info' }, '', 18) ?>
      </span>
      <div style="flex:1">
        <b><?= e($n->title) ?></b>
        <?php if ($n->body): ?><p class="text-sm text-secondary m-0"><?= e($n->body) ?></p><?php endif; ?>
        <span class="text-sm text-muted"><?= jrelative($n->created_at) ?></span>
      </div>
      <?php if ($n->link): ?><a class="btn btn--sm btn--ghost" href="<?= e($n->link) ?>"><?= icon('arrow-left', '', 14) ?></a><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
