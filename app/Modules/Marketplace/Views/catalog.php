<?php
/** ویوی کاتالوگ بازارگاه — Marketplace::catalog */
$this->extend('layouts.public');
?>
<section class="container" style="padding-top:var(--space-5)">
  <div class="breadcrumb"><a href="<?= url('/') ?>">خانه</a> <span>/</span> <span>بازارگاه</span></div>
  <div class="page-header">
    <div>
      <h1>بازارگاه فرت</h1>
      <div class="page-header__sub">دست‌بافته‌های اصیل با کد شناسنامهٔ QR</div>
    </div>
  </div>

  <!-- چیپ‌های نوع محصول -->
  <div class="type-chips">
    <a class="type-chip <?= $filters['type'] === '' ? 'active' : '' ?>" href="<?= url('marketplace') ?>">همهٔ محصولات</a>
    <?php foreach ($types as $t): ?>
      <a class="type-chip <?= $filters['type'] === $t['code'] ? 'active' : '' ?>"
         href="<?= url('marketplace?type=' . rawurlencode((string) $t['code'])) ?>">
        <?= e($t['name_fa']) ?>
        <span class="count">(<?= e((string) ($typeCounts[$t['code']] ?? 0)) ?>)</span>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="results-count">
    <b><?= fa((string) $paginator->total) ?></b> محصول یافت شد
    <?php if ($filters['q'] !== ''): ?>— برای جست‌وجوی «<?= e($filters['q']) ?>»<?php endif; ?>
  </div>

  <!-- فیلترها -->
  <form class="filter-bar" method="get" action="<?= url('marketplace') ?>">
    <input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= icon('search', '', 14) ?> جست‌وجوی نام، SKU، طرح…" style="flex:2">
    <select class="form-control" name="type" data-auto-submit>
      <option value="">همهٔ انواع</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= e($t['code']) ?>" <?= $filters['type'] === $t['code'] ? 'selected' : '' ?>><?= e($t['name_fa']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-control" name="color" data-auto-submit>
      <option value="">همهٔ رنگ‌ها</option>
      <?php foreach ($colors as $c): ?>
        <option value="<?= e($c['code']) ?>" <?= $filters['color'] === $c['code'] ? 'selected' : '' ?>><?= e($c['name_fa']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-control" name="sort" data-auto-submit>
      <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>جدیدترین</option>
      <option value="cheap" <?= $filters['sort'] === 'cheap' ? 'selected' : '' ?>>ارزان‌ترین</option>
      <option value="expensive" <?= $filters['sort'] === 'expensive' ? 'selected' : '' ?>>گران‌ترین</option>
      <option value="popular" <?= $filters['sort'] === 'popular' ? 'selected' : '' ?>>محبوب‌ترین</option>
    </select>
    <label class="checkbox"><input type="checkbox" name="stock" value="1" <?= $filters['inStock'] ? 'checked' : '' ?> data-auto-submit> فقط موجود</label>
    <button class="btn btn--sm btn--outline" type="submit"><?= icon('search', '', 15) ?> اعمال</button>
    <a class="btn btn--sm btn--ghost" href="<?= url('marketplace') ?>">پاک‌کردن</a>
  </form>

  <?php if ($products === []): ?>
    <?= component('empty-state', [
        'icon' => 'search',
        'title' => 'محصولی با این فیلترها یافت نشد',
        'message' => 'فیلترها را تغییر دهید یا عبارت جست‌وجوی دیگری امتحان کنید.',
    ]) ?>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <?= component('product-card', ['product' => $product]) ?>
      <?php endforeach; ?>
    </div>
    <?= $paginator->links() ?>
  <?php endif; ?>
</section>
