<?php
/** ویوی حرکت‌های انبار — Production::admin.movements */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$refLabels = ['purchase' => 'خرید', 'distribution' => 'توزیع به بافنده', 'delivery' => 'تحویل بافت', 'sale' => 'فروش', 'return' => 'مرجوعی', 'adjust' => 'اصلاح دستی', 'consignment' => 'کالای امانی'];
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <a href="<?= url('admin/inventory/materials') ?>">انبار</a> <span>/</span> <span>حرکت‌ها</span></div>
    <h1>حرکت‌های انبار</h1>
    <div class="page-header__sub">مجموع <?= fa((string) $paginator->total) ?> حرکت ثبت‌شده</div>
  </div>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>#</th><th>نوع</th><th>قلم</th><th>مقدار</th><th>علت</th><th>کاربر</th><th>تاریخ</th></tr></thead>
    <tbody>
      <?php foreach ($movements as $m): ?>
      <tr>
        <td class="num text-muted"><?= fa((string) $m['id']) ?></td>
        <td><?= $m['direction'] === 'in'
            ? '<span class="badge badge--success">↙ ورود</span>'
            : '<span class="badge badge--warning">↗ خروج</span>' ?></td>
        <td>
          <?php if (!empty($m['material_name'])): ?>
            <?= e($m['material_name']) ?> <span class="code-chip" style="font-size:.62rem"><?= e($m['material_code']) ?></span>
          <?php elseif (!empty($m['sku'])): ?>
            <?= e($m['sku']) ?>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td class="num"><?= fa(rtrim(rtrim(number_format((float) $m['quantity'], 2), '0'), '.')) ?> <?= e((string) ($m['unit'] ?? '')) ?></td>
        <td><span class="badge badge--muted"><?= e($refLabels[$m['ref_type']] ?? $m['ref_type']) ?></span>
          <?php if (!empty($m['note'])): ?><div class="text-sm text-muted"><?= e($m['note']) ?></div><?php endif; ?>
        </td>
        <td class="text-sm"><?= e($m['user_name'] ?? '—') ?></td>
        <td class="text-sm"><?= jdt($m['created_at'], 'Y/m/d', true) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $paginator->links(url('admin/inventory/movements')) ?>
