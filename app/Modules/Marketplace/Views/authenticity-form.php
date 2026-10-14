<?php
/** ویوی فرم استعلام اصالت — Marketplace::authenticity-form */
$this->extend('layouts.public');
?>
<section class="container-narrow" style="padding:var(--space-7) 0">
  <div class="breadcrumb"><a href="<?= url('/') ?>">خانه</a> <span>/</span> <span>استعلام اصالت</span></div>
  <div class="card text-center">
    <div style="display:flex;justify-content:center;color:var(--color-primary);margin-bottom:var(--space-3)"><?= icon('qr', '', 52) ?></div>
    <h1>استعلام اصالت محصول فرت</h1>
    <p class="text-secondary text-sm mb-4">
      کد اصالت روی برچسب محصول را وارد کنید یا QR آن را با دوربین گوشی بپویشید.<br>
      قالب کد: <span class="code-chip">FB-1403-0521-W023</span>
    </p>
    <form method="post" action="<?= url('authenticity') ?>" style="max-width:440px;margin-inline:auto">
      <?= csrf_field() ?>
      <div class="form-group">
        <input class="form-control ltr text-center" name="code" value="<?= e(old('code')) ?>" placeholder="FB-0000-0000-W000" required autofocus
               style="font-family:var(--font-code);letter-spacing:1px">
      </div>
      <button class="btn btn--primary btn--block btn--lg" type="submit"><?= icon('search', '', 18) ?> استعلام اصالت</button>
    </form>
  </div>
</section>
