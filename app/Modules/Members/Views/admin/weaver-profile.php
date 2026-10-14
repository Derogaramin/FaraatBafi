<?php
/** ویوی پروفایل بافنده — Members::admin.weaver-profile */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div class="flex gap-4">
    <span class="avatar avatar--lg"><?= e(mb_substr((string) $weaver['name'], 0, 1)) ?></span>
    <div>
      <div class="breadcrumb"><a href="<?= url('admin/weavers') ?>">بافندگان</a> <span>/</span> <span><?= e($weaver['code']) ?></span></div>
      <h1><?= e($weaver['name']) ?> <span class="code-chip"><?= e($weaver['code']) ?></span></h1>
      <div class="page-header__sub">
        <?= e(\Core\Statuses::SKILL[$weaver['skill_level']] ?? '') ?>
        <?php if ($weaver['specialty']): ?>— <?= e($weaver['specialty']) ?><?php endif; ?>
        <?php if ($weaver['city']): ?>— <?= e($weaver['city']) ?><?php if ($weaver['village']): ?> / <?= e($weaver['village']) ?><?php endif; ?><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="flex gap-2">
    <?= $weaver['status'] === 'active' ? '<span class="badge badge--success badge--lg badge-dot">فعال</span>' : '<span class="badge badge--muted badge--lg badge-dot">غیرفعال</span>' ?>
    <a class="btn btn--sm btn--outline" href="<?= url('admin/weavers/' . $weaver['id'] . '/edit') ?>"><?= icon('edit', '', 14) ?> ویرایش</a>
    <form method="post" action="<?= url('admin/weavers/' . $weaver['id'] . '/status') ?>"
          data-confirm="وضعیت فعالیت این بافنده تغییر کند؟" <?= $weaver['status'] === 'active' ? 'data-confirm-danger' : '' ?>>
      <?= csrf_field() ?>
      <button class="btn btn--sm <?= $weaver['status'] === 'active' ? 'btn--danger' : 'btn--success' ?>" type="submit">
        <?= $weaver['status'] === 'active' ? 'غیرفعال‌سازی' : 'فعال‌سازی' ?>
      </button>
    </form>
  </div>
</div>

<div class="kpi-grid mb-4">
  <?= component('kpi-card', ['label' => 'کل سفارش‌های بافت', 'value' => fa((string) ($stats['total_wos'] ?? 0)), 'icon' => 'loom', 'color' => 'action']) ?>
  <?= component('kpi-card', ['label' => 'تکمیل‌شده', 'value' => fa((string) ($stats['done_wos'] ?? 0)), 'icon' => 'check', 'color' => 'success']) ?>
  <?= component('kpi-card', ['label' => 'در جریان', 'value' => fa((string) ($stats['active_wos'] ?? 0)), 'icon' => 'clock', 'color' => 'warning']) ?>
  <?= component('kpi-card', ['label' => 'ماندهٔ قابل پرداخت', 'value' => toman((int) ($stats['pending_total'] ?? 0)), 'icon' => 'coin', 'color' => 'primary', 'hint' => 'پرداخت‌شده: ' . toman_short((int) ($stats['paid_total'] ?? 0)) . ' تومان']) ?>
</div>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('shuttle', '', 18) ?> سفارش‌های تولید</h3>
    <?php if ($workOrders === []): ?>
      <p class="text-sm text-muted">سفارشی ثبت نشده است.</p>
    <?php else: ?>
      <table class="table" style="min-width:0">
        <thead><tr><th>کد</th><th>محصول</th><th>تعداد</th><th>وضعیت</th><th>موعد</th></tr></thead>
        <tbody>
          <?php foreach ($workOrders as $wo): ?>
          <tr>
            <td><a class="mono-code" href="<?= url('admin/production/' . $wo['id']) ?>"><?= e($wo['code']) ?></a></td>
            <td class="text-sm"><?= e($wo['product_name']) ?></td>
            <td class="num"><?= fa((string) $wo['quantity']) ?></td>
            <td><?= component('status-pill', ['group' => 'work_order', 'status' => $wo['status']]) ?></td>
            <td class="text-sm"><?= $wo['due_date'] ? jdt($wo['due_date']) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="stack">
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('user', '', 18) ?> اطلاعات</h3>
      <dl class="spec-list">
        <dt>موبایل</dt><dd class="num ltr"><?= $weaver['phone'] ? fa($weaver['phone']) : '—' ?></dd>
        <dt>کد ملی</dt><dd class="num ltr"><?= $weaver['national_id'] ? fa($weaver['national_id']) : '—' ?></dd>
        <dt>دستگاه بافت</dt><dd><?= fa((string) $weaver['loom_count']) ?></dd>
        <dt>نرخ دستمزد</dt><dd class="num"><?= toman((int) $weaver['wage_rate']) ?></dd>
        <dt>امتیاز کیفیت</dt><dd><?= fa((string) $weaver['rating']) ?> از ۵ <?php if (!empty($stats['avg_score'])): ?>(میانگین بازرسی‌ها: <?= fa((string) $stats['avg_score']) ?>)<?php endif; ?></dd>
        <dt>کدهای اصالت صادرشده</dt><dd><?= fa((string) $authenticityCount) ?> کد FB</dd>
        <dt>عضویت از</dt><dd><?= $weaver['joined_at'] ? jdt($weaver['joined_at']) : '—' ?></dd>
      </dl>
      <?php if (!empty($weaver['note'])): ?><p class="text-sm text-secondary mt-3"><?= nl2br(e($weaver['note'])) ?></p><?php endif; ?>
    </div>

    <div class="card">
      <h3 class="card__title mb-3"><?= icon('coin', '', 18) ?> آخرین فیش‌های دستمزد</h3>
      <?php if ($payments === []): ?>
        <p class="text-sm text-muted m-0">فیشی صادر نشده است.</p>
      <?php else: ?>
        <?php foreach (array_slice($payments, 0, 6) as $p): ?>
          <div class="flex-between text-sm" style="padding:6px 0;border-bottom:1px dashed var(--color-border)">
            <span class="code-chip" style="font-size:.66rem"><?= e($p['code']) ?></span>
            <span class="num"><?= toman((int) $p['amount'], false) ?></span>
            <?= component('status-pill', ['group' => 'payment', 'status' => $p['status']]) ?>
          </div>
        <?php endforeach; ?>
        <a class="btn btn--sm btn--outline mt-3" href="<?= url('admin/finance/payments?weaver_id=' . (int) $weaver['id']) ?>">همهٔ فیش‌ها</a>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3 class="card__title mb-3"><?= icon('thread', '', 18) ?> آخرین مواد دریافتی</h3>
      <?php if ($materials === []): ?>
        <p class="text-sm text-muted m-0">موادی دریافت نکرده است.</p>
      <?php else: ?>
        <?php foreach (array_slice($materials, 0, 6) as $m): ?>
          <div class="flex-between text-sm" style="padding:6px 0;border-bottom:1px dashed var(--color-border)">
            <span><?= e($m['material_name']) ?></span>
            <span class="num"><?= fa(rtrim(rtrim(number_format((float) $m['quantity'], 2), '0'), '.')) ?> <?= e($m['unit']) ?></span>
            <span class="text-muted"><?= jdt($m['distributed_at']) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
