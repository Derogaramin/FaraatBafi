<?php
/**
 * ویوی جزئیات سفارش بافنده — Weaver::order-detail
 * دکمه‌های بزرگ انگشتی با مودال تأیید برای تغییر وضعیت بافت
 */
$this->extend('layouts.weaver');
$steps = \Core\Statuses::WORK_ORDER_STEPS;
$actionLabels = [
    'receive_materials' => ['دریافت مواد اولیه', 'materials', 'مواد اولیه را از انبار تعاونی تحویل گرفته‌اید؟'],
    'start_weaving'     => ['شروع بافت', 'weaving', 'بافت این سفارش را روی دستگاه شروع می‌کنید؟'],
    'deliver'           => ['تحویل به انبار', 'deliver', 'بافت تکمیل و آمادهٔ تحویل به انبار تعاونی است؟'],
];
?>
<div class="breadcrumb mb-3"><a href="<?= url('weaver/orders') ?>">سفارش‌ها</a> <span>/</span> <span><?= e($wo['code']) ?></span></div>

<div class="card wo-card mb-4" style="--wo-color:var(--color-primary)">
  <div class="wo-card__head">
    <div>
      <h1 style="font-size:1.1rem;margin-bottom:4px"><?= e($variant['model_name'] ?? '') ?></h1>
      <div class="text-sm text-muted"><?= fa((string) $wo['quantity']) ?> عدد • <span class="mono-code"><?= e($wo['code']) ?></span></div>
    </div>
    <?= component('status-pill', ['group' => 'work_order', 'status' => $wo['status'], 'size' => 'lg']) ?>
  </div>

  <div class="wo-card__product">
    <div class="wo-card__thumb" style="width:88px;height:88px">
      <?php if (!empty($variant['image'])): ?>
        <img src="<?= e(upload_asset($variant['image'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">
      <?php endif; ?>
    </div>
    <div>
      <div class="text-sm"><?= e($variant['color_name'] ?? '') ?> — طرح <?= e($variant['pattern_name'] ?? '') ?></div>
      <div class="code-chip mt-1" style="font-size:.66rem"><?= e($variant['sku'] ?? '') ?></div>
      <?php if (!empty($variant['size_cm'])): ?><div class="text-sm text-muted mt-1">ابعاد: <?= e($variant['size_cm']) ?></div><?php endif; ?>
    </div>
  </div>

  <?= component('progress-steps', ['current' => $wo['status'], 'rejected' => $wo['status'] === 'qc_rejected']) ?>

  <?php if ($wo['due_date']): ?>
    <div class="text-center mb-3">
      <span class="countdown badge--lg" data-countdown="<?= (string) (int) ceil((strtotime($wo['due_date'] . ' 23:59:59') - time()) / 86400) ?>">
        <?= icon('clock', '', 14) ?> موعد تحویل: <?= jdt($wo['due_date']) ?> — <?= \Core\Jalali::countdown($wo['due_date']) ?>
      </span>
    </div>
  <?php endif; ?>

  <?php if ($wo['status'] === 'qc_rejected'): ?>
    <div class="card mb-3" style="background:var(--color-danger-light);border:none">
      <b class="text-danger"><?= icon('alert', '', 16) ?> نتیجهٔ بازرسی کیفیت: رد</b>
      <p class="text-sm m-0 mt-1">لطفاً ایرادها را برطرف کرده و دوباره «شروع بافت» را ثبت کنید.</p>
    </div>
  <?php endif; ?>

  <?php
  // اگر مواد هنوز توزیع نشده، «دریافت مواد» غیرفعال + دلیل نمایشی (نقد §۸٫۸)
  $materialsPending = $wo['status'] === 'assigned' && $materials === [];
  ?>
  <?php if ($nextActions !== []): ?>
  <div class="big-actions">
    <?php foreach ($nextActions as $action => $to): ?>
      <?php [$label, $style, $confirm] = $actionLabels[$action] ?? [$action, 'deliver', '']; ?>
      <?php if ($action === 'receive_materials' && $materialsPending): ?>
        <button class="big-action big-action--materials" disabled style="opacity:.5;cursor:not-allowed">
          <?= icon('thread', '', 22) ?> دریافت مواد اولیه
        </button>
      <?php else: ?>
      <form method="post" action="<?= url('weaver/orders/' . $wo['id'] . '/status') ?>" class="m-0"
            data-confirm="<?= e($confirm) ?>" data-confirm-title="<?= e($label) ?>" data-confirm-yes="بله، <?= e($label) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= e($action) ?>">
        <button class="big-action big-action--<?= e($style) ?>" type="submit">
          <?= icon(match ($action) { 'receive_materials' => 'thread', 'start_weaving' => 'loom', default => 'package' }, '', 22) ?>
          <?= e($label) ?>
        </button>
      </form>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php if ($materialsPending): ?>
    <p class="text-sm text-muted text-center mt-2 m-0">
      <?= icon('info', '', 14) ?> انبار هنوز مواد این سفارش را ارسال نکرده است. پس از ارسال، همین دکمه فعال می‌شود.
    </p>
  <?php endif; ?>
  <?php else: ?>
    <p class="text-sm text-muted text-center m-0">در این مرحله اقدامی از سمت شما لازم نیست.</p>
  <?php endif; ?>
</div>

<div class="stack">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('thread', '', 18) ?> مواد دریافتی این سفارش</h3>
    <?php if ($materials === []): ?>
      <p class="text-sm text-muted m-0">هنوز موادی ارسال نشده است. پس از ارسال توسط انبار، دکمهٔ «دریافت مواد» فعال می‌شود.</p>
    <?php else: ?>
      <?php foreach ($materials as $m): ?>
        <div class="flex-between text-sm" style="padding:8px 0;border-bottom:1px dashed var(--color-border)">
          <span><?= e($m['material_name']) ?> <span class="code-chip" style="font-size:.6rem"><?= e($m['material_code']) ?></span></span>
          <span class="num fw-bold"><?= fa(rtrim(rtrim(number_format((float) $m['quantity'], 2), '0'), '.')) ?> <?= e($m['unit']) ?></span>
          <span class="text-muted"><?= jdt($m['distributed_at']) ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card__title mb-3"><?= icon('coin', '', 18) ?> دستمزد این سفارش</h3>
    <div class="flex-between mb-2">
      <span class="text-sm">نرخ هر واحد</span>
      <b class="num"><?= toman((int) $wo['unit_wage']) ?></b>
    </div>
    <div class="flex-between mb-3" style="padding-top:var(--space-2);border-top:1px dashed var(--color-border)">
      <span class="text-sm">جمع دستمزد (<?= fa((string) $wo['quantity']) ?> واحد)</span>
      <b class="num text-primary-brand text-lg"><?= toman((int) $wo['quantity'] * (int) $wo['unit_wage']) ?></b>
    </div>
    <?php if ($payments === []): ?>
      <p class="text-sm text-muted m-0">پس از تأیید کیفیت، فیش دستمزد صادر می‌شود.</p>
    <?php else: foreach ($payments as $p): ?>
      <div class="flex-between text-sm" style="padding:8px 0;border-bottom:1px dashed var(--color-border)">
        <span class="code-chip" style="font-size:.64rem"><?= e($p['code']) ?></span>
        <span class="num"><?= toman((int) $p['amount'], false) ?></span>
        <?= component('status-pill', ['group' => 'payment', 'status' => $p['status']]) ?>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <?php if ($inspections !== []): ?>
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('quality', '', 18) ?> نتیجهٔ بازرسی</h3>
    <?php foreach ($inspections as $qc): ?>
      <div class="flex-between text-sm" style="padding:8px 0;border-bottom:1px dashed var(--color-border);flex-wrap:wrap;gap:6px">
        <span class="code-chip" style="font-size:.62rem"><?= e($qc['code']) ?></span>
        <?= component('status-pill', ['group' => 'qc', 'status' => $qc['result']]) ?>
        <?php if ($qc['score'] !== null): ?><span class="badge badge--action">امتیاز <?= fa((string) $qc['score']) ?></span><?php endif; ?>
        <?php if (!empty($qc['defect_name'])): ?><span class="text-danger text-sm"><?= e($qc['defect_name']) ?></span><?php endif; ?>
      </div>
      <?php if (!empty($qc['note'])): ?><p class="text-sm text-secondary"><?= e($qc['note']) ?></p><?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
