<?php
/**
 * ویوی جزئیات سفارش تولید — Production::admin.show
 * داده: wo, variant, weaver, distributions, materials, inspections, defects, authenticity, payments, weavers, order, transitions
 */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$canManage = \Core\Auth::can('production.manage');
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/production') ?>">تولید</a> <span>/</span> <span><?= e($wo['code']) ?></span></div>
    <h1>سفارش تولید <span class="mono-code"><?= e($wo['code']) ?></span></h1>
    <div class="page-header__sub">
      <?= fa((string) $wo['quantity']) ?> عدد <?= e($variant['model_name'] ?? '') ?> —
      <?php if ($order): ?>سفارش مشتری <a href="<?= url('admin/orders/' . $order['id']) ?>" class="mono-code"><?= e($order['code']) ?></a><?php else: ?>تولید برای انبار<?php endif; ?>
    </div>
  </div>
  <?= component('status-pill', ['group' => 'work_order', 'status' => $wo['status'], 'size' => 'lg']) ?>
</div>

<div class="card mb-4">
  <?= component('progress-steps', ['current' => $wo['status'], 'rejected' => $wo['status'] === 'qc_rejected']) ?>
</div>

<div class="grid" style="grid-template-columns:1.5fr 1fr;gap:var(--space-5);align-items:start" id="wo-grid">
  <div class="stack">
    <!-- محصول و بافنده -->
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('box', '', 18) ?> محصول و بافنده</h3>
      <div class="flex gap-4" style="align-items:flex-start;flex-wrap:wrap">
        <div class="wo-card__thumb" style="width:96px;height:96px">
          <?php if (!empty($variant['image'])): ?><img src="<?= e(upload_asset($variant['image'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit"><?php endif; ?>
        </div>
        <dl class="spec-list" style="flex:1;min-width:240px">
          <dt>SKU</dt><dd><span class="code-chip"><?= e($variant['sku'] ?? '') ?></span></dd>
          <dt>مدل فنی</dt><dd class="mono-code"><?= e($variant['model_code'] ?? '') ?></dd>
          <dt>رنگ / طرح</dt><dd><?= e($variant['color_name'] ?? '') ?> / <?= e($variant['pattern_name'] ?? '') ?></dd>
          <dt>تعداد</dt><dd><?= fa((string) $wo['quantity']) ?></dd>
          <dt>دستمزد واحد</dt><dd class="num"><?= toman((int) $wo['unit_wage']) ?></dd>
          <dt>کل دستمزد</dt><dd class="num fw-bold text-primary-brand"><?= toman((int) $wo['quantity'] * (int) $wo['unit_wage']) ?></dd>
          <dt>اولویت</dt><dd><?= component('status-pill', ['group' => 'priority', 'status' => $wo['priority']]) ?></dd>
          <dt>موعد</dt><dd><?= $wo['due_date'] ? jdt($wo['due_date']) . ' — ' . \Core\Jalali::countdown($wo['due_date']) : '—' ?></dd>
        </dl>
        <div style="min-width:220px">
          <?php if ($weaver): ?>
            <a class="flex gap-3" href="<?= url('admin/weavers/' . $weaver['id']) ?>" style="color:inherit">
              <span class="avatar avatar--lg"><?= e(mb_substr((string) $weaver['name'], 0, 1)) ?></span>
              <span>
                <b><?= e($weaver['name']) ?></b>
                <span class="code-chip" style="display:block;margin-top:4px"><?= e($weaver['code']) ?></span>
                <span class="text-sm text-muted"><?= e(\Core\Statuses::SKILL[$weaver['skill_level']] ?? '') ?> — امتیاز <?= fa((string) $weaver['rating']) ?></span>
              </span>
            </a>
          <?php else: ?>
            <span class="badge badge--warning badge--lg">بافنده تخصیص نیافته</span>
          <?php endif; ?>

          <?php if ($canManage && in_array($wo['status'], ['draft', 'assigned'], true)): ?>
          <form method="post" action="<?= url('admin/production/' . $wo['id'] . '/assign') ?>" class="stack mt-4">
            <?= csrf_field() ?>
            <label class="form-label m-0"><?= $weaver ? 'تغییر بافنده' : 'تخصیص بافنده' ?></label>
            <select class="form-control" name="weaver_id" required>
              <option value="">انتخاب بافنده…</option>
              <?php foreach ($weavers as $w): ?>
                <option value="<?= $w->id() ?>" <?= $weaver && (int) $weaver['id'] === (int) $w->id() ? 'selected' : '' ?>>
                  <?= e($w->code) ?> — <?= e($w->name) ?> (<?= e($w->skillLabel()) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn--secondary btn--sm" type="submit"><?= icon('handshake', '', 14) ?> تخصیص و اعلان به بافنده</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- تغییر وضعیت -->
    <?php if ($canManage && $transitions !== []): ?>
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('refresh', '', 18) ?> تغییر وضعیت (مدیر)</h3>
      <form method="post" action="<?= url('admin/production/' . $wo['id'] . '/status') ?>" class="flex gap-2" style="flex-wrap:wrap" id="status-form">
        <?= csrf_field() ?>
        <input type="hidden" name="status" data-fill value="">
        <?php foreach ($transitions as $t): ?>
          <button class="btn btn--sm <?= $t === 'cancelled' ? 'btn--danger' : 'btn--outline' ?>" type="button"
                  data-confirm-action="وضعیت سفارش به «<?= e(\Core\Statuses::label('work_order', $t)) ?>» تغییر کند؟"
                  data-target="#status-form" data-value="<?= e($t) ?>">
            <?= e(\Core\Statuses::label('work_order', $t)) ?>
          </button>
        <?php endforeach; ?>
      </form>
    </div>
    <?php endif; ?>

    <!-- بازرسی کیفیت -->
    <?php if ($canManage && $wo['status'] === 'delivered'): ?>
    <div class="card" style="border-color:var(--color-primary)">
      <h3 class="card__title mb-3"><?= icon('quality', '', 18) ?> ثبت بازرسی کیفیت (QC)</h3>
      <form method="post" action="<?= url('admin/production/' . $wo['id'] . '/qc') ?>" class="stack">
        <?= csrf_field() ?>
        <div class="form-row-3">
          <div class="form-group m-0">
            <label class="form-label">نتیجه <span class="required">*</span></label>
            <select class="form-control" name="result" required>
              <option value="passed">قبول</option>
              <option value="conditional">قبول مشروط</option>
              <option value="rejected">رد</option>
            </select>
          </div>
          <div class="form-group m-0">
            <label class="form-label">امتیاز (۰–۱۰۰)</label>
            <input class="form-control num" name="score" type="number" min="0" max="100" placeholder="85">
          </div>
          <div class="form-group m-0">
            <label class="form-label">نوع عیب</label>
            <select class="form-control" name="defect_type_id">
              <option value="">—</option>
              <?php foreach ($defects as $d): ?>
                <option value="<?= $d->id() ?>"><?= e($d->code) ?> — <?= e($d->name_fa) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group m-0">
          <label class="form-label">یادداشت بازرس</label>
          <textarea class="form-control" name="note" rows="2" placeholder="تراکم پود یکنواخت؛ دوخت لبه استاندارد…"></textarea>
        </div>
        <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ثبت بازرسی — کد QC خودکار</button>
      </form>
    </div>
    <?php endif; ?>

    <!-- توزیع مواد -->
    <?php if ($canManage && in_array($wo['status'], ['assigned', 'materials_received', 'weaving'], true)): ?>
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('thread', '', 18) ?> توزیع مادهٔ اولیه</h3>
      <form method="post" action="<?= url('admin/production/' . $wo['id'] . '/materials') ?>" class="flex gap-2" style="flex-wrap:wrap">
        <?= csrf_field() ?>
        <select class="form-control" name="material_id" required style="width:auto;min-width:200px">
          <option value="">انتخاب ماده…</option>
          <?php foreach ($materials as $m): ?>
            <option value="<?= $m->id() ?>"><?= e($m->name_fa) ?> (<?= e($m->code) ?>) — موجودی: <?= fa(rtrim(rtrim(number_format((float) $m->stock, 1), '0'), '.')) ?> <?= e($m->unitLabel()) ?></option>
          <?php endforeach; ?>
        </select>
        <input class="form-control num" name="quantity" type="number" step="0.001" min="0.001" placeholder="مقدار" required style="width:120px">
        <input class="form-control" name="note" placeholder="یادداشت" style="width:auto;flex:1;min-width:140px">
        <button class="btn btn--secondary btn--sm" type="submit"><?= icon('download', '', 14) ?> توزیع و کسر از انبار</button>
      </form>
    </div>
    <?php endif; ?>

    <!-- جدول مواد توزیع‌شده -->
    <div class="card">
      <h3 class="card__title mb-3">مواد توزیع‌شده</h3>
      <?php if ($distributions === []): ?>
        <p class="text-sm text-muted m-0">هنوز ماده‌ای برای این سفارش ارسال نشده است.</p>
      <?php else: ?>
        <table class="table" style="min-width:0">
          <thead><tr><th>ماده</th><th>مقدار</th><th>تاریخ</th><th>تحویل‌دهنده</th></tr></thead>
          <tbody>
            <?php foreach ($distributions as $d): ?>
            <tr>
              <td><?= e($d['material_name']) ?> <span class="code-chip" style="font-size:.64rem"><?= e($d['material_code']) ?></span></td>
              <td class="num"><?= fa(rtrim(rtrim(number_format((float) $d['quantity'], 2), '0'), '.')) ?> <?= e($d['unit']) ?></td>
              <td class="text-sm"><?= jdt($d['distributed_at'], 'Y/m/d', true) ?></td>
              <td class="text-sm"><?= e($d['by_name'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- بازرسی‌های ثبت‌شده -->
    <?php if ($inspections !== []): ?>
    <div class="card">
      <h3 class="card__title mb-3">بازرسی‌های کیفیت</h3>
      <?php foreach ($inspections as $qc): ?>
        <div class="flex-between" style="padding:var(--space-2) 0;border-bottom:1px dashed var(--color-border);flex-wrap:wrap;gap:6px">
          <span class="code-chip"><?= e($qc['code']) ?></span>
          <?= component('status-pill', ['group' => 'qc', 'status' => $qc['result']]) ?>
          <?php if ($qc['score'] !== null): ?><span class="badge badge--action">امتیاز <?= fa((string) $qc['score']) ?></span><?php endif; ?>
          <?php if ($qc['defect_name']): ?><span class="text-sm text-danger"><?= icon('alert', '', 13) ?> <?= e($qc['defect_name']) ?></span><?php endif; ?>
          <span class="text-sm text-muted"><?= jdt($qc['inspected_at'], 'Y/m/d', true) ?> — <?= e($qc['inspector_name'] ?? '') ?></span>
        </div>
        <?php if (!empty($qc['note'])): ?><p class="text-sm text-secondary"><?= e($qc['note']) ?></p><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ستون کنار -->
  <div class="stack">
    <!-- تایم‌لاین -->
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('clock', '', 18) ?> رویدادها</h3>
      <div class="timeline">
        <?php
        $events = array_filter([
            ['created_at', 'ایجاد سفارش تولید'],
            ['assigned_at', 'تخصیص به بافنده'],
            ['materials_at', 'دریافت مواد اولیه'],
            ['started_at', 'شروع بافت'],
            ['delivered_at', 'تحویل به انبار'],
            ['completed_at', 'تکمیل و تسویه'],
        ], fn ($e) => !empty($wo[$e[0]]));
        foreach ($events as $i => [$field, $label]): ?>
          <div class="timeline__item <?= $i === count($events) - 1 ? 'is-active' : 'is-success' ?>">
            <div class="timeline__title"><?= $label ?></div>
            <div class="timeline__meta"><?= jdt($wo[$field], 'Y/m/d', true) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- کدهای اصالت -->
    <div class="card">
      <div class="card__header">
        <h3 class="card__title"><?= icon('qr', '', 18) ?> کدهای اصالت (FB)</h3>
        <?php if ($canManage && in_array($wo['status'], ['qc_passed', 'completed'], true) && $authenticity === []): ?>
          <form method="post" action="<?= url('admin/production/' . $wo['id'] . '/authenticity') ?>" class="m-0">
            <?= csrf_field() ?>
            <button class="btn btn--sm btn--secondary" type="submit"><?= icon('plus', '', 13) ?> صدور کدها</button>
          </form>
        <?php endif; ?>
      </div>
      <?php if ($authenticity === []): ?>
        <p class="text-sm text-muted m-0">
          <?= in_array($wo['status'], ['qc_passed', 'completed'], true) ? 'کدها هنوز صادر نشده‌اند.' : 'پس از تأیید کیفیت، کدهای FB به‌صورت خودکار صادر می‌شوند.' ?>
        </p>
      <?php else: ?>
        <div class="chip-row">
          <?php foreach ($authenticity as $a): ?>
            <a class="chip" href="<?= url('authenticity/' . rawurlencode((string) $a['code'])) ?>" target="_blank" title="مشاهدهٔ صفحهٔ اصالت"><?= icon('qr', '', 12) ?> <?= e($a['code']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- فیش دستمزد -->
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('coin', '', 18) ?> دستمزد</h3>
      <?php if ($payments === []): ?>
        <p class="text-sm text-muted m-0">پس از تأیید کیفیت، فیش دستمزد (PAY) خودکار صادر می‌شود.</p>
      <?php else: ?>
        <?php foreach ($payments as $p): ?>
          <div class="flex-between" style="padding:var(--space-2) 0">
            <span class="code-chip"><?= e($p['code']) ?></span>
            <span class="num fw-bold"><?= toman((int) $p['amount'], false) ?></span>
            <?= component('status-pill', ['group' => 'payment', 'status' => $p['status']]) ?>
          </div>
        <?php endforeach; ?>
        <a class="btn btn--sm btn--outline mt-3" href="<?= url('admin/finance/payments?weaver_id=' . (int) ($wo['weaver_id'] ?? 0)) ?>">مدیریت فیش‌ها</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<style>@media (max-width:992px){#wo-grid{grid-template-columns:1fr !important}}</style>
