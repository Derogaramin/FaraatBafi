<?php
/** ویوی تک‌نوشتهٔ وبلاگ — Blog::show (ستون متن باریک) */
$this->extend('layouts.public');
?>
<article class="container-narrow" style="padding:var(--space-6) 0">
  <div class="breadcrumb">
    <a href="<?= url('/') ?>">خانه</a> <span>/</span>
    <a href="<?= url('blog') ?>">وبلاگ</a> <span>/</span>
    <span><?= e($post['title']) ?></span>
  </div>

  <header class="mb-4">
    <span class="badge badge--muted"><?= e($post['category']) ?></span>
    <h1 style="font-size:1.6rem;margin:var(--space-3) 0"><?= e($post['title']) ?></h1>
    <div class="text-sm text-muted flex gap-4">
      <span><?= icon('user', '', 14) ?> <?= e($author) ?></span>
      <span><?= icon('clock', '', 14) ?> <?= jdt($post['published_at']) ?></span>
      <span><?= icon('eye', '', 14) ?> <?= fa((string) $post['views']) ?> بازدید</span>
    </div>
  </header>

  <img src="<?= e(!empty($post['image']) ? upload_asset($post['image']) : weave_visual('#8B2E3A', (string) $post['slug'])) ?>" alt="<?= e($post['title']) ?>"
       style="width:100%;aspect-ratio:16/8;object-fit:cover;border-radius:var(--radius-lg);margin-bottom:var(--space-5)">

  <div class="card" style="line-height:2.3;font-size:1.02rem">
    <?= nl2br(e($post['body'])) ?>
  </div>

  <?php if (!empty($post['tags'])): ?>
    <div class="chip-row mt-4">
      <?php foreach (array_filter(array_map('trim', explode(',', (string) $post['tags']))) as $tag): ?>
        <a class="chip" href="<?= url('blog?q=' . rawurlencode($tag)) ?>">#<?= e($tag) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($related !== []): ?>
  <div class="section-title mt-5"><h2 style="font-size:1.1rem">نوشته‌های مرتبط</h2></div>
  <div class="grid grid-3">
    <?php foreach ($related as $r): ?>
      <a class="card card--hover p-3" href="<?= url('blog/' . rawurlencode((string) $r['slug'])) ?>" style="text-decoration:none;color:inherit">
        <b class="text-sm"><?= e($r['title']) ?></b>
        <div class="text-sm text-muted mt-1"><?= jdt($r['published_at']) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</article>
