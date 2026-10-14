<?php
/** ویوی جزئیات محصول — Marketplace::product (داده: $product آرایهٔ detailed) */
$this->extend('layouts.public');
$p = $product;
$inStock = (int) $p['stock'] > 0;
$gallerySrc = !empty($p['image']) ? upload_asset($p['image']) : weave_visual((string) $p['color_hex'], (string) $p['sku']);
$siblingVisual = fn (array $s) => !empty($s['image']) ? upload_asset($s['image']) : weave_visual((string) $s['hex'], (string) $s['sku']);
?>
<section class="container" style="padding-top:var(--space-5)">
  <div class="breadcrumb">
    <a href="<?= url('/') ?>">خانه</a> <span>/</span>
    <a href="<?= url('marketplace') ?>">بازارگاه</a> <span>/</span>
    <span><?= e($p['model_name']) ?></span>
  </div>

  <div class="grid" style="grid-template-columns:1.1fr 1fr;gap:var(--space-6)" id="product-top">
    <!-- گالری -->
    <div>
      <div class="card" style="padding:0;overflow:hidden">
        <div style="aspect-ratio:4/3;background:var(--color-bg-subtle) var(--pattern-hero) center/160px">
          <img src="<?= e($gallerySrc) ?>" alt="<?= e($p['model_name']) ?>" style="width:100%;height:100%;object-fit:cover">
        </div>
      </div>
      <?php if (!empty($siblings)): ?>
        <div class="mt-3">
          <div class="text-sm text-muted mb-2">سایر رنگ/طرح‌های همین مدل:</div>
          <div class="flex gap-2" style="flex-wrap:wrap">
            <?php foreach ($siblings as $s): ?>
              <a href="<?= url('marketplace/product/' . rawurlencode((string) $s['sku'])) ?>" title="<?= e($s['sku']) ?> — <?= e($s['color_name']) ?>"
                 style="width:64px;height:64px;border-radius:var(--radius-btn);overflow:hidden;border:2px solid var(--color-border);display:block;transition:border-color .15s">
                <img src="<?= e($siblingVisual($s)) ?>" alt="<?= e($s['color_name']) ?>" style="width:100%;height:100%;object-fit:cover">
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- اطلاعات -->
    <div class="stack">
      <div>
        <div class="flex gap-2 mb-2">
          <span class="badge badge--primary"><?= e($p['type_name']) ?></span>
          <?php if ($inStock): ?>
            <span class="badge badge--success badge-dot">موجود (<?= fa((string) $p['stock']) ?> عدد)</span>
          <?php else: ?>
            <span class="badge badge--danger badge-dot">ناموجود — قابل پیش‌سفارش</span>
          <?php endif; ?>
        </div>
        <h1 style="margin-bottom:var(--space-2)"><?= e($p['model_name']) ?></h1>
        <div class="text-sm text-muted">
          رنگ <b><?= e($p['color_name']) ?></b> • طرح <b><?= e($p['pattern_name']) ?></b>
        </div>
        <div class="mt-2"><span class="code-chip" style="font-size:.9rem"><?= e($p['sku']) ?></span></div>
      </div>

      <div class="flex gap-4" style="align-items:flex-end">
        <span class="price" style="font-size:1.7rem"><?= toman((int) $p['price']) ?></span>
      </div>

      <form method="post" action="<?= url('cart/add') ?>" class="flex gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="sku" value="<?= e($p['sku']) ?>">
        <input class="form-control num" data-qty name="qty" type="number" min="1" max="<?= max(1, (int) $p['stock']) ?>" value="1" style="width:90px;text-align:center">
        <?php if ($inStock): ?>
          <button class="btn btn--primary" type="submit" style="flex:1"><?= icon('cart', '', 18) ?> افزودن به سبد خرید</button>
        <?php else: ?>
          <button class="btn btn--outline" type="submit" style="flex:1" disabled>ناموجود</button>
        <?php endif; ?>
      </form>

      <!-- مشخصات فنی (FUCS) -->
      <div class="card">
        <div class="card__header"><h3 class="card__title"><?= icon('loom', '', 18) ?> مشخصات فنی بافت</h3></div>
        <dl class="spec-list">
          <dt>کد مدل فنی</dt><dd class="mono-code"><?= e($p['model_code']) ?></dd>
          <dt>پودگذاری</dt><dd><?= e($weaveLabels[$p['weave']] ?? $p['weave']) ?> (<?= e($p['weave']) ?>)</dd>
          <dt>گره</dt><dd><?= e($knotLabels[$p['knot']] ?? $p['knot']) ?></dd>
          <dt>دوخت</dt><dd><?= e($stitchLabels[$p['stitch']] ?? $p['stitch']) ?></dd>
          <dt>چله</dt><dd><?= fa((string) $p['chelle']) ?></dd>
          <?php if (!empty($p['size_cm'])): ?><dt>ابعاد</dt><dd class="num"><?= e($p['size_cm']) ?> سانتی‌متر</dd><?php endif; ?>
          <?php if (!empty($p['weight_gr'])): ?><dt>وزن</dt><dd><?= fa((string) $p['weight_gr']) ?> گرم</dd><?php endif; ?>
          <dt>کد رنگ</dt><dd><span class="color-dot" style="background:<?= e($p['color_hex']) ?>"></span> <?= e($p['color_code']) ?> — <?= e($p['color_name']) ?></dd>
          <dt>کد طرح</dt><dd class="mono-code"><?= e($p['pattern_code']) ?> — <?= e($p['pattern_name']) ?></dd>
        </dl>
        <?php if (!empty($p['model_description'])): ?>
          <p class="text-sm text-secondary mt-3" style="margin-bottom:0"><?= nl2br(e($p['model_description'])) ?></p>
        <?php endif; ?>
      </div>

      <!-- اصالت -->
      <div class="card" style="background:var(--color-secondary-light);border-color:transparent">
        <h3 class="card__title"><?= icon('qr', '', 18) ?> شناسنامهٔ دیجیتال</h3>
        <p class="text-sm m-0">
          این محصول با کد اصالت یکتای <span class="code-chip">FB-…</span> و برچسب QR عرضه می‌شود.
          با پویش QR، نام بافنده، تاریخ بافت و نتیجهٔ بازرسی کیفیت را ببینید.
          <?php if ($sampleAuth): ?>
            <br>نمونهٔ صادرشده برای این محصول: <a href="<?= url('authenticity/' . rawurlencode((string) $sampleAuth['code'])) ?>" class="mono-code"><?= e($sampleAuth['code']) ?></a>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>
</section>

<!-- نوار خرید چسبان موبایل -->
<div class="sticky-buy">
  <div style="flex:1;min-width:0">
    <div class="price"><?= toman((int) $p['price']) ?></div>
    <div class="text-sm text-muted" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($p['model_name']) ?></div>
  </div>
  <?php if ($inStock): ?>
    <button class="btn btn--primary" data-add-to-cart="<?= url('cart/add') ?>" data-sku="<?= e($p['sku']) ?>" data-no-drawer="1" style="flex-shrink:0">
      <?= icon('cart', '', 17) ?> افزودن به سبد
    </button>
  <?php else: ?>
    <button class="btn btn--outline" disabled style="flex-shrink:0">ناموجود</button>
  <?php endif; ?>
</div>

<style>@media (max-width:900px){#product-top{grid-template-columns:1fr !important}}</style>
<script>document.body.classList.add('has-sticky-buy');</script>
