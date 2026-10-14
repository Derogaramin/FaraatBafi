<?php
/** ویوی مکاتبات داخلی — Admin::governance.letters */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$kindLabels = ['letter' => 'نامه', 'memo' => 'یادداشت', 'notice' => 'اطلاعیه'];
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>مکاتبات</span></div>
    <h1>مکاتبات داخلی</h1>
    <div class="page-header__sub">کدگذاری <span class="code-chip">IR-1403-0105</span></div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 2.2fr;gap:var(--space-5);align-items:start" id="lt-grid">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> نامهٔ جدید</h3>
    <form method="post" action="<?= url('admin/governance/letters') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0">
        <label class="form-label">گیرنده <span class="required">*</span></label>
        <select class="form-control" name="to_user_id" required>
          <option value="">انتخاب…</option>
          <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['code']) ?> — <?= e($u['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group m-0">
        <label class="form-label">نوع</label>
        <select class="form-control" name="kind">
          <option value="letter">نامه</option><option value="memo">یادداشت</option><option value="notice">اطلاعیه</option>
        </select>
      </div>
      <div class="form-group m-0"><label class="form-label">موضوع <span class="required">*</span></label><input class="form-control" name="subject" required></div>
      <div class="form-group m-0"><label class="form-label">متن <span class="required">*</span></label><textarea class="form-control" name="body" rows="5" required style="line-height:2.1"></textarea></div>
      <button class="btn btn--primary" type="submit"><?= icon('letter', '', 15) ?> ارسال نامه</button>
    </form>
  </div>

  <div class="stack">
    <?php if ($letters === []): ?>
      <div class="card"><?= component('empty-state', ['icon' => 'letter', 'title' => 'مکاتبه‌ای وجود ندارد']) ?></div>
    <?php else: foreach ($letters as $l): ?>
      <div class="card">
        <div class="flex-between mb-2" style="flex-wrap:wrap;gap:6px">
          <div>
            <span class="code-chip"><?= e($l['code']) ?></span>
            <span class="badge badge--muted"><?= e($kindLabels[$l['kind']] ?? $l['kind']) ?></span>
            <b class="ms-2"><?= e($l['subject']) ?></b>
          </div>
          <span class="text-sm text-muted"><?= jrelative($l['sent_at']) ?></span>
        </div>
        <p class="text-sm text-secondary" style="white-space:pre-line;margin-bottom:var(--space-2)"><?= e($l['body']) ?></p>
        <div class="text-sm text-muted">
          از: <?= e((string) ($l['from_name'] ?? 'سامانه')) ?> ← به: <?= e((string) ($l['to_name'] ?? '—')) ?>
          <?= $l['is_read'] ? '• <span class="badge badge--success">خوانده‌شده</span>' : '• <span class="badge badge--warning">خوانده‌نشده</span>' ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<style>@media (max-width:900px){#lt-grid{grid-template-columns:1fr !important}}</style>
