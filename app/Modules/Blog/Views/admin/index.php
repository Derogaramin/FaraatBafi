<?php
/** ویوی مدیریت وبلاگ — Blog::admin.index */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>وبلاگ</span></div>
    <h1>مدیریت وبلاگ</h1>
  </div>
  <a class="btn btn--primary" href="<?= url('admin/blog/create') ?>"><?= icon('plus', '', 16) ?> نوشتهٔ جدید</a>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>عنوان</th><th>نامک</th><th>دسته</th><th>وضعیت</th><th>بازدید</th><th>انتشار</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($posts as $p): ?>
      <tr>
        <td class="fw-bold"><?= e($p->title) ?></td>
        <td><span class="code-chip" style="font-size:.66rem"><?= e($p->slug) ?></span></td>
        <td><span class="badge badge--muted"><?= e($p->category) ?></span></td>
        <td><?= $p->status === 'published' ? '<span class="badge badge--success">منتشرشده</span>' : '<span class="badge badge--warning">پیش‌نویس</span>' ?></td>
        <td class="num"><?= fa((string) $p->views) ?></td>
        <td class="text-sm"><?= $p->published_at ? jdt($p->published_at) : '—' ?></td>
        <td class="actions">
          <?php if ($p->status === 'published'): ?>
            <a class="btn btn--sm btn--ghost" target="_blank" href="<?= url('blog/' . rawurlencode((string) $p->slug)) ?>"><?= icon('eye', '', 13) ?></a>
          <?php endif; ?>
          <a class="btn btn--sm btn--outline" href="<?= url('admin/blog/' . $p->id() . '/edit') ?>"><?= icon('edit', '', 13) ?></a>
          <form method="post" action="<?= url('admin/blog/' . $p->id() . '/delete') ?>" data-confirm="نوشتهٔ «<?= e($p->title) ?>» حذف شود؟" data-confirm-danger>
            <?= csrf_field() ?>
            <button class="btn btn--sm btn--ghost text-danger" type="submit"><?= icon('trash', '', 13) ?></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
