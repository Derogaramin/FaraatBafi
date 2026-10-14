<?php
/** ویوی پروفایل بافنده — Weaver::profile */
$this->extend('layouts.weaver');
?>
<div class="card text-center mb-4">
  <span class="avatar avatar--lg" style="margin-inline:auto"><?= e(mb_substr((string) $weaver['name'], 0, 1)) ?></span>
  <h1 style="font-size:1.15rem;margin-top:var(--space-3)"><?= e($weaver['name']) ?></h1>
  <div class="text-sm text-muted"><?= e($weaver['skillLabel'] ?? '') ?><?php if ($weaver['specialty']): ?> — <?= e($weaver['specialty']) ?><?php endif; ?></div>
  <div class="mt-2"><span class="code-chip"><?= e($weaver['code']) ?></span></div>
</div>

<div class="grid grid-2 mb-4">
  <div class="card text-center p-3"><div class="text-sm text-muted">کل سفارش‌ها</div><b class="num text-lg"><?= fa((string) ($stats['total_wos'] ?? 0)) ?></b></div>
  <div class="card text-center p-3"><div class="text-sm text-muted">تکمیل‌شده</div><b class="num text-lg text-success"><?= fa((string) ($stats['done_wos'] ?? 0)) ?></b></div>
  <div class="card text-center p-3"><div class="text-sm text-muted">کد اصالت صادرشده</div><b class="num text-lg"><?= fa((string) ($stats['authenticity_count'] ?? 0)) ?></b></div>
  <div class="card text-center p-3"><div class="text-sm text-muted">میانگین امتیاز QC</div><b class="num text-lg"><?= fa((string) ($stats['avg_score'] ?? '—')) ?></b></div>
</div>

<div class="card mb-4">
  <h3 class="card__title mb-3"><?= icon('user', '', 18) ?> اطلاعات</h3>
  <dl class="spec-list">
    <dt>کد بافندگی</dt><dd class="mono-code"><?= e($weaver['code']) ?></dd>
    <dt>موبایل</dt><dd class="num ltr"><?= $weaver['phone'] ? fa($weaver['phone']) : '—' ?></dd>
    <dt>شهر / روستا</dt><dd><?= e(trim(($weaver['city'] ?? '') . ' ' . ($weaver['village'] ?? '')) ?: '—') ?></dd>
    <dt>تعداد دستگاه بافت</dt><dd><?= fa((string) $weaver['loom_count']) ?></dd>
    <dt>نرخ دستمزد هر واحد</dt><dd class="num"><?= toman((int) $weaver['wage_rate']) ?></dd>
    <dt>عضویت از</dt><dd><?= $weaver['joined_at'] ? jdt($weaver['joined_at']) : '—' ?></dd>
  </dl>
</div>

<div class="card">
  <h3 class="card__title mb-3"><?= icon('qr', '', 18) ?> شناسهٔ بافندگی</h3>
  <p class="text-sm text-secondary m-0">
    کد <span class="mono-code"><?= e($weaver['code']) ?></span> شما در همهٔ کدهای اصالت محصولاتی که بافته‌اید
    (بخش سوم کد <span class="code-chip">FB-…-…-<?= e($weaver['code']) ?></span>) درج می‌شود و مشتریان با پویش QR
    نام و خاستگاه شما را می‌بینند.
  </p>
</div>
