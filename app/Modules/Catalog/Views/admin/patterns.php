<?php
/** ویوی کتابخانهٔ طرح — Catalog::admin.patterns */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>طرح‌ها</span></div>
    <h1>کتابخانهٔ طرح</h1>
    <div class="page-header__sub">کد طرح: <span class="code-chip">P</span> + چهار رقم صعودی — مثل <span class="code-chip">P0042</span></div>
  </div>
  <a class="btn btn--primary" href="<?= url('admin/catalog/patterns/create') ?>"><?= icon('plus', '', 16) ?> طرح جدید</a>
</div>

<?php if (count($patterns) === 0): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'star', 'title' => 'طرحی ثبت نشده است', 'action_url' => url('admin/catalog/patterns/create'), 'action_label' => 'ثبت اولین طرح']) ?></div>
<?php else: ?>
<div class="product-grid">
  <?php foreach ($patterns as $p): ?>
    <div class="card card--hover" style="padding:0;overflow:hidden">
      <div style="aspect-ratio:4/3;background:var(--color-bg-subtle) var(--pattern-hero) center/140px">
        <?php if ($p->image): ?><img src="<?= e(upload_asset($p->image)) ?>" alt="<?= e($p->name_fa) ?>" style="width:100%;height:100%;object-fit:cover"><?php endif; ?>
      </div>
      <div style="padding:var(--space-4)">
        <div class="flex-between">
          <span class="code-chip"><?= e($p->code) ?></span>
          <?= $p->is_active ? '<span class="badge badge--success">فعال</span>' : '<span class="badge badge--muted">غیرفعال</span>' ?>
        </div>
        <h3 class="card__title mt-2 mb-1"><?= e($p->name_fa) ?></h3>
        <?php if ($p->designer): ?><div class="text-sm text-muted">طراح: <?= e($p->designer) ?></div><?php endif; ?>
        <?php if ($p->motif): ?><div class="text-sm text-muted">نقش‌مایه: <?= e($p->motif) ?></div><?php endif; ?>
        <a class="btn btn--sm btn--outline mt-3" href="<?= url('admin/catalog/patterns/' . $p->id() . '/edit') ?>"><?= icon('edit', '', 13) ?> ویرایش</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
