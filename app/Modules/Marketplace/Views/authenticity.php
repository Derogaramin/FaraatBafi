<?php
/**
 * ویوی صفحهٔ اصالت محصول — Marketplace::authenticity
 * داده: detail (بافنده، محصول، بافت)، qrSvg
 */
$this->extend('layouts.public');
$d = $detail;
$weaveLabels = \Core\CodeGenerator::WEAVES;
$knotLabels = \Core\CodeGenerator::KNOTS;
$stitchLabels = \Core\CodeGenerator::STITCHES;
?>
<section class="container-narrow" style="padding:var(--space-6) 0">
  <div class="breadcrumb"><a href="<?= url('/') ?>">خانه</a> <span>/</span> <span>اصالت <?= e($d['code']) ?></span></div>

  <div class="card authenticity-card mb-4" style="background:linear-gradient(160deg, var(--color-surface) 60%, var(--color-success-light));border-color:var(--color-success)">
    <span class="badge badge--success badge--lg badge-dot mb-3"><?= icon('check', '', 14) ?> اصالت این محصول تأیید شده است</span>
    <h1 style="font-size:1.3rem" class="mono-code"><?= e($d['code']) ?></h1>
    <div class="qr-box mt-3"><?= $qrSvg ?></div>
    <p class="text-sm text-muted mt-3 mb-0">QR بالا حاوی آدرس همین صفحه است: <span class="ltr text-sm"><?= e($d['qr_url']) ?></span></p>
  </div>

  <div class="grid grid-2">
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('shuttle', '', 18) ?> بافنده</h3>
      <div class="flex gap-3 mb-3">
        <span class="avatar avatar--lg"><?= e(mb_substr((string) $d['weaver_name'], 0, 1)) ?></span>
        <div>
          <b><?= e($d['weaver_name']) ?></b>
          <div class="text-sm text-muted">بافندهٔ تعاونی — کد <span class="mono-code"><?= e($d['weaver_code']) ?></span></div>
        </div>
      </div>
      <dl class="spec-list">
        <?php if (!empty($d['weaver_city'])): ?><dt>خاستگاه</dt><dd><?= e($d['weaver_city']) ?></dd><?php endif; ?>
        <?php if (!empty($d['specialty'])): ?><dt>تخصص</dt><dd><?= e($d['specialty']) ?></dd><?php endif; ?>
        <dt>امتیاز کیفیت</dt><dd><?= fa((string) round((float) $d['rating'], 1)) ?> از ۵</dd>
        <?php if (!empty($d['woven_at'])): ?><dt>تاریخ بافت</dt><dd><?= jdt($d['woven_at']) ?></dd><?php endif; ?>
        <?php if (!empty($d['wo_code'])): ?><dt>سفارش تولید</dt><dd class="mono-code"><?= e($d['wo_code']) ?></dd><?php endif; ?>
      </dl>
    </div>

    <div class="card">
      <h3 class="card__title mb-3"><?= icon('box', '', 18) ?> محصول</h3>
      <?php if (!empty($d['image'])): ?>
        <img src="<?= e(upload_asset($d['image'])) ?>" alt="<?= e($d['product_name']) ?>" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:var(--radius-btn);margin-bottom:var(--space-3)">
      <?php endif; ?>
      <b><?= e($d['product_name']) ?></b>
      <div class="mt-2"><span class="code-chip"><?= e($d['sku']) ?></span></div>
      <dl class="spec-list mt-3">
        <dt>نوع</dt><dd><?= e($d['type_name']) ?></dd>
        <dt>مدل فنی</dt><dd class="mono-code"><?= e($d['model_code']) ?></dd>
        <dt>پودگذاری</dt><dd><?= e($weaveLabels[$d['weave']] ?? $d['weave']) ?></dd>
        <dt>گره</dt><dd><?= e($knotLabels[$d['knot']] ?? $d['knot']) ?></dd>
        <dt>دوخت</dt><dd><?= e($stitchLabels[$d['stitch']] ?? $d['stitch']) ?></dd>
        <dt>چله</dt><dd><?= fa((string) $d['chelle']) ?></dd>
        <?php if (!empty($d['size_cm'])): ?><dt>ابعاد</dt><dd class="num"><?= e($d['size_cm']) ?></dd><?php endif; ?>
        <dt>رنگ</dt><dd><?= e($d['color_name']) ?></dd>
        <dt>طرح</dt><dd><?= e($d['pattern_name']) ?></dd>
        <dt>وضعیت</dt><dd><?= component('status-pill', ['group' => 'authenticity', 'status' => $d['status']]) ?></dd>
      </dl>
    </div>
  </div>

  <div class="text-center mt-4">
    <a class="btn btn--outline btn--sm" href="<?= url('marketplace/product/' . rawurlencode((string) $d['sku'])) ?>"><?= icon('cart', '', 15) ?> مشاهده در بازارگاه</a>
    <a class="btn btn--ghost btn--sm no-print" href="javascript:print()"><?= icon('print', '', 15) ?> چاپ شناسنامه</a>
  </div>
</section>
