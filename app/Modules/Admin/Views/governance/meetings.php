<?php
/** ویوی جلسات — Admin::governance.meetings */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$canManage = \Core\Auth::can('governance.manage');
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>جلسات</span></div>
    <h1>جلسات هیئت‌مدیره</h1>
    <div class="page-header__sub">کدگذاری <span class="code-chip">MT-1403-04</span></div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 2.2fr;gap:var(--space-5);align-items:start" id="mt-grid">
  <?php if ($canManage): ?>
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> جلسهٔ جدید</h3>
    <form method="post" action="<?= url('admin/governance/meetings') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0"><label class="form-label">عنوان <span class="required">*</span></label><input class="form-control" name="title" required placeholder="جلسهٔ ماهانهٔ مهر"></div>
      <div class="form-row">
        <div class="form-group m-0"><label class="form-label">تاریخ (شمسی) <span class="required">*</span></label><input class="form-control ltr jalali-date" name="meeting_date" placeholder="<?= e(\Core\Jalali::format('Y/m/d')) ?>" required></div>
        <div class="form-group m-0"><label class="form-label">محل</label><input class="form-control" name="location" placeholder="دفتر مرکزی"></div>
      </div>
      <div class="form-group m-0"><label class="form-label">دستور جلسه</label><textarea class="form-control" name="agenda" rows="3"></textarea></div>
      <button class="btn btn--primary" type="submit"><?= icon('meeting', '', 15) ?> ثبت جلسه</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="stack">
    <?php if (count($meetings) === 0): ?>
      <div class="card"><?= component('empty-state', ['icon' => 'meeting', 'title' => 'جلسه‌ای ثبت نشده است']) ?></div>
    <?php else: foreach ($meetings as $m): ?>
      <a class="card card--hover flex-between" href="<?= url('admin/governance/meetings/' . $m->id()) ?>" style="text-decoration:none;color:inherit;flex-wrap:wrap;gap:8px">
        <div>
          <span class="code-chip"><?= e($m->code) ?></span>
          <b class="ms-2"><?= e($m->title) ?></b>
          <div class="text-sm text-muted mt-1"><?= icon('clock', '', 13) ?> <?= jdt($m->meeting_date) ?><?php if ($m->location): ?> • <?= icon('map-pin', '', 13) ?> <?= e($m->location) ?><?php endif; ?></div>
        </div>
        <span class="badge badge--<?= ['scheduled' => 'info', 'held' => 'success', 'cancelled' => 'danger'][$m->status] ?? 'muted' ?>">
          <?= ['scheduled' => 'برنامه‌ریزی‌شده', 'held' => 'برگزارشده', 'cancelled' => 'لغوشده'][$m->status] ?? $m->status ?>
        </span>
      </a>
    <?php endforeach; endif; ?>
  </div>
</div>
<style>@media (max-width:900px){#mt-grid{grid-template-columns:1fr !important}}</style>
