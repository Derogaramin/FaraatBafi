<?php
/** ویوی پرداخت — Marketplace::checkout (داده: details, prefill, shipping, freeShipOver) */
$this->extend('layouts.public');
$items = $details['items'];
$subtotal = $details['subtotal'];
?>
<section class="container" style="padding-top:var(--space-5)">
  <div class="breadcrumb"><a href="<?= url('cart') ?>">سبد خرید</a> <span>/</span> <span>پرداخت</span></div>
  <h1 class="mb-4">اطلاعات ارسال و پرداخت</h1>

  <form method="post" action="<?= url('checkout') ?>" id="checkout-grid-wrap">
    <?= csrf_field() ?>
    <div class="grid" style="grid-template-columns:1.7fr 1fr;gap:var(--space-5);align-items:start" id="checkout-grid">
      <div class="stack">
        <div class="card">
          <h3 class="card__title mb-4"><?= icon('truck', '', 18) ?> نشانی تحویل</h3>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="receiver">نام گیرنده <span class="required">*</span></label>
              <input class="form-control" id="receiver" name="receiver" value="<?= e((string) old('receiver', $prefill['receiver'])) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="phone">شمارهٔ موبایل <span class="required">*</span></label>
              <input class="form-control ltr" id="phone" name="phone" inputmode="tel" value="<?= e((string) old('phone', $prefill['phone'])) ?>" required>
              <div class="form-hint">کد پیگیری سفارش با این شماره قابل استعلام است.</div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="province">استان</label>
              <input class="form-control" id="province" name="province" value="<?= e((string) old('province', $prefill['province'])) ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="city">شهر <span class="required">*</span></label>
              <input class="form-control" id="city" name="city" value="<?= e((string) old('city', $prefill['city'])) ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="address">نشانی کامل <span class="required">*</span></label>
            <textarea class="form-control" id="address" name="address" rows="2" required><?= e((string) old('address', $prefill['address'])) ?></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="postal_code">کد پستی</label>
              <input class="form-control ltr" id="postal_code" name="postal_code" value="<?= e((string) old('postal_code', $prefill['postal_code'])) ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="coupon_code">کد تخفیف</label>
              <input class="form-control ltr" id="coupon_code" name="coupon_code" placeholder="CPN-NOWRUZ-1403" value="<?= e((string) old('coupon_code')) ?>">
            </div>
          </div>
          <div class="form-group m-0">
            <label class="form-label" for="note">توضیح برای تعاونی <span class="text-muted">(اختیاری)</span></label>
            <textarea class="form-control" id="note" name="note" rows="2" placeholder="مثلاً: بسته‌بندی هدیه شود"><?= e((string) old('note')) ?></textarea>
          </div>
        </div>

        <div class="card">
          <h3 class="card__title mb-3"><?= icon('box', '', 18) ?> اقلام سبد (<?= fa((string) $details['count']) ?>)</h3>
          <?php foreach ($items as $item): ?>
            <div class="flex-between" style="padding:var(--space-2) 0;border-bottom:1px dashed var(--color-border)">
              <div>
                <div class="text-sm fw-bold"><?= e($item['product_name']) ?> × <?= fa((string) $item['qty']) ?></div>
                <div class="code-chip" style="font-size:.68rem"><?= e($item['sku']) ?></div>
              </div>
              <span class="num text-sm"><?= toman((int) $item['line_total']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card" style="position:sticky;top:calc(var(--header-h) + 16px)">
        <h3 class="card__title mb-4">مبلغ قابل پرداخت</h3>
        <dl class="spec-list mb-3">
          <dt>جمع اقلام</dt><dd class="num"><?= toman($subtotal) ?></dd>
          <dt>ارسال</dt><dd class="num"><?= $shipping === 0 ? 'رایگان' : toman($shipping) ?></dd>
        </dl>
        <div class="flex-between mb-4" style="padding-top:var(--space-3);border-top:2px dashed var(--color-border)">
          <b>مبلغ نهایی</b><b class="price text-lg"><?= toman($subtotal + $shipping) ?></b>
        </div>
        <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('check', '', 18) ?> ثبت نهایی سفارش</button>
        <p class="text-sm text-muted text-center mt-3 m-0">
          با ثبت سفارش، کد پیگیری <span class="code-chip">ORD-…</span> دریافت می‌کنید.
        </p>
      </div>
    </div>
  </form>
  <style>@media (max-width:900px){#checkout-grid{grid-template-columns:1fr !important}}</style>
</section>
