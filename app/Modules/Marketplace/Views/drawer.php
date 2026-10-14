<?php
/**
 * ویوی سبد کشویی — Marketplace::drawer (بدون لایه؛ داخل کشو بارگذاری می‌شود)
 * داده: details (items/subtotal/count), shipping
 */
$items = $details['items'];
$subtotal = $details['subtotal'];
$freeShipOver = \App\Modules\Marketplace\Services\CheckoutService::FREE_SHIPPING_OVER;
$progress = $subtotal >= $freeShipOver ? 100 : (int) min(100, round($subtotal / max(1, $freeShipOver) * 100));
?>
<div class="drawer__head">
  <h3><?= icon('cart', '', 20) ?> سبد خرید <span class="badge badge--primary"><?= fa((string) $details['count']) ?></span></h3>
  <button class="theme-toggle" data-drawer-close aria-label="بستن"><?= icon('x', '', 18) ?></button>
</div>

<?php if ($items === []): ?>
  <div class="drawer__empty">
    <?= icon('cart', '', 44) ?>
    <p>سبد خرید شما خالی است</p>
    <a class="btn btn--primary btn--sm" href="<?= url('marketplace') ?>" data-drawer-close>رفتن به بازارگاه</a>
  </div>
<?php else: ?>
  <div class="drawer__items">
    <?php foreach ($items as $item): ?>
      <?php $thumb = !empty($item['image']) ? upload_asset($item['image']) : weave_visual((string) ($item['color_hex'] ?? '#8B2E3A'), (string) $item['sku']); ?>
      <div class="drawer-item">
        <img class="drawer-item__img" src="<?= e($thumb) ?>" alt="<?= e($item['product_name']) ?>">
        <div class="drawer-item__info">
          <b><?= e($item['product_name']) ?></b>
          <span class="text-sm text-muted mono-code" style="font-size:.62rem"><?= e($item['sku']) ?></span>
          <div class="flex-between mt-1">
            <span class="text-sm num"><?= fa((string) $item['qty']) ?> × <?= toman((int) $item['price'], false) ?></span>
            <b class="num text-sm"><?= toman((int) $item['line_total'], false) ?></b>
          </div>
        </div>
        <button class="drawer-item__remove" data-drawer-remove="<?= e($item['sku']) ?>" title="حذف" aria-label="حذف"><?= icon('trash', '', 15) ?></button>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="drawer__foot">
    <div class="drawer-freeship">
      <?php if ($subtotal >= $freeShipOver): ?>
        <span class="badge badge--success"><?= icon('truck', '', 13) ?> ارسال این سفارش رایگان است!</span>
      <?php else: ?>
        <div class="text-sm text-muted mb-1">
          <?= toman_short($freeShipOver - $subtotal) ?> تومان تا <b>ارسال رایگان</b>
        </div>
        <div class="progress"><div class="progress__bar" style="width:<?= $progress ?>%"></div></div>
      <?php endif; ?>
    </div>
    <div class="flex-between mt-3">
      <span class="text-secondary">جمع سبد</span>
      <b class="price text-lg"><?= toman($subtotal) ?></b>
    </div>
    <div class="flex-between text-sm text-muted mb-3">
      <span>هزینهٔ ارسال</span>
      <span class="num"><?= $shipping === 0 ? 'رایگان' : toman($shipping, false) ?></span>
    </div>
    <a class="btn btn--primary btn--block btn--lg" href="<?= url('checkout') ?>"><?= icon('check', '', 18) ?> ادامهٔ فرآیند خرید</a>
    <a class="btn btn--ghost btn--block btn--sm mt-2" href="<?= url('cart') ?>" data-drawer-close>مشاهدهٔ سبد کامل</a>
  </div>
<?php endif; ?>
