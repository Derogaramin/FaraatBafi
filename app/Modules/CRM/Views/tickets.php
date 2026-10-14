<?php
/** ویوی تیکت‌های کاربر — CRM::tickets */
$this->extend('layouts.panel', ['panel' => 'account', 'panelTitle' => 'پنل کاربر']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('my') ?>">میز کار</a> <span>/</span> <span>تیکت‌ها</span></div>
    <h1>تیکت‌های پشتیبانی من</h1>
  </div>
  <a class="btn btn--primary" href="<?= url('my/tickets/create') ?>"><?= icon('plus', '', 16) ?> تیکت جدید</a>
</div>

<?php if (count($tickets) === 0): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'ticket', 'title' => 'تیکتی ندارید', 'message' => 'سؤال یا شکایتی دارید؟ تیم پشتیبانی فرت پاسخگوست.', 'action_url' => url('my/tickets/create'), 'action_label' => 'ثبت تیکت']) ?></div>
<?php else: ?>
<div class="stack">
  <?php foreach ($tickets as $t): ?>
    <a class="card card--hover flex-between" href="<?= url('my/tickets/' . $t->id()) ?>" style="text-decoration:none;color:inherit;flex-wrap:wrap;gap:8px">
      <div>
        <span class="code-chip"><?= e($t->code) ?></span>
        <b class="ms-2"><?= e($t->subject) ?></b>
        <div class="text-sm text-muted mt-1"><?= jrelative($t->created_at) ?></div>
      </div>
      <div class="flex gap-2">
        <?= component('status-pill', ['group' => 'ticket', 'status' => $t->status]) ?>
        <span class="badge badge--muted"><?= e(\Core\Statuses::label('priority', (string) $t->priority)) ?></span>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
