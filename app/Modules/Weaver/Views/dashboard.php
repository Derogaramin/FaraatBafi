<?php
/** ویوی داشبورد بافنده — Weaver::dashboard (موبایل‌محور) */
$this->extend('layouts.weaver');
?>
<div class="weaver-hello">
  <h2>خدا قوت، <?= e($weaver['name']) ?> 🌿</h2>
  <p><?= e($today) ?> — کد بافندگی شما: <span class="mono-code" style="color:#F5E7C8"><?= e($weaver['code']) ?></span></p>
  <div class="weaver-hello__stats">
    <div class="weaver-hello__stat"><b><?= fa((string) count($active)) ?></b><span>سفارش فعال</span></div>
    <div class="weaver-hello__stat"><b><?= fa((string) ($stats['delivered_this_week'] ?? 0)) ?></b><span>تحویل این هفته</span></div>
    <div class="weaver-hello__stat"><b><?= fa((string) ($stats['done_total'] ?? 0)) ?></b><span>کل تکمیل‌شده</span></div>
  </div>
</div>

<div class="weaver-stats">
  <div class="card text-center p-3">
    <div class="text-sm text-muted">دستمزد در انتظار</div>
    <b class="num text-primary-brand"><?= toman_short((int) ($stats['pending_wage'] ?? 0)) ?></b>
  </div>
  <div class="card text-center p-3">
    <div class="text-sm text-muted">پرداختی ۳۰ روز</div>
    <b class="num text-success"><?= toman_short((int) ($stats['paid_30d'] ?? 0)) ?></b>
  </div>
  <div class="card text-center p-3">
    <div class="text-sm text-muted">امتیاز کیفیت</div>
    <b class="num"><?= fa((string) $weaver['rating']) ?> ⭐</b>
  </div>
</div>

<h2 class="mb-3" style="font-size:1.05rem"><?= icon('shuttle', '', 18) ?> سفارش‌های فعال بافت</h2>

<?php if ($active === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'loom', 'title' => 'سفارش فعالی ندارید', 'message' => 'به‌زودی سفارش جدیدی به شما تخصیص می‌یابد و اعلان آن را اینجا می‌بینید.']) ?></div>
<?php else: ?>
<div class="wo-list">
  <?php foreach ($active as $wo): ?>
    <?php
    $woColor = match ($wo['status']) {
        'weaving' => 'var(--color-warning)',
        'materials_received' => 'var(--color-secondary)',
        'delivered' => 'var(--color-success)',
        default => 'var(--color-action)',
    };
    $steps = \Core\Statuses::WORK_ORDER_STEPS;
    $idx = array_search($wo['status'], $steps, true);
    ?>
    <a class="card card--hover wo-card reveal" data-reveal style="--wo-color:<?= $woColor ?>;text-decoration:none;color:inherit" href="<?= url('weaver/orders/' . $wo['id']) ?>">
      <div class="wo-card__head">
        <div style="flex:1;min-width:0">
          <b><?= e($wo['product_name']) ?></b>
          <div class="text-sm text-muted"><?= fa((string) $wo['quantity']) ?> عدد • <span class="mono-code"><?= e($wo['code']) ?></span></div>
        </div>
        <?php
        // حلقهٔ پیشرفت
        $pct = \App\Modules\Production\Models\WorkOrder::hydrate($wo)->progressPercent();
        $C = 2 * M_PI * 24;
        $offset = $C * (1 - $pct / 100);
        ?>
        <div class="ring" style="--ring-color:<?= $woColor ?>" role="img" aria-label="پیشرفت <?= fa((string) $pct) ?> درصد">
          <svg viewBox="0 0 56 56">
            <circle class="ring__track" cx="28" cy="28" r="24" fill="none" stroke-width="5"></circle>
            <circle class="ring__bar" cx="28" cy="28" r="24" fill="none" stroke-width="5"
                    stroke-dasharray="<?= number_format($C, 1) ?>" stroke-dashoffset="<?= number_format($offset, 1) ?>"></circle>
          </svg>
          <span class="ring__label"><?= fa((string) $pct) ?>٪</span>
        </div>
      </div>
      <div class="wo-card__product">
        <div class="wo-card__thumb">
          <?php if (!empty($wo['image'])): ?><img src="<?= e(upload_asset($wo['image'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit"><?php endif; ?>
        </div>
        <div style="flex:1">
          <div class="text-sm"><?= e($wo['color_name'] ?? '') ?></div>
          <div class="code-chip" style="font-size:.62rem"><?= e($wo['sku']) ?></div>
        </div>
        <?php if ($wo['due_date']): ?>
          <span class="countdown" data-countdown="<?= (string) (int) ceil((strtotime($wo['due_date'] . ' 23:59:59') - time()) / 86400) ?>">
            <?= icon('clock', '', 13) ?> <?= \Core\Jalali::countdown($wo['due_date']) ?>
          </span>
        <?php endif; ?>
      </div>
      <div class="mini-steps">
        <?php foreach ($steps as $i => $s): ?>
          <span class="<?= $idx !== false && $i < $idx ? 'done' : ($i === $idx ? 'current' : '') ?>"></span>
        <?php endforeach; ?>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="mt-5">
  <a class="btn btn--outline btn--block" href="<?= url('weaver/orders') ?>"><?= icon('shuttle', '', 16) ?> همهٔ سفارش‌ها</a>
</div>
