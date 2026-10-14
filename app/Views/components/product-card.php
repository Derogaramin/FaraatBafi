<?php
/**
 * کامپوننت کارت محصول — بازارگاه
 * داده: ['product' => row شامل sku, price, name_fa, image, stock, color_name, hex, pattern_name, type_name]
 * اگر تصویر آپلودشده نداشته باشد، «بافت رنگی» اختصاصی همان محصول نمایش داده می‌شود.
 */
$p = $product;
$inStock = (int) ($p['stock'] ?? 0) > 0;
$hex = (string) ($p['hex'] ?? $p['color_hex'] ?? '#8B2E3A');
$sku = (string) ($p['sku'] ?? '');
$visual = !empty($p['image'])
    ? upload_asset($p['image'])
    : weave_visual($hex, $sku);
$stockBadge = !$inStock
    ? '<span class="badge badge--danger">ناموجود</span>'
    : ((int) $p['stock'] <= 2 ? '<span class="badge badge--warning">آخرین موجودی</span>' : '');
?>
<article class="card card--hover product-card reveal" data-reveal>
  <a class="product-card__img" href="<?= url('marketplace/product/' . rawurlencode($sku)) ?>" aria-label="<?= e($p['name_fa'] ?? $p['product_name'] ?? '') ?>">
    <img src="<?= e($visual) ?>" alt="<?= e($p['name_fa'] ?? $p['product_name'] ?? 'محصول فرت') ?>" loading="lazy">
    <div class="product-card__badges">
      <?= $stockBadge ?>
      <?php if (!empty($p['is_featured'])): ?>
        <span class="badge badge--secondary">ویژه</span>
      <?php endif; ?>
    </div>
  </a>
  <div class="product-card__body">
    <h3 class="product-card__title">
      <a href="<?= url('marketplace/product/' . rawurlencode($sku)) ?>"><?= e($p['name_fa'] ?? $p['product_name'] ?? '') ?></a>
    </h3>
    <div class="product-card__meta">
      <?php if (!empty($p['color_name'])): ?>
        <span class="flex gap-1">
          <span class="color-dot" style="background:<?= e($hex) ?>"></span>
          <?= e($p['color_name']) ?>
        </span>
      <?php endif; ?>
      <?php if (!empty($p['pattern_name'])): ?><span>طرح <?= e($p['pattern_name']) ?></span><?php endif; ?>
    </div>
    <div class="product-card__meta"><span class="mono-code" style="font-size:.68rem"><?= e($sku) ?></span></div>
    <div class="product-card__footer">
      <span class="price"><?= toman((int) ($p['price'] ?? 0)) ?></span>
      <?php if ($inStock): ?>
        <button class="btn btn--sm" data-add-to-cart="<?= url('cart/add') ?>" data-sku="<?= e($sku) ?>">
          <?= icon('cart', '', 15) ?> افزودن
        </button>
      <?php else: ?>
        <a class="btn btn--sm btn--outline" href="<?= url('marketplace/product/' . rawurlencode($sku)) ?>">جزئیات</a>
      <?php endif; ?>
    </div>
  </div>
</article>
