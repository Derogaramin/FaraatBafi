<?php
/** ویوی فهرست واریانت‌ها (SKU) — Catalog::admin.variants */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>محصولات</span></div>
    <h1>محصولات فروشگاهی (SKU)</h1>
    <div class="page-header__sub">الگو: <span class="code-chip">[MODEL]-[COLOR]-[PATTERN]</span> — مثل <span class="code-chip">HT-2LBSTD-CH125-R01-P0003</span></div>
  </div>
  <a class="btn btn--primary" href="<?= url('admin/catalog/variants/create') ?>"><?= icon('plus', '', 16) ?> واریانت جدید</a>
</div>

<form class="filter-bar" method="get" action="<?= url('admin/catalog/variants') ?>">
  <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="جست‌وجو در SKU / نام محصول / رنگ…">
  <button class="btn btn--sm btn--outline" type="submit"><?= icon('search', '', 15) ?></button>
</form>

<?php if ($variants === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'box', 'title' => 'واریانتی ثبت نشده است', 'message' => 'ابتدا مدل فنی، رنگ و طرح را بسازید سپس SKU ترکیب کنید.', 'action_url' => url('admin/catalog/variants/create')]) ?></div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>SKU</th><th>محصول</th><th>رنگ</th><th>طرح</th><th>قیمت</th><th>موجودی</th><th>ویژه</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($variants as $v): ?>
      <tr>
        <td><span class="code-chip"><?= e($v['sku']) ?></span></td>
        <td><?= e($v['model_name']) ?></td>
        <td><span class="color-dot" style="background:<?= e($v['hex']) ?>"></span> <?= e($v['color_name']) ?></td>
        <td><?= e($v['pattern_name']) ?></td>
        <td class="num"><?= toman((int) $v['price'], false) ?></td>
        <td class="num">
          <?php if ((int) $v['stock'] <= 0): ?><span class="badge badge--danger">۰</span>
          <?php elseif ((int) $v['stock'] <= 3): ?><span class="badge badge--warning"><?= fa((string) $v['stock']) ?></span>
          <?php else: ?><?= fa((string) $v['stock']) ?><?php endif; ?>
        </td>
        <td><?= $v['is_featured'] ? icon('star', 'text-warning', 15) : '—' ?></td>
        <td><?= $v['is_active'] ? '<span class="badge badge--success">فعال</span>' : '<span class="badge badge--muted">غیرفعال</span>' ?></td>
        <td class="actions">
          <a class="btn btn--sm btn--outline" href="<?= url('marketplace/product/' . rawurlencode((string) $v['sku'])) ?>" target="_blank" title="نمایش در بازارگاه"><?= icon('eye', '', 13) ?></a>
          <a class="btn btn--sm btn--outline" href="<?= url('admin/catalog/variants/' . $v['id'] . '/edit') ?>"><?= icon('edit', '', 13) ?></a>
          <form method="post" action="<?= url('admin/catalog/variants/' . $v['id'] . '/toggle') ?>" class="m-0">
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
