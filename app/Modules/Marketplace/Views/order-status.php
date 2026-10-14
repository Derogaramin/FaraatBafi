<?php
/** ویوی وضعیت سفارش (عمومی) — Marketplace::order-status */
$this->extend('layouts.public');
$steps = ['pending', 'confirmed', 'in_production', 'ready', 'shipped', 'delivered'];
$labels = ['pending' => 'ثبت سفارش', 'confirmed' => 'تأیید', 'in_production' => 'در حال بافت', 'ready' => 'آمادهٔ ارسال', 'shipped' => 'ارسال', 'delivered' => 'تحویل'];
$currentIdx = array_search($order['status'], $steps, true);
$cancelled = in_array($order['status'], ['cancelled', 'returned'], true);
?>
<section class="container-narrow" style="padding:var(--space-6) 0">
  <div class="breadcrumb"><a href="<?= url('/') ?>">خانه</a> <span>/</span> <span>وضعیت سفارش <?= e($order['code']) ?></span></div>

  <div class="card mb-4">
    <div class="flex-between" style="flex-wrap:wrap">
      <div>
        <h1 class="mb-2" style="font-size:1.25rem">سفارش <span class="mono-code"><?= e($order['code']) ?></span></h1>
        <div class="text-sm text-muted">ثبت: <?= jdt($order['placed_at'], 'Y/m/d', true) ?> • کانال: <?= e(\Core\Statuses::CHANNEL[$order['channel']] ?? $order['channel']) ?></div>
      </div>
      <?= component('status-pill', ['group' => 'order', 'status' => $order['status'], 'size' => 'lg']) ?>
    </div>

    <?php if (!$cancelled): ?>
      <?= component('progress-steps', ['steps' => $steps, 'labels' => $labels, 'current' => $order['status']]) ?>
    <?php else: ?>
      <div class="card mt-4" style="background:var(--color-danger-light);border:none">
        <b class="text-danger"><?= icon('x', '', 16) ?> این سفارش <?= e(\Core\Statuses::label('order', $order['status'])) ?> شده است.</b>
      </div>
    <?php endif; ?>
  </div>

  <div class="card mb-4">
    <h3 class="card__title mb-3">اقلام سفارش</h3>
    <?php foreach ($items as $item): ?>
      <div class="flex-between" style="padding:var(--space-2) 0;border-bottom:1px dashed var(--color-border);flex-wrap:wrap;gap:8px">
        <div class="flex gap-3">
          <div class="wo-card__thumb" style="width:52px;height:52px">
            <?php if (!empty($item['image'])): ?>
              <img src="<?= e(upload_asset($item['image'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">
            <?php endif; ?>
          </div>
          <div>
            <div class="fw-bold text-sm"><?= e($item['title']) ?></div>
            <div class="code-chip" style="font-size:.66rem"><?= e($item['sku']) ?></div>
            <div class="text-sm text-muted"><?= fa((string) $item['quantity']) ?> × <span class="num"><?= toman((int) $item['unit_price'], false) ?></span></div>
          </div>
        </div>
        <b class="num"><?= toman((int) $item['total']) ?></b>
      </div>
    <?php endforeach; ?>
    <dl class="spec-list mt-4">
      <dt>جمع اقلام</dt><dd class="num"><?= toman((int) $order['subtotal']) ?></dd>
      <?php if ((int) $order['discount'] > 0): ?>
        <dt>تخفیف</dt><dd class="num text-success">− <?= toman((int) $order['discount']) ?></dd>
      <?php endif; ?>
      <dt>هزینهٔ ارسال</dt><dd class="num"><?= (int) $order['shipping_cost'] === 0 ? 'رایگان' : toman((int) $order['shipping_cost']) ?></dd>
      <dt>مبلغ کل</dt><dd class="num text-primary-brand" style="font-size:1.05rem"><?= toman((int) $order['total']) ?></dd>
      <?php if ($invoice): ?>
        <dt>فاکتور</dt><dd><span class="code-chip"><?= e($invoice['code']) ?></span> — <?= $invoice['paid'] ? '<span class="badge badge--success">پرداخت‌شده</span>' : '<span class="badge badge--warning">در انتظار پرداخت</span>' ?></dd>
      <?php endif; ?>
      <?php if ($shipment): ?>
        <dt>محموله</dt>
        <dd>
          <span class="code-chip"><?= e($shipment['code']) ?></span>
          <?= e($shipment['carrier']) ?>
          <?php if (!empty($shipment['tracking_no'])): ?>— کد رهگیری: <span class="mono-code"><?= e($shipment['tracking_no']) ?></span><?php endif; ?>
        </dd>
      <?php endif; ?>
    </dl>
  </div>

  <div class="card">
    <h3 class="card__title mb-2"><?= icon('qr', '', 18) ?> کد اصالت محصولات</h3>
    <p class="text-sm text-secondary m-0">
      پس از تکمیل بافت و بازرسی کیفیت، کدهای اصالت FB برای اقلام این سفارش صادر می‌شود
      و روی برچسب QR هر محصول درج خواهد بود. از <a href="<?= url('authenticity') ?>">صفحهٔ استعلام اصالت</a> می‌توانید آن‌ها را راستی‌آزمایی کنید.
    </p>
  </div>
</section>
