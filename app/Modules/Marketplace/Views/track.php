<?php
/** ویوی فرم پیگیری سفارش — Marketplace::track */
$this->extend('layouts.public');
?>
<section class="container-narrow" style="padding:var(--space-7) 0">
  <div class="breadcrumb"><a href="<?= url('/') ?>">خانه</a> <span>/</span> <span>پیگیری سفارش</span></div>
  <div class="card">
    <h1 class="mb-2"><?= icon('truck', '', 24) ?> پیگیری سفارش</h1>
    <p class="text-secondary text-sm mb-4">کد سفارش (مثل <span class="code-chip">ORD-1403-000125</span>) و شمارهٔ موبایلی که هنگام خرید وارد کردید را بنویسید.</p>
    <form method="post" action="<?= url('orders/track') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label" for="code">کد سفارش <span class="required">*</span></label>
        <input class="form-control ltr" id="code" name="code" value="<?= e(old('code')) ?>" placeholder="ORD-1403-000125" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label" for="phone">شمارهٔ موبایل <span class="required">*</span></label>
        <input class="form-control ltr" id="phone" name="phone" inputmode="tel" placeholder="09xxxxxxxxx" required>
      </div>
      <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('search', '', 18) ?> مشاهدهٔ وضعیت</button>
    </form>
  </div>
</section>
