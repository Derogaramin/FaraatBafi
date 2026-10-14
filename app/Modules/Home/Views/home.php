<?php
/**
 * ویوی صفحهٔ فرود — Home::home
 * داده: featured, latestPosts, stats
 */
$this->extend('layouts.public');
?>

<!-- ═══════ هیرو ═══════ -->
<section class="hero">
  <div class="container hero__inner">
    <div>
      <span class="hero__eyebrow"><?= icon('shuttle', '', 16) ?> تعاونی تولیدی صنایع دستی نقشینه بافت تربت جام</span>
      <h1>هر تار و پودِ <em>فرت</em>،<br>روایتِ دستان یک استادکار است</h1>
      <p>
        حوله، دستمال، شال و پارچه‌های دست‌باف با رنگ‌های طبیعی و نقش‌مایه‌های اصیل خراسان.
        هر محصول دارای <b>کد اصالت یکتا (FB)</b> و شناسنامهٔ QR است که نام بافنده و مشخصات فنی بافت را روایت می‌کند.
      </p>
      <div class="hero__actions">
        <a class="btn btn--primary btn--lg" href="<?= url('marketplace') ?>"><?= icon('cart', '', 18) ?> ورود به بازارگاه</a>
        <a class="btn btn--outline btn--lg" href="<?= url('authenticity') ?>"><?= icon('qr', '', 18) ?> استعلام اصالت محصول</a>
      </div>
      <div class="hero__stats">
        <div class="hero__stat"><b><?= fa((string) ($stats['weavers'] ?? 0)) ?></b><span>بافندهٔ فعال</span></div>
        <div class="hero__stat"><b><?= fa((string) ($stats['products'] ?? 0)) ?></b><span>محصول دست‌باف</span></div>
        <div class="hero__stat"><b><?= fa((string) ($stats['agencies'] ?? 0)) ?></b><span>نمایندگی فروش</span></div>
        <div class="hero__stat"><b><?= fa((string) ($stats['customers'] ?? 0)) ?>+</b><span>مشتری همیشگی</span></div>
      </div>
    </div>
    <?php
    // کولاژ تصویری هیرو: سه محصول دارای تصویر (یا بافت رنگی اختصاصی)
    $heroItems = array_slice(array_values(array_filter($featured, fn ($f) => !empty($f['image']))), 0, 3);
    while (count($heroItems) < 3) {
        $heroItems[] = $featured[count($heroItems) % max(1, count($featured))] ?? null;
    }
    $heroSample = $featured[0] ?? null;
    ?>
    <div class="hero__collage" aria-hidden="true">
      <?php $i = 0; foreach ($heroItems as $hi): if (!$hi) { continue; } $i++;
          $src = !empty($hi['image']) ? upload_asset($hi['image']) : weave_visual((string) ($hi['hex'] ?? '#8B2E3A'), (string) ($hi['sku'] ?? ''));
      ?>
        <div class="tile tile--<?= $i ?>"><img src="<?= e($src) ?>" alt="" loading="eager"></div>
      <?php endforeach; ?>
      <div class="hero__qr-badge">
        <svg class="qr-mini" viewBox="0 0 24 24" fill="none" stroke="#8B2E3A" stroke-width="1.6">
          <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z"/><path d="M14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" fill="#8B2E3A" stroke="none"/>
        </svg>
        <span>هر محصول، شناسنامهٔ QR دارد<br><span class="text-muted" style="font-weight:400">کد اصالت FB</span></span>
      </div>
    </div>
  </div>
</section>

<!-- ═══════ محصولات ویژه ═══════ -->
<section class="container" style="padding-top:var(--space-7)">
  <div class="section-title">
    <h2>دست‌بافته‌های ویژه</h2>
    <a class="text-sm fw-bold" href="<?= url('marketplace') ?>">مشاهدهٔ همه <?= icon('arrow-left', '', 14) ?></a>
  </div>

  <?php if ($featured === []): ?>
    <?= component('empty-state', ['icon' => 'box', 'title' => 'هنوز محصولی ثبت نشده است', 'message' => 'به‌زودی دست‌بافته‌های تازه روی چله می‌نشینند.']) ?>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($featured as $product): ?>
        <?= component('product-card', ['product' => $product]) ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- ═══════ اصالت ═══════ -->
<section class="container" style="padding-top:var(--space-7)">
  <div class="grid grid-2" style="align-items:center">
    <div class="card card--pattern">
      <h2 class="text-primary-brand"><?= icon('qr', '', 22) ?> شناسنامهٔ دیجیتال هر بافت</h2>
      <p class="text-secondary">
        روی هر محصول فرت یک برچسب QR خواهید دید. با پویش آن به صفحهٔ اصالت محصول می‌رسید؛
        جایی که <b>نام بافنده</b>، <b>تاریخ بافت</b>، <b>مشخصات فنی</b> (چله، پودگذاری، گره، دوخت) و
        <b>کد یکتای FB</b> درج شده است. کد اصالت با الگوی
        <span class="code-chip">FB-۱۴۰۳-۰۵۲۱-W۰۲۳</span>
        ساخته می‌شود؛ سه بخش آن سال شمسی، سری ساخت و کد بافنده است.
      </p>
      <a class="btn btn--secondary" href="<?= url('authenticity') ?>"><?= icon('search', '', 16) ?> استعلام کد اصالت</a>
    </div>
    <div class="card">
      <div class="card__header"><h3 class="card__title">چطور کار می‌کند؟</h3></div>
      <div class="timeline">
        <div class="timeline__item is-success">
          <div class="timeline__title">۱. سفارش شما ثبت می‌شود <span class="code-chip">ORD-…</span></div>
          <div class="timeline__meta">از بازارگاه، نمایندگی یا تلفنی</div>
        </div>
        <div class="timeline__item is-success">
          <div class="timeline__title">۲. سفارش بافت به استادکار سپرده می‌شود <span class="code-chip">WO-…</span></div>
          <div class="timeline__meta">مواد اولیه (پنبه/نخ رنگ‌شده) تحویل بافنده می‌شود</div>
        </div>
        <div class="timeline__item is-success">
          <div class="timeline__title">۳. بازرسی کیفیت <span class="code-chip">QC-…</span></div>
          <div class="timeline__meta">چله، تراکم پود، گره و دوخت بررسی و امتیاز داده می‌شود</div>
        </div>
        <div class="timeline__item is-active">
          <div class="timeline__title">۴. صدور کد اصالت و ارسال <span class="code-chip">FB-…</span></div>
          <div class="timeline__meta">QR شناسنامه روی محصول نصب و سفارش ارسال می‌شود</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════ وبلاگ ═══════ -->
<?php if (!empty($latestPosts)): ?>
<section class="container" style="padding-top:var(--space-7)">
  <div class="section-title">
    <h2>از وبلاگ فرت</h2>
    <a class="text-sm fw-bold" href="<?= url('blog') ?>">همهٔ نوشته‌ها <?= icon('arrow-left', '', 14) ?></a>
  </div>
  <div class="grid grid-3">
    <?php foreach ($latestPosts as $post): ?>
      <a class="card card--hover reveal" data-reveal href="<?= url('blog/' . rawurlencode((string) $post['slug'])) ?>" style="text-decoration:none;color:inherit">
        <img src="<?= e(!empty($post['image']) ? upload_asset($post['image']) : weave_visual('#1A3B4C', (string) $post['slug'])) ?>" alt="<?= e($post['title']) ?>" style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radius-btn);margin-bottom:var(--space-3)">
        <h3 class="card__title mb-2"><?= e($post['title']) ?></h3>
        <p class="text-sm text-secondary" style="margin:0"><?= e(mb_substr((string) $post['excerpt'], 0, 110)) ?>…</p>
        <div class="text-sm text-muted mt-3"><?= jdt($post['published_at']) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="container" style="padding-top:var(--space-7)">
  <div class="card text-center" style="background:linear-gradient(135deg,var(--color-action),#12293a);color:#F3EBDD;border:none">
    <h2 style="color:#fff">بافندهٔ فرت هستید؟</h2>
    <p style="opacity:.85;max-width:52ch;margin-inline:auto">
      با کد بافندگی (W###) و شمارهٔ موبایل خود وارد پنل شوید؛ سفارش‌های بافت، مواد دریافتی و دستمزدها همیشه همراه شماست.
    </p>
    <a class="btn btn--secondary btn--lg mt-3" href="<?= url('login') ?>"><?= icon('shuttle', '', 18) ?> ورود به پنل بافنده</a>
  </div>
</section>
