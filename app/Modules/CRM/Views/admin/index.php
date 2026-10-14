<?php
/** ویوی تیکت‌ها در پنل مدیر — CRM::admin.index */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$catLabels = ['order' => 'سفارش', 'product' => 'محصول', 'payment' => 'پرداخت', 'complaint' => 'شکایت', 'other' => 'سایر'];
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>تیکت‌ها</span></div>
    <h1>تیکت‌های پشتیبانی</h1>
  </div>
</div>

<div class="chip-row mb-4">
  <a class="chip <?= $filter === '' ? 'active' : '' ?>" href="<?= url('admin/tickets') ?>">همه</a>
  <?php foreach ($statuses as $key => $s): ?>
    <a class="chip <?= $filter === $key ? 'active' : '' ?>" href="<?= url('admin/tickets?status=' . $key) ?>"><?= e($s['label']) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tickets === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'ticket', 'title' => 'تیکتی وجود ندارد']) ?></div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>کد</th><th>موضوع</th><th>کاربر</th><th>دسته</th><th>اولویت</th><th>پاسخ‌ها</th><th>وضعیت</th><th>آخرین فعالیت</th></tr></thead>
    <tbody>
      <?php foreach ($tickets as $t): ?>
      <tr>
        <td><span class="code-chip"><?= e($t['code']) ?></span></td>
        <td><a class="fw-bold" href="<?= url('admin/tickets/' . $t['id']) ?>"><?= e($t['subject']) ?></a></td>
        <td class="text-sm"><?= e($t['user_name']) ?> <span class="code-chip" style="font-size:.6rem"><?= e($t['user_code']) ?></span></td>
        <td><span class="badge badge--muted"><?= e($catLabels[$t['category']] ?? $t['category']) ?></span></td>
        <td><?= component('status-pill', ['group' => 'priority', 'status' => $t['priority']]) ?></td>
        <td class="num"><?= fa((string) $t['replies_count']) ?></td>
        <td><?= component('status-pill', ['group' => 'ticket', 'status' => $t['status']]) ?></td>
        <td class="text-sm text-muted"><?= jrelative($t['updated_at'] ?? $t['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
