<?php
/** ویوی سبد خرید — Marketplace::cart (داده: details = items/subtotal/count) */
$this->extend('layouts.public');
$items = $details['items'];
$subtotal = $details['subtotal'];
?>
<section class="container" style="padding-top:var(--space-5)">
  <div class="breadcrumb"><a href="<?= url('marketplace') ?>">بازارگاه</a> <span>/</span> <span>سبد خرید</span></div>
  <h1 class="mb-4">سبد خرید</h1>

  <?php if ($items === []): ?>
    <div class="card">
      <?= component('empty-state', [
          'icon' => 'cart',
          'title' => 'سبد خرید شما خالی است',
          'message' => 'از بازارگاه فرت دیدن کنید و دست‌بافتهٔ دلخواه خود را بیافزایید.',
          'action_url' => url('marketplace'),
          'action_label' => 'رفتن به بازارگاه',
      ]) ?>
    </div>
  <?php else: ?>
    <div class="grid" style="grid-template-columns:1.7fr 1fr;gap:var(--space-5);align-items:start" id="cart-grid">
      <div class="stack">
        <?php foreach ($items as $item): ?>
          <div class="card flex gap-4" style="align-items:flex-start">
            <div class="wo-card__thumb" style="width:88px;height:88px">
              <img src="<?= e(!empty($item['image']) ? upload_asset($item['image']) : weave_visual((string) ($item['color_hex'] ?? '#8B2E3A'), (string) $item['sku'])) ?>" alt="<?= e($item['product_name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">
            </div>
            <div style="flex:1">
              <div class="flex-between" style="align-items:flex-start">
                <div>
                  <a href="<?= url('marketplace/product/' . rawurlencode((string) $item['sku'])) ?>" class="fw-bold"><?= e($item['product_name']) ?></a>
                  <div class="text-sm text-muted mt-1"><?= e($item['type_name']) ?><?php if (!empty($item['size_cm'])): ?> • <?= e($item['size_cm']) ?> سانتی‌متر<?php endif; ?></div>
                  <div class="mt-1"><span class="code-chip"><?= e($item['sku']) ?></span></div>
                </div>
                <form method="post" action="<?= url('cart/remove') ?>" class="m-0">
                  <?= csrf_field() ?>
                  <input type="hidden" name="sku" value="<?= e($item['sku']) ?>">
                  <button class="btn btn--ghost btn--sm btn--icon" type="submit" title="حذف" data-confirm="این قلم از سبد حذف شود؟" data-confirm-danger><?= icon('trash', '', 16) ?></button>
                </form>
              </div>
              <div class="flex-between mt-3">
                <form method="post" action="<?= url('cart/update') ?>" class="flex gap-2 m-0">
                  <?= csrf_field() ?>
                  <input type="hidden" name="sku" value="<?= e($item['sku']) ?>">
                  <input class="form-control num" name="qty" type="number" min="1" max="<?= max(1, (int) $item['stock']) ?>" value="<?= fa((string) $item['qty']) ?>" style="width:80px;min-height:38px;text-align:center">
                  <button class="btn btn--outline btn--sm" type="submit"><?= icon('refresh', '', 14) ?></button>
                </form>
                <span class="price"><?= toman((int) $item['line_total']) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="flex-between">
          <a class="btn btn--ghost btn--sm" href="<?= url('marketplace') ?>"><?= icon('arrow-right', '', 15) ?> ادامهٔ خرید</a>
          <form method="post" action="<?= url('cart/clear') ?>" data-confirm="همهٔ اقلام سبد حذف شوند؟" data-confirm-danger>
            <?= csrf_field() ?>
            <button class="btn btn--ghost btn--sm text-danger" type="submit"><?= icon('trash', '', 15) ?> خالی‌کردن سبد</button>
          </form>
        </div>
      </div>

      <!-- خلاصه -->
      <div class="card" style="position:sticky;top:calc(var(--header-h) + 16px)">
        <h3 class="card__title mb-4">خلاصهٔ سفارش</h3>
        <?php $shipping = \App\Modules\Marketplace\Services\CheckoutService::shippingFor($subtotal); ?>
        <dl class="spec-list mb-4">
          <dt>جمع اقلام (<?= fa((string) $details['count']) ?> عدد)</dt><dd class="num"><?= toman($subtotal) ?></dd>
          <dt>هزینهٔ ارسال</dt>
          <dd class="num"><?= $shipping === 0 ? '<span class="badge badge--success">رایگان</span>' : toman($shipping) ?></dd>
          <?php if ($shipping > 0): ?>
            <dt></dt><dd class="text-sm text-muted" style="font-weight:400">ارسال رایگان برای خرید بالای <?= toman(\App\Modules\Marketplace\Services\CheckoutService::FREE_SHIPPING_OVER) ?></dd>
          <?php endif; ?>
        </dl>
        <div class="flex-between mb-4" style="padding-top:var(--space-3);border-top:2px dashed var(--color-border)">
          <b class="text-lg">مبلغ قابل پرداخت</b>
          <b class="price text-lg"><?= toman($subtotal + $shipping) ?></b>
        </div>
        <a class="btn btn--primary btn--block btn--lg" href="<?= url('checkout') ?>"><?= icon('truck', '', 18) ?> ادامهٔ فرآیند خرید</a>
      </div>
    </div>
    <style>@media (max-width:900px){#cart-grid{grid-template-columns:1fr !important}}</style>
  <?php endif; ?>
</section>
