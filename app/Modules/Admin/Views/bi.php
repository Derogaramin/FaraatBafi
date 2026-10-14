<?php
/** ویوی هوش تجاری — Admin::bi */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$channelLabels = \Core\Statuses::CHANNEL;
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>هوش تجاری</span></div>
    <h1>هوش تجاری</h1>
    <div class="page-header__sub">تحلیل فروش، عملکرد بافندگان و کیفیت</div>
  </div>
  <a class="btn btn--sm btn--outline" href="<?= url('admin/bi/weavers') ?>"><?= icon('shuttle', '', 14) ?> جدول کامل بافندگان</a>
</div>

<div class="grid grid-2 mb-4" style="align-items:start">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('star', '', 18) ?> پرفروش‌ترین محصولات</h3>
    <?php if ($topProducts === []): ?><p class="text-sm text-muted m-0">داده‌ای نیست.</p><?php else: ?>
    <table class="table" style="min-width:0">
      <thead><tr><th>SKU</th><th>محصول</th><th>فروش</th><th>درآمد</th></tr></thead>
      <tbody>
        <?php foreach ($topProducts as $p): ?>
        <tr>
          <td><span class="code-chip" style="font-size:.6rem"><?= e($p['sku']) ?></span></td>
          <td class="text-sm"><?= e($p['title']) ?></td>
          <td class="num"><?= fa((string) $p['sold']) ?></td>
          <td class="num"><?= toman((int) $p['revenue'], false) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card__title mb-3"><?= icon('shuttle', '', 18) ?> برترین بافندگان</h3>
    <?php if ($topWeavers === []): ?><p class="text-sm text-muted m-0">داده‌ای نیست.</p><?php else: ?>
    <table class="table" style="min-width:0">
      <thead><tr><th>کد</th><th>نام</th><th>تکمیل</th><th>امتیاز QC</th></tr></thead>
      <tbody>
        <?php foreach ($topWeavers as $w): ?>
        <tr>
          <td><span class="code-chip"><?= e($w['code']) ?></span></td>
          <td class="text-sm fw-bold"><?= e($w['name']) ?></td>
          <td class="num"><?= fa((string) $w['done']) ?></td>
          <td><?= $w['avg_score'] !== null ? '<span class="badge badge--success">' . fa((string) $w['avg_score']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card__title mb-3"><?= icon('alert', '', 18) ?> شایع‌ترین عیوب بافت</h3>
    <?php if ($defects === []): ?>
      <p class="text-sm text-muted m-0">رد یا عیبی ثبت نشده — کیفیت عالی است! 🌿</p>
    <?php else: foreach ($defects as $d): ?>
      <div class="flex-between text-sm" style="padding:8px 0;border-bottom:1px dashed var(--color-border)">
        <span><?= e($d['name_fa']) ?></span>
        <span class="badge badge--danger"><?= fa((string) $d['c']) ?> مورد</span>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card">
    <h3 class="card__title mb-3"><?= icon('store', '', 18) ?> فروش به تفکیک کانال</h3>
    <?php foreach ($channels as $c): ?>
      <?php $maxRev = max(1, ...array_column($channels, 'revenue')); ?>
      <div class="mb-3">
        <div class="flex-between text-sm mb-1">
          <span><?= e($channelLabels[$c['channel']] ?? $c['channel']) ?> — <?= fa((string) $c['cnt']) ?> سفارش</span>
          <b class="num"><?= toman_short((int) $c['revenue']) ?> تومان</b>
        </div>
        <div class="progress"><div class="progress__bar" style="width:<?= (int) round(((int) $c['revenue'] / (int) $maxRev) * 100) ?>%"></div></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
