<?php
/** ویوی فهرست نمایندگی‌ها — Members::admin.agencies */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>نمایندگی‌ها</span></div>
    <h1>نمایندگی‌های فروش</h1>
    <div class="page-header__sub">کدگذاری <span class="code-chip">A03</span></div>
  </div>
  <a class="btn btn--primary" href="<?= url('admin/agencies/create') ?>"><?= icon('plus', '', 16) ?> نمایندگی جدید</a>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>کد</th><th>نام</th><th>مدیر</th><th>شهر</th><th>پورسانت</th><th>سفارش‌ها</th><th>پورسانت معوق</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($agencies as $a): ?>
      <tr>
        <td><span class="code-chip"><?= e($a['code']) ?></span></td>
        <td class="fw-bold"><?= e($a['name']) ?><?php if ($a['phone']): ?><div class="text-sm text-muted num ltr"><?= fa($a['phone']) ?></div><?php endif; ?></td>
        <td><?= e((string) ($a['manager_name'] ?: '—')) ?></td>
        <td><?= e((string) ($a['city'] ?: '—')) ?></td>
        <td class="num"><?= fa((string) $a['commission_rate']) ?>٪</td>
        <td class="num"><?= fa((string) $a['orders_count']) ?></td>
        <td class="num"><?= (int) $a['pending_commission'] > 0 ? toman((int) $a['pending_commission'], false) : '—' ?></td>
        <td><?= $a['status'] === 'active' ? '<span class="badge badge--success badge-dot">فعال</span>' : '<span class="badge badge--muted badge-dot">غیرفعال</span>' ?></td>
        <td class="actions">
          <a class="btn btn--sm btn--outline" href="<?= url('admin/agencies/' . $a['id']) ?>"><?= icon('eye', '', 13) ?></a>
          <a class="btn btn--sm btn--ghost" href="<?= url('admin/agencies/' . $a['id'] . '/edit') ?>"><?= icon('edit', '', 13) ?></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
