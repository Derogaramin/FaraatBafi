<?php
/** ویوی کالای امانی — Agency::consignment */
$this->extend('layouts.panel', ['panel' => 'agency', 'panelTitle' => 'نمایندگی ' . ($agency['code'] ?? '')]);
$totalValue = 0;
foreach ($consignment as $c) { $totalValue += max(0, (int) $c['quantity']) * (int) $c['price']; }
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('agency') ?>">داشبورد</a> <span>/</span> <span>کالای امانی</span></div>
    <h1>کالای امانی نزد نمایندگی</h1>
    <div class="page-header__sub">ارزش کل: <b class="num"><?= toman($totalValue) ?></b></div>
  </div>
</div>

<?php if ($consignment === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'warehouse', 'title' => 'کالای امانی ندارید', 'message' => 'برای دریافت کالا با دفتر تعاونی هماهنگ کنید.']) ?></div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>SKU</th><th>محصول</th><th>رنگ</th><th>موجودی</th><th>فی فروش</th><th>ارزش</th></tr></thead>
    <tbody>
      <?php foreach ($consignment as $c): ?>
      <tr>
        <td><span class="code-chip" style="font-size:.68rem"><?= e($c['sku']) ?></span></td>
        <td><?= e($c['product_name']) ?></td>
        <td><?= e($c['color_name']) ?></td>
        <td class="num"><?= (int) $c['quantity'] < 0 ? '<span class="badge badge--danger">' . fa((string) $c['quantity']) . '</span>' : '<b>' . fa((string) $c['quantity']) . '</b>' ?></td>
        <td class="num"><?= toman((int) $c['price'], false) ?></td>
        <td class="num"><?= toman(max(0, (int) $c['quantity']) * (int) $c['price'], false) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
