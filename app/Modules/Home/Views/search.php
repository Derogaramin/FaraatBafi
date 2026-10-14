<?php
/** ویوی نتایج جست‌وجو — Home::search */
$this->extend('layouts.public');
$empty = $q === '' || (!empty($results) && array_sum(array_map('count', $results)) === 0);
?>
<section class="container-narrow" style="padding-top:var(--space-6)">
  <div class="breadcrumb"><a href="<?= url('/') ?>">خانه</a> <span>/</span> <span>جست‌وجو</span></div>
  <h1 class="mb-3">جست‌وجوی جهانی</h1>

  <form method="get" action="<?= url('search') ?>" class="filter-bar mb-4">
    <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="کد FUCS (مثل FB-1405-… یا ORD-…) یا متن دلخواه…" autofocus style="flex:3">
    <button class="btn btn--primary btn--sm" type="submit"><?= icon('search', '', 15) ?> جست‌وجو</button>
  </form>
  <p class="form-hint mb-4">کدهای اصالت، سفارش، SKU، بافنده، نمایندگی، مشتری، WO و تیکت مستقیماً به صفحهٔ خود می‌پرند.</p>

  <?php if ($q !== '' && $empty): ?>
    <?= component('empty-state', ['icon' => 'search', 'title' => 'نتیجه‌ای یافت نشد', 'message' => 'عبارت «' . e($q) . '» در هیچ‌یک از موجودیت‌ها پیدا نشد.']) ?>
  <?php endif; ?>

  <?php if (!empty($results['products'])): ?>
  <h2 class="mt-4" style="font-size:1.05rem"><?= icon('box', '', 18) ?> محصولات</h2>
  <div class="product-grid mt-3">
    <?php foreach ($results['products'] as $p): ?>
      <?= component('product-card', ['product' => $p + ['name_fa' => $p['name_fa']]]) ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($results['posts'])): ?>
  <h2 class="mt-5" style="font-size:1.05rem"><?= icon('blog', '', 18) ?> وبلاگ</h2>
  <div class="stack mt-3">
    <?php foreach ($results['posts'] as $post): ?>
      <a class="card card--hover" href="<?= url('blog/' . rawurlencode((string) $post['slug'])) ?>" style="text-decoration:none;color:inherit">
        <b><?= e($post['title']) ?></b>
        <p class="text-sm text-secondary m-0 mt-1"><?= e(mb_substr((string) $post['excerpt'], 0, 120)) ?>…</p>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (\Core\Auth::isAdmin()): ?>
    <?php if (!empty($results['orders'])): ?>
    <h2 class="mt-5" style="font-size:1.05rem"><?= icon('cart', '', 18) ?> سفارش‌ها</h2>
    <div class="table-wrap mt-3">
      <table class="table">
        <thead><tr><th>کد</th><th>گیرنده</th><th>وضعیت</th><th>مبلغ</th><th>تاریخ</th></tr></thead>
        <tbody>
          <?php foreach ($results['orders'] as $o): ?>
          <tr>
            <td><a class="mono-code" href="<?= url('admin/orders/' . $o['id']) ?>"><?= e($o['code']) ?></a></td>
            <td><?= e($o['receiver']) ?></td>
            <td><?= component('status-pill', ['group' => 'order', 'status' => $o['status']]) ?></td>
            <td class="num"><?= toman((int) $o['total'], false) ?></td>
            <td class="text-sm"><?= jdt($o['placed_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['work_orders'])): ?>
    <h2 class="mt-5" style="font-size:1.05rem"><?= icon('loom', '', 18) ?> سفارش‌های تولید</h2>
    <div class="chip-row mt-3">
      <?php foreach ($results['work_orders'] as $wo): ?>
        <a class="chip" href="<?= url('admin/production/' . $wo['id']) ?>"><?= e($wo['code']) ?> — <?= e(\Core\Statuses::label('work_order', $wo['status'])) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['weavers'])): ?>
    <h2 class="mt-5" style="font-size:1.05rem"><?= icon('shuttle', '', 18) ?> بافندگان</h2>
    <div class="chip-row mt-3">
      <?php foreach ($results['weavers'] as $w): ?>
        <a class="chip" href="<?= url('admin/weavers/' . $w['id']) ?>"><?= e($w['code']) ?> — <?= e($w['name']) ?><?= $w['city'] ? ' (' . e($w['city']) . ')' : '' ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['agencies'])): ?>
    <h2 class="mt-5" style="font-size:1.05rem"><?= icon('store', '', 18) ?> نمایندگی‌ها</h2>
    <div class="chip-row mt-3">
      <?php foreach ($results['agencies'] as $a): ?>
        <a class="chip" href="<?= url('admin/agencies/' . $a['id']) ?>"><?= e($a['code']) ?> — <?= e($a['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</section>
