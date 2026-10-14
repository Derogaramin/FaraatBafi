<?php
/** ویوی جزئیات تیکت در پنل مدیر — CRM::admin.detail */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/tickets') ?>">تیکت‌ها</a> <span>/</span> <span><?= e($ticket['code']) ?></span></div>
    <h1><?= e($ticket['subject']) ?></h1>
    <div class="page-header__sub">
      <span class="code-chip"><?= e($ticket['code']) ?></span> •
      <?= e($user['name'] ?? '') ?> (<span class="mono-code"><?= e($user['code'] ?? '') ?></span>) •
      <?= jdt($ticket['created_at'], 'Y/m/d', true) ?>
      <?php if (!empty($ticket['order_code'])): ?> • سفارش: <span class="mono-code"><?= e($ticket['order_code']) ?></span><?php endif; ?>
    </div>
  </div>
  <div class="flex gap-2">
    <?= component('status-pill', ['group' => 'ticket', 'status' => $ticket['status'], 'size' => 'lg']) ?>
    <?= component('status-pill', ['group' => 'priority', 'status' => $ticket['priority']]) ?>
  </div>
</div>

<div class="card mb-4">
  <p style="white-space:pre-line;margin:0"><?= e($ticket['body']) ?></p>
</div>

<?php foreach ($replies as $r): ?>
  <div class="card mb-3" style="<?= $r['is_staff'] ? 'border-inline-start:4px solid var(--color-secondary)' : '' ?>">
    <div class="flex-between mb-2">
      <b class="text-sm"><?= e($r['name']) ?> <span class="code-chip" style="font-size:.6rem"><?= e($r['user_code']) ?></span> <?= $r['is_staff'] ? '<span class="badge badge--secondary">پشتیبانی</span>' : '<span class="badge badge--muted">کاربر</span>' ?></b>
      <span class="text-sm text-muted"><?= jrelative($r['created_at']) ?></span>
    </div>
    <p class="m-0" style="white-space:pre-line"><?= e($r['body']) ?></p>
  </div>
<?php endforeach; ?>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('letter', '', 18) ?> پاسخ پشتیبانی</h3>
    <form method="post" action="<?= url('admin/tickets/' . $ticket['id'] . '/reply') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <textarea class="form-control" name="body" rows="4" required placeholder="پاسخ تیم پشتیبانی…"></textarea>
      </div>
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ارسال پاسخ</button>
    </form>
  </div>
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('settings', '', 18) ?> تغییر وضعیت</h3>
    <?php foreach ($statuses ?? \Core\Statuses::TICKET as $key => $s): ?>
      <form method="post" action="<?= url('admin/tickets/' . $ticket['id'] . '/status') ?>" class="mb-2">
        <?= csrf_field() ?>
        <input type="hidden" name="status" value="<?= e($key) ?>">
        <button class="btn btn--sm <?= $ticket['status'] === $key ? 'btn--primary' : 'btn--outline' ?>" type="submit" <?= $ticket['status'] === $key ? 'disabled' : '' ?>>
          <?= e($s['label']) ?>
        </button>
      </form>
    <?php endforeach; ?>
  </div>
</div>
