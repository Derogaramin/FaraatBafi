<?php
/** ویوی جزئیات جلسه و مصوبات — Admin::governance.meeting-detail */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$canManage = \Core\Auth::can('governance.manage');
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/governance/meetings') ?>">جلسات</a> <span>/</span> <span><?= e($meeting['code']) ?></span></div>
    <h1><?= e($meeting['title']) ?> <span class="code-chip"><?= e($meeting['code']) ?></span></h1>
    <div class="page-header__sub"><?= jdt($meeting['meeting_date']) ?><?php if ($meeting['location']): ?> • <?= e($meeting['location']) ?><?php endif; ?></div>
  </div>
</div>

<?php if (!empty($meeting['agenda'])): ?>
<div class="card mb-4">
  <h3 class="card__title mb-2"><?= icon('letter', '', 18) ?> دستور جلسه</h3>
  <p style="white-space:pre-line;margin:0"><?= e($meeting['agenda']) ?></p>
</div>
<?php endif; ?>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('vote', '', 18) ?> مصوبات</h3>
    <?php if (count($resolutions) === 0): ?><p class="text-sm text-muted">مصوبه‌ای ثبت نشده است.</p>
    <?php else: foreach ($resolutions as $r): ?>
      <div style="padding:var(--space-3) 0;border-bottom:1px dashed var(--color-border)">
        <div class="flex gap-2">
          <span class="code-chip"><?= e($r->code) ?></span>
          <b class="text-sm"><?= e($r->title) ?></b>
          <span class="badge badge--<?= $r->status === 'approved' ? 'success' : ($r->status === 'rejected' ? 'danger' : 'warning') ?>"><?= ['approved' => 'تصویب', 'pending' => 'در انتظار', 'rejected' => 'رد'][$r->status] ?? '' ?></span>
        </div>
        <?php if ($r->body): ?><p class="text-sm text-secondary mt-1 mb-0" style="white-space:pre-line"><?= e($r->body) ?></p><?php endif; ?>
      </div>
    <?php endforeach; endif; ?>

    <?php if ($canManage): ?>
    <form method="post" action="<?= url('admin/governance/meetings/' . $meeting['id'] . '/resolution') ?>" class="stack mt-4">
      <?= csrf_field() ?>
      <div class="form-group m-0"><input class="form-control" name="title" placeholder="عنوان مصوبه" required></div>
      <div class="form-group m-0"><textarea class="form-control" name="body" rows="2" placeholder="متن مصوبه"></textarea></div>
      <button class="btn btn--sm btn--secondary" type="submit"><?= icon('plus', '', 14) ?> افزودن مصوبه (کد RES خودکار)</button>
    </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card__title mb-3"><?= icon('book', '', 18) ?> صورت‌جلسه</h3>
    <?php if ($canManage): ?>
      <form method="post" action="<?= url('admin/governance/meetings/' . $meeting['id'] . '/minutes') ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-group m-0">
          <textarea class="form-control" name="minutes" rows="10" style="line-height:2.2" required><?= e((string) ($meeting['minutes'] ?? '')) ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group m-0">
            <label class="form-label">وضعیت</label>
            <select class="form-control" name="status">
              <option value="scheduled" <?= $meeting['status'] === 'scheduled' ? 'selected' : '' ?>>برنامه‌ریزی‌شده</option>
              <option value="held" <?= $meeting['status'] === 'held' ? 'selected' : '' ?>>برگزارشده</option>
              <option value="cancelled" <?= $meeting['status'] === 'cancelled' ? 'selected' : '' ?>>لغوشده</option>
            </select>
          </div>
        </div>
        <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ذخیرهٔ صورت‌جلسه</button>
      </form>
    <?php else: ?>
      <p style="white-space:pre-line;margin:0"><?= e((string) ($meeting['minutes'] ?? 'صورت‌جلسه‌ای ثبت نشده است.')) ?></p>
    <?php endif; ?>
  </div>
</div>
