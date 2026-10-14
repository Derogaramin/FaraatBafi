<?php
/** ویوی پروفایل نمایندگی — Members::admin.agency-profile */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/agencies') ?>">نمایندگی‌ها</a> <span>/</span> <span><?= e($agency['code']) ?></span></div>
    <h1><?= e($agency['name']) ?> <span class="code-chip"><?= e($agency['code']) ?></span></h1>
    <div class="page-header__sub"><?= e((string) ($agency['city'] ?: '')) ?> — مدیر: <?= e((string) ($agency['manager_name'] ?: '—')) ?> — پورسانت <?= fa((string) $agency['commission_rate']) ?>٪</div>
  </div>
  <a class="btn btn--sm btn--outline" href="<?= url('admin/agencies/' . $agency['id'] . '/edit') ?>"><?= icon('edit', '', 14) ?> ویرایش</a>
</div>

<div class="kpi-grid mb-4">
  <?= component('kpi-card', ['label' => 'کل فروش', 'value' => fa((string) ($salesStats['cnt'] ?? 0)) . ' سفارش', 'icon' => 'cart', 'color' => 'action']) ?>
  <?= component('kpi-card', ['label' => 'درآمد فروش', 'value' => toman((int) ($salesStats['revenue'] ?? 0)), 'icon' => 'coin', 'color' => 'success']) ?>
  <?= component('kpi-card', ['label' => 'ماندهٔ حساب', 'value' => toman((int) $agency['balance']), 'icon' => 'money', 'color' => ((int) $agency['balance'] > 0 ? 'warning' : 'secondary')]) ?>
  <?= component('kpi-card', ['label' => 'اقلام امانی', 'value' => fa((string) count($consignment)), 'icon' => 'warehouse', 'color' => 'primary']) ?>
</div>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('truck', '', 18) ?> ارسال کالای امانی</h3>
    <form method="post" action="<?= url('admin/agencies/' . $agency['id'] . '/consign') ?>" class="flex gap-2" style="flex-wrap:wrap">
      <?= csrf_field() ?>
      <select class="form-control" name="variant_id" required style="flex:2;min-width:220px">
        <option value="">انتخاب محصول…</option>
        <?php foreach ($variants as $v): ?>
          <option value="<?= $v['id'] ?>"><?= e($v['name_fa']) ?> — <?= e($v['sku']) ?> (انبار: <?= fa((string) $v['stock']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <input class="form-control num" name="quantity" type="number" min="1" max="500" placeholder="تعداد" required style="width:110px">
      <button class="btn btn--secondary btn--sm" type="submit"><?= icon('download', '', 14) ?> ارسال</button>
    </form>

    <h3 class="card__title mt-5 mb-3">موجودی امانی فعلی</h3>
    <?php if ($consignment === []): ?>
      <p class="text-sm text-muted m-0">کالای امانی نزد این نمایندگی نیست.</p>
    <?php else: ?>
      <table class="table" style="min-width:0">
        <thead><tr><th>SKU</th><th>محصول</th><th>موجودی</th><th>فی</th></tr></thead>
        <tbody>
          <?php foreach ($consignment as $c): ?>
          <tr>
            <td><span class="code-chip" style="font-size:.64rem"><?= e($c['sku']) ?></span></td>
            <td class="text-sm"><?= e($c['product_name']) ?> — <?= e($c['color_name']) ?></td>
            <td class="num"><?= (int) $c['quantity'] < 0 ? '<span class="badge badge--danger">' . fa((string) $c['quantity']) . '</span>' : fa((string) $c['quantity']) ?></td>
            <td class="num text-sm"><?= toman((int) $c['price'], false) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="stack">
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('cart', '', 18) ?> آخرین سفارش‌ها</h3>
      <?php if ($orders === []): ?><p class="text-sm text-muted m-0">سفارشی ثبت نشده است.</p><?php else: ?>
        <?php foreach ($orders as $o): ?>
          <div class="flex-between text-sm" style="padding:6px 0;border-bottom:1px dashed var(--color-border)">
            <a class="mono-code" href="<?= url('admin/orders/' . $o['id']) ?>"><?= e($o['code']) ?></a>
            <?= component('status-pill', ['group' => 'order', 'status' => $o['status']]) ?>
            <span class="num"><?= toman((int) $o['total'], false) ?></span>
            <span class="text-muted"><?= jdt($o['placed_at']) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3 class="card__title mb-3"><?= icon('coin', '', 18) ?> پورسانت‌ها</h3>
      <?php if ($commissions === []): ?><p class="text-sm text-muted m-0">پورسانتی ثبت نشده است.</p><?php else: ?>
        <?php foreach (array_slice($commissions, 0, 8) as $c): ?>
          <div class="flex-between text-sm" style="padding:6px 0;border-bottom:1px dashed var(--color-border)">
            <span class="code-chip" style="font-size:.62rem"><?= e($c['code']) ?></span>
            <span class="num"><?= toman((int) $c['amount'], false) ?></span>
            <?= component('status-pill', ['group' => 'commission', 'status' => $c['status']]) ?>
          </div>
        <?php endforeach; ?>
        <a class="btn btn--sm btn--outline mt-3" href="<?= url('admin/finance/commissions') ?>">مدیریت پورسانت‌ها</a>
      <?php endif; ?>
    </div>
  </div>
</div>
