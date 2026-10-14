<?php
/** ویوی فهرست سفارش‌ها — پنل مدیر (Marketplace::admin.orders) */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>سفارش‌ها</span></div>
    <h1>سفارش‌های فروش</h1>
    <div class="page-header__sub">مجموع <?= fa((string) $paginator->total) ?> سفارش</div>
  </div>
  <a class="btn btn--sm btn--outline" href="<?= url('admin/orders/export?' . http_build_query(array_filter(['status' => $filters['status'], 'channel' => $filters['channel'], 'q' => $filters['q']]))) ?>">
    <?= icon('download', '', 14) ?> خروجی CSV
  </a>
</div>
<?php
// لینک ستون مرتب‌شدنی
$sortLink = function (string $key, string $label) use ($filters): string {
    $isCurrent = ($filters['sort'] ?? 'id') === $key;
    $nextDir = $isCurrent && ($filters['dir'] ?? 'desc') === 'desc' ? 'asc' : 'desc';
    $qs = http_build_query(array_filter(['status' => $filters['status'], 'channel' => $filters['channel'], 'q' => $filters['q']]) + ['sort' => $key, 'dir' => $nextDir]);
    $arrow = $isCurrent ? (($filters['dir'] ?? 'desc') === 'desc' ? ' ↓' : ' ↑') : '';
    return '<a href="' . e(url('admin/orders') . '?' . $qs) . '" class="' . ($isCurrent ? 'fw-bold' : '') . '">' . e($label) . $arrow . '</a>';
};
?>

<form class="filter-bar" method="get" action="<?= url('admin/orders') ?>">
  <input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="جست‌وجو: کد سفارش / گیرنده / موبایل">
  <select class="form-control" name="status" data-auto-submit>
    <option value="">همهٔ وضعیت‌ها</option>
    <?php foreach ($statuses as $key => $s): ?>
      <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($s['label']) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="form-control" name="channel" data-auto-submit>
    <option value="">همهٔ کانال‌ها</option>
    <?php foreach ($channels as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $filters['channel'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn--sm btn--outline" type="submit"><?= icon('search', '', 15) ?> فیلتر</button>
</form>

<?php if ($orders === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'cart', 'title' => 'سفارشی یافت نشد']) ?></div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th><?= $sortLink('id', 'کد سفارش') ?></th><th>مشتری</th><th>کانال</th><th><?= $sortLink('status', 'وضعیت') ?></th>
        <th><?= $sortLink('total', 'مبلغ کل') ?></th><th><?= $sortLink('placed_at', 'تاریخ ثبت') ?></th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td><span class="code-chip"><?= e($o['code']) ?></span></td>
        <td>
          <?= e($o['customer_name'] ?? '—') ?>
          <?php if (!empty($o['agency_name'])): ?><div class="text-sm text-muted"><?= icon('store', '', 12) ?> <?= e($o['agency_name']) ?></div><?php endif; ?>
        </td>
        <td><span class="badge badge--muted"><?= e(\Core\Statuses::CHANNEL[$o['channel']] ?? $o['channel']) ?></span></td>
        <td><?= component('status-pill', ['group' => 'order', 'status' => $o['status']]) ?></td>
        <td class="num"><?= toman((int) $o['total']) ?></td>
        <td class="text-sm"><?= jdt($o['placed_at'], 'Y/m/d', true) ?></td>
        <td class="actions">
          <a class="btn btn--sm btn--outline" href="<?= url('admin/orders/' . $o['id']) ?>"><?= icon('eye', '', 14) ?> جزئیات</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $paginator->links(url('admin/orders')) ?>
<?php endif; ?>
