<?php
/** ویوی فهرست وبلاگ — Blog::index */
$this->extend('layouts.public');
?>
<section class="container" style="padding-top:var(--space-5)">
  <div class="breadcrumb"><a href="<?= url('/') ?>">خانه</a> <span>/</span> <span>وبلاگ</span></div>
  <div class="page-header">
    <div>
      <h1>وبلاگ فرت</h1>
      <div class="page-header__sub">داستان بافت، رنگ‌های طبیعی و زندگی کارگاه</div>
    </div>
  </div>

  <form class="filter-bar" method="get" action="<?= url('blog') ?>">
    <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="جست‌وجو در نوشته‌ها…">
    <button class="btn btn--sm btn--outline" type="submit"><?= icon('search', '', 15) ?></button>
  </form>

  <?php if ($posts === []): ?>
    <div class="card"><?= component('empty-state', ['icon' => 'blog', 'title' => 'نوشته‌ای یافت نشد']) ?></div>
  <?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($posts as $p): ?>
      <a class="card card--hover reveal" data-reveal href="<?= url('blog/' . rawurlencode((string) $p['slug'])) ?>" style="text-decoration:none;color:inherit;display:flex;flex-direction:column">
        <div style="aspect-ratio:16/9;border-radius:var(--radius-btn);overflow:hidden;background:var(--color-bg-subtle);margin-bottom:var(--space-3)">
          <img src="<?= e(!empty($p['image']) ? upload_asset($p['image']) : weave_visual(['#8B2E3A', '#1A3B4C', '#4A6B4A'][crc32((string) $p['slug']) % 3], (string) $p['slug'])) ?>" alt="<?= e($p['title']) ?>" style="width:100%;height:100%;object-fit:cover" loading="lazy">
        </div>
        <span class="badge badge--muted mb-2" style="align-self:flex-start"><?= e($p['category']) ?></span>
        <h3 class="card__title mb-2"><?= e($p['title']) ?></h3>
        <p class="text-sm text-secondary" style="flex:1"><?= e(mb_substr((string) ($p['excerpt'] ?? ''), 0, 120)) ?>…</p>
        <div class="flex-between mt-3 text-sm text-muted" style="border-top:1px dashed var(--color-border);padding-top:var(--space-3)">
          <span><?= jdt($p['published_at']) ?></span>
          <span><?= icon('eye', '', 14) ?> <?= fa((string) $p['views']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?= $paginator->links() ?>
  <?php endif; ?>
</section>
