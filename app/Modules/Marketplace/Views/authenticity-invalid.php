<?php
/** ویوی کد اصالت نامعتبر — Marketplace::authenticity-invalid */
$this->extend('layouts.public');
?>
<section class="container-narrow" style="padding:var(--space-7) 0">
  <div class="card authenticity-card" style="border-color:var(--color-danger)">
    <div style="display:flex;justify-content:center;color:var(--color-danger);margin-bottom:var(--space-3)"><?= icon('alert', '', 56) ?></div>
    <h1 class="text-danger">کد اصالت یافت نشد</h1>
    <?php if (!empty($code)): ?>
      <p class="mono-code text-muted"><?= e($code) ?></p>
    <?php endif; ?>
    <p class="text-secondary text-sm" style="max-width:44ch;margin-inline:auto">
      این کد در سامانهٔ اصالت فرت ثبت نشده است. ممکن است کد را اشتباه وارد کرده باشید یا
      برچسب محصول متعلق به فرت نباشد. در صورت شک، با دفتر تعاونی تماس بگیرید.
    </p>
    <a class="btn btn--primary mt-3" href="<?= url('authenticity') ?>"><?= icon('refresh', '', 16) ?> تلاش دوباره</a>
  </div>
</section>
