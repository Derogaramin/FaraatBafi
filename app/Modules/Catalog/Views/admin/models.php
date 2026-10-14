<?php
/** ویوی فهرست مدل‌های فنی — Catalog::admin.models */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>مدل‌های فنی</span></div>
    <h1>مدل‌های فنی محصول</h1>
    <div class="page-header__sub">الگوی کد: <span class="code-chip">[TYPE]-[WEAVE][KNOT][STITCH]-CH[XX]</span> — مثل <span class="code-chip">HT-2LBSTD-CH125</span></div>
  </div>
  <a class="btn btn--primary" href="<?= url('admin/catalog/models/create') ?>"><?= icon('plus', '', 16) ?> مدل فنی جدید</a>
</div>

<form class="filter-bar" method="get" action="<?= url('admin/catalog/models') ?>">
  <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="جست‌وجو در کد یا نام مدل…">
  <button class="btn btn--sm btn--outline" type="submit"><?= icon('search', '', 15) ?></button>
</form>

<?php if ($models === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'loom', 'title' => 'مدل فنی ثبت نشده است', 'action_url' => url('admin/catalog/models/create'), 'action_label' => 'ساخت اولین مدل']) ?></div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead>
      <tr><th>کد مدل</th><th>نام</th><th>نوع</th><th>پود</th><th>گره</th><th>دوخت</th><th>چله</th><th>قیمت پایه</th><th>واریانت‌ها</th><th>وضعیت</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($models as $m): ?>
      <tr>
        <td><span class="code-chip"><?= e($m['code']) ?></span></td>
        <td><?= e($m['name_fa']) ?></td>
        <td><span class="badge badge--muted"><?= e($m['type_code']) ?></span> <?= e($m['type_name']) ?></td>
        <td><?= e($weaves[$m['weave']] ?? $m['weave']) ?></td>
        <td><?= e($knots[$m['knot']] ?? $m['knot']) ?></td>
        <td><?= e($stitches[$m['stitch']] ?? $m['stitch']) ?></td>
        <td class="num"><?= fa((string) $m['chelle']) ?></td>
        <td class="num"><?= toman((int) $m['base_price'], false) ?></td>
        <td class="num"><?= fa((string) $m['variants_count']) ?></td>
        <td><?= $m['is_active'] ? '<span class="badge badge--success">فعال</span>' : '<span class="badge badge--muted">غیرفعال</span>' ?></td>
        <td class="actions">
          <a class="btn btn--sm btn--outline" href="<?= url('admin/catalog/models/' . $m['id'] . '/edit') ?>"><?= icon('edit', '', 13) ?></a>
          <form method="post" action="<?= url('admin/catalog/models/' . $m['id'] . '/toggle') ?>" class="m-0">
            <?= csrf_field() ?>
            <button class="btn btn--sm btn--ghost" type="submit" title="تغییر وضعیت"><?= icon('refresh', '', 13) ?></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
