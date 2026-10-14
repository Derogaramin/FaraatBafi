<?php
/** ویوی دستمزد بافندگان — Finance::admin.payments */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$canManage = \Core\Auth::can('payroll.manage');
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>دستمزدها</span></div>
    <h1>فیش‌های دستمزد بافندگان</h1>
    <div class="page-header__sub">کدگذاری <span class="code-chip">PAY-W045-1403-02</span></div>
  </div>
</div>

<div class="kpi-grid mb-4">
  <?= component('kpi-card', ['label' => 'پرداخت‌شده (کل)', 'value' => toman((int) ($summary['paid_total'] ?? 0)), 'icon' => 'check', 'color' => 'success']) ?>
  <?= component('kpi-card', ['label' => 'در انتظار پرداخت', 'value' => toman((int) ($summary['pending_total'] ?? 0)), 'icon' => 'clock', 'color' => 'warning']) ?>
  <?= component('kpi-card', ['label' => 'تعداد فیش‌ها', 'value' => fa((string) $paginator->total), 'icon' => 'coin', 'color' => 'action']) ?>
</div>

<div class="grid" style="grid-template-columns:1fr 2.6fr;gap:var(--space-5);align-items:start" id="pay-grid">
  <?php if ($canManage): ?>
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> صدور فیش دستی</h3>
    <form method="post" action="<?= url('admin/finance/payments') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0">
        <label class="form-label">بافنده <span class="required">*</span></label>
        <select class="form-control" name="weaver_id" required>
          <option value="">انتخاب…</option>
          <?php foreach ($weavers as $w): ?>
            <option value="<?= $w->id() ?>" <?= (int) ($filters['weaver_id'] ?? 0) === (int) $w->id() ? 'selected' : '' ?>><?= e($w->code) ?> — <?= e($w->name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group m-0">
        <label class="form-label">مبلغ (تومان) <span class="required">*</span></label>
        <input class="form-control num" name="amount" type="number" min="1000" step="1000" required>
      </div>
      <div class="form-group m-0">
        <label class="form-label">دوره (شمسی)</label>
        <input class="form-control ltr" name="period" placeholder="<?= e(\Core\Jalali::format('Y/m')) ?>">
      </div>
      <div class="form-group m-0">
        <label class="form-label">یادداشت</label>
        <input class="form-control" name="note" placeholder="پیش‌پرداخت / تسویهٔ دوره">
      </div>
      <button class="btn btn--primary" type="submit"><?= icon('coin', '', 15) ?> صدور فیش PAY</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="stack">
    <form class="filter-bar" method="get" action="<?= url('admin/finance/payments') ?>">
      <select class="form-control" name="status" data-auto-submit>
        <option value="">همهٔ وضعیت‌ها</option>
        <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>در انتظار پرداخت</option>
        <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>پرداخت‌شده</option>
      </select>
      <select class="form-control" name="weaver_id" data-auto-submit>
        <option value="">همهٔ بافندگان</option>
        <?php foreach ($weavers as $w): ?>
          <option value="<?= $w->id() ?>" <?= (int) $filters['weaver_id'] === (int) $w->id() ? 'selected' : '' ?>><?= e($w->code) ?> — <?= e($w->name) ?></option>
        <?php endforeach; ?>
      </select>
    </form>

    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>کد فیش</th><th>بافنده</th><th>سفارش تولید</th><th>دوره</th><th>مبلغ</th><th>وضعیت</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
          <tr>
            <td><span class="code-chip"><?= e($p['code']) ?></span></td>
            <td><a href="<?= url('admin/weavers/' . $p['weaver_id']) ?>"><?= e($p['weaver_name']) ?></a> <span class="text-muted text-sm">(<?= e($p['weaver_code']) ?>)</span></td>
            <td><?= $p['wo_code'] ? '<a class="mono-code text-sm" href="' . url('admin/production/' . $p['work_order_id']) . '">' . e($p['wo_code']) . '</a>' : '<span class="text-muted text-sm">دستی</span>' ?></td>
            <td class="text-sm"><?= $p['period'] ? fa(str_replace('-', '/', (string) $p['period'])) : '—' ?></td>
            <td class="num fw-bold"><?= toman((int) $p['amount'], false) ?></td>
            <td><?= component('status-pill', ['group' => 'payment', 'status' => $p['status']]) ?>
              <?php if ($p['paid_at']): ?><div class="text-sm text-muted"><?= jdt($p['paid_at']) ?></div><?php endif; ?>
            </td>
            <td>
              <a class="btn btn--sm btn--ghost" target="_blank" href="<?= url('admin/finance/payments/' . $p['id'] . '/print') ?>" title="چاپ فیش"><?= icon('print', '', 13) ?></a>
              <?php if ($canManage && $p['status'] === 'pending'): ?>
                <form method="post" action="<?= url('admin/finance/payments/' . $p['id'] . '/pay') ?>"
                      data-confirm="فیش <?= e($p['code']) ?> به مبلغ <?= e(toman((int) $p['amount'])) ?> پرداخت و ماندهٔ بافنده تسویه شود؟">
                  <?= csrf_field() ?>
                  <button class="btn btn--sm btn--success" type="submit"><?= icon('check', '', 13) ?> پرداخت</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= $paginator->links(url('admin/finance/payments')) ?>
  </div>
</div>
<style>@media (max-width:900px){#pay-grid{grid-template-columns:1fr !important}}</style>
