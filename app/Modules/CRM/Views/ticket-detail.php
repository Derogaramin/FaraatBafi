<?php
/** ویوی جزئیات تیکت (کاربر) — CRM::ticket-detail */
$this->extend('layouts.panel', ['panel' => 'account', 'panelTitle' => 'پنل کاربر']);
$t = $ticket->attributes();
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('my/tickets') ?>">تیکت‌ها</a> <span>/</span> <span><?= e($t['code']) ?></span></div>
    <h1><?= e($t['subject']) ?></h1>
    <div class="page-header__sub"><span class="code-chip"><?= e($t['code']) ?></span> • <?= jdt($t['created_at'], 'Y/m/d', true) ?></div>
  </div>
  <?= component('status-pill', ['group' => 'ticket', 'status' => $t['status'], 'size' => 'lg']) ?>
</div>

<div class="card mb-4">
  <p style="white-space:pre-line;margin:0"><?= e($t['body']) ?></p>
</div>

<?php foreach ($replies as $r): ?>
  <div class="card mb-3" style="<?= $r['is_staff'] ? 'border-inline-start:4px solid var(--color-secondary)' : '' ?>">
    <div class="flex-between mb-2">
      <b class="text-sm"><?= e($r['name']) ?> <?= $r['is_staff'] ? '<span class="badge badge--secondary">پشتیبانی فرت</span>' : '' ?></b>
      <span class="text-sm text-muted"><?= jrelative($r['created_at']) ?></span>
    </div>
    <p class="m-0" style="white-space:pre-line"><?= e($r['body']) ?></p>
  </div>
<?php endforeach; ?>

<?php if ($t['status'] !== 'closed'): ?>
<div class="card">
  <h3 class="card__title mb-3"><?= icon('letter', '', 18) ?> ارسال پاسخ</h3>
  <form method="post" action="<?= url('my/tickets/' . $t['id'] . '/reply') ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <textarea class="form-control" name="body" rows="4" required placeholder="متن پاسخ…"></textarea>
    </div>
    <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ثبت پاسخ</button>
  </form>
</div>
<?php endif; ?>
