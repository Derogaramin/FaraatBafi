<?php
/** ویوی تماس با ما — Home::contact */
$this->extend('layouts.public');
?>
<section class="container-narrow" style="padding:var(--space-7) 0">
  <div class="breadcrumb"><a href="<?= url('/') ?>">خانه</a> <span>/</span> <span>تماس با ما</span></div>
  <div class="grid grid-2">
    <div class="card">
      <h2>ارتباط با تعاونی</h2>
      <ul style="list-style:none;padding:0">
        <li class="flex gap-2 mb-3"><?= icon('map-pin', '', 18) ?> <span>خراسان رضوی، تربت جام — کارگاه مرکزی نقشینه بافت</span></li>
        <li class="flex gap-2 mb-3"><?= icon('phone', '', 18) ?> <span class="num ltr">051-34567890</span></li>
        <li class="flex gap-2 mb-3"><?= icon('clock', '', 18) ?> <span>شنبه تا چهارشنبه ۸ الی ۱۷ — پنجشنبه ۸ الی ۱۳</span></li>
        <li class="flex gap-2 mb-3"><?= icon('store', '', 18) ?> <span>فروش حضوری: ۵ نمایندگی فعال در استان</span></li>
      </ul>
      <div class="card mt-3" style="background:var(--color-primary-light);border:none">
        <p class="text-sm m-0"><b>پیگیری سفارش:</b> با کد <span class="code-chip">ORD-…</span> و شمارهٔ موبایل از <a href="<?= url('orders/track') ?>">صفحهٔ پیگیری</a> وضعیت سفارش را ببینید.</p>
      </div>
    </div>

    <div class="card">
      <h2>پیام خود را بگذارید</h2>
      <form method="post" action="<?= url('contact') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label" for="name">نام و نام خانوادگی <span class="required">*</span></label>
          <input class="form-control <?= isset($session_errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e(old('name')) ?>" required>
          <?php if (isset($session_errors['name'])): ?><div class="form-error"><?= e($session_errors['name']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="phone">شمارهٔ موبایل <span class="required">*</span></label>
          <input class="form-control ltr <?= isset($session_errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" inputmode="tel" placeholder="09xxxxxxxxx" value="<?= e(old('phone')) ?>" required>
          <?php if (isset($session_errors['phone'])): ?><div class="form-error"><?= e($session_errors['phone']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="message">پیام <span class="required">*</span></label>
          <textarea class="form-control <?= isset($session_errors['message']) ? 'is-invalid' : '' ?>" id="message" name="message" rows="5" required><?= e(old('message')) ?></textarea>
          <?php if (isset($session_errors['message'])): ?><div class="form-error"><?= e($session_errors['message']) ?></div><?php endif; ?>
        </div>
        <button class="btn btn--primary btn--block" type="submit"><?= icon('letter', '', 18) ?> ارسال پیام</button>
      </form>
    </div>
  </div>
</section>
