<?php
/** ویوی پورسانت نمایندگی‌ها — Finance::admin.commissions */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$canManage = \Core\Auth::can('finance.manage');
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>پورسانت‌ها</span></div>
    <h1>پورسانت نمایندگی‌ها</h1>
    <div class="page-header__sub">کدگذاری <span class="code-chip">COM-A03-1403-03</span></div>
  </div>
</div>

<div class="kpi-grid mb-4">
  <?= component('kpi-card', ['label' => 'پرداخت‌شده', 'value' => toman((int) ($summary['paid'] ?? 0)), 'icon' => 'check', 'color' => 'success']) ?>
  <?= component('kpi-card', ['label' => 'در انتظار / تأییدشده', 'value' => toman((int) ($summary['pending'] ?? 0)), 'icon' => 'clock', 'color' => 'warning']) ?>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>کد</th><th>نمایندگی</th><th>سفارش</th><th>دوره</th><th>مبنای محاسبه</th><th>نرخ</th><th>پورسانت</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($commissions as $c): ?>
      <tr>
        <td><span class="code-chip"><?= e($c['code']) ?></span></td>
        <td><a href="<?= url('admin/agencies/' . $c['agency_id']) ?>"><?= e($c['agency_name']) ?></a></td>
        <td><?= $c['order_code'] ? '<a class="mono-code text-sm" href="' . url('admin/orders/' . $c['order_id']) . '">' . e($c['order_code']) . '</a>' : '—' ?></td>
        <td class="text-sm"><?= $c['period'] ? fa(str_replace('-', '/', (string) $c['period'])) : '—' ?></td>
        <td class="num text-sm"><?= toman((int) $c['base_amount'], false) ?></td>
        <td class="num"><?= fa((string) $c['rate']) ?>٪</td>
        <td class="num fw-bold"><?= toman((int) $c['amount'], false) ?></td>
        <td><?= component('status-pill', ['group' => 'commission', 'status' => $c['status']]) ?></td>
        <td>
          <?php if ($canManage && $c['status'] !== 'paid' && $c['status'] !== 'rejected'): ?>
            <div class="actions">
              <?php if ($c['status'] === 'pending'): ?>
              <form method="post" action="<?= url('admin/finance/commissions/' . $c['id'] . '/status') ?>" class="m-0">
                <?= csrf_field() ?><input type="hidden" name="status" value="approved">
                <button class="btn btn--sm btn--outline" type="submit"><?= icon('check', '', 12) ?> تأیید</button>
              </form>
              <?php endif; ?>
              <form method="post" action="<?= url('admin/finance/commissions/' . $c['id'] . '/status') ?>" class="m-0"
                    data-confirm="پورسانت <?= e($c['code']) ?> پرداخت‌شده علامت بخورد؟">
                <?= csrf_field() ?><input type="hidden" name="status" value="paid">
                <button class="btn btn--sm btn--success" type="submit"><?= icon('coin', '', 12) ?> پرداخت</button>
              </form>
              <form method="post" action="<?= url('admin/finance/commissions/' . $c['id'] . '/status') ?>" class="m-0"
                    data-confirm="پورسانت <?= e($c['code']) ?> رد شود؟" data-confirm-danger>
                <?= csrf_field() ?><input type="hidden" name="status" value="rejected">
                <button class="btn btn--sm btn--ghost text-danger" type="submit"><?= icon('x', '', 12) ?></button>
              </form>
            </div>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
