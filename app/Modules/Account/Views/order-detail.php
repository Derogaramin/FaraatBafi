<?php
/** ویوی جزئیات سفارش مشتری — Account::order-detail */
$this->extend('layouts.panel', ['panel' => 'account', 'panelTitle' => 'پنل مشتری']);
$steps = ['pending', 'confirmed', 'in_production', 'ready', 'shipped', 'delivered'];
$labels = ['pending' => 'ثبت', 'confirmed' => 'تأیید', 'in_production' => 'بافت', 'ready' => 'آماده', 'shipped' => 'ارسال', 'delivered' => 'تحویل'];
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('my/orders') ?>">سفارش‌ها</a> <span>/</span> <span><?= e($order['code']) ?></span></div>
    <h1>سفارش <span class="mono-code"><?= e($order['code']) ?></span></h1>
    <div class="page-header__sub">ثبت <?= jdt($order['placed_at'], 'Y/m/d', true) ?></div>
  </div>
  <?= component('status-pill', ['group' => 'order', 'status' => $order['status'], 'size' => 'lg']) ?>
</div>

<?php if (!in_array($order['status'], ['cancelled', 'returned'], true)): ?>
<div class="card mb-4"><?= component('progress-steps', ['steps' => $steps, 'labels' => $labels, 'current' => $order['status']]) ?></div>
<?php endif; ?>

<div class="card mb-4">
  <h3 class="card__title mb-3"><?= icon('box', '', 18) ?> اقلام</h3>
  <?php foreach ($items as $item): ?>
    <div class="flex-between" style="padding:var(--space-2) 0;border-bottom:1px dashed var(--color-border);flex-wrap:wrap;gap:8px">
      <div class="flex gap-3">
        <div class="wo-card__thumb" style="width:56px;height:56px">
          <?php if (!empty($item['image'])): ?><img src="<?= e(upload_asset($item['image'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit"><?php endif; ?>
        </div>
        <div>
          <div class="fw-bold text-sm"><?= e($item['title']) ?></div>
          <div class="code-chip" style="font-size:.62rem"><?= e($item['sku']) ?></div>
          <div class="text-sm text-muted"><?= fa((string) $item['quantity']) ?> × <span class="num"><?= toman((int) $item['unit_price'], false) ?></span></div>
        </div>
      </div>
      <b class="num"><?= toman((int) $item['total']) ?></b>
    </div>
  <?php endforeach; ?>

  <dl class="spec-list mt-4">
    <dt>جمع اقلام</dt><dd class="num"><?= toman((int) $order['subtotal']) ?></dd>
    <?php if ((int) $order['discount'] > 0): ?><dt>تخفیف</dt><dd class="num text-success">− <?= toman((int) $order['discount']) ?></dd><?php endif; ?>
    <dt>ارسال</dt><dd class="num"><?= (int) $order['shipping_cost'] === 0 ? 'رایگان' : toman((int) $order['shipping_cost']) ?></dd>
    <dt>مبلغ کل</dt><dd class="num text-primary-brand text-lg"><b><?= toman((int) $order['total']) ?></b></dd>
    <?php if ($invoice): ?><dt>فاکتور</dt><dd><span class="code-chip"><?= e($invoice['code']) ?></span> <?= $invoice['paid'] ? '<span class="badge badge--success">پرداخت‌شده</span>' : '<span class="badge badge--warning">در انتظار پرداخت</span>' ?></dd><?php endif; ?>
    <?php if ($shipment): ?>
      <dt>محموله</dt><dd><span class="code-chip"><?= e($shipment['code']) ?></span> <?= e($shipment['carrier']) ?><?= $shipment['tracking_no'] ? ' — رهگیری: <span class="mono-code">' . e($shipment['tracking_no']) . '</span>' : '' ?></dd>
    <?php endif; ?>
    <dt>نشانی</dt><dd style="font-weight:400" class="text-sm"><?= e(trim(($order['city'] ?? '') . '، ' . ($order['address'] ?? ''))) ?></dd>
  </dl>
</div>

<div class="flex gap-2">
  <a class="btn btn--outline btn--sm" href="<?= url('my/tickets/create?order=' . rawurlencode((string) $order['code'])) ?>"><?= icon('ticket', '', 15) ?> ثبت سؤال/شکایت دربارهٔ این سفارش</a>
  <a class="btn btn--ghost btn--sm" href="<?= url('orders/' . rawurlencode((string) $order['code'])) ?>"><?= icon('eye', '', 15) ?> صفحهٔ پیگیری عمومی</a>
</div>
