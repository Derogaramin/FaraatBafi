<?php
/** ویوی ممیزی تغییرات — Admin::audits */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>ممیزی</span></div>
    <h1>ممیزی تغییرات حساس</h1>
    <div class="page-header__sub">چه کسی، چه چیزی را، از چه به چه تغییر داد — قیمت‌ها، موجودی‌ها، نرخ‌ها و وضعیت‌ها</div>
  </div>
</div>

<form class="filter-bar" method="get" action="<?= url('admin/audits') ?>">
  <select class="form-control" name="entity" data-auto-submit>
    <option value="">همهٔ موجودیت‌ها</option>
    <?php foreach ($entities as $en): ?>
      <option value="<?= e($en) ?>" <?= $entity === $en ? 'selected' : '' ?>><?= e($labels[$en] ?? $en) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<?php if ($rows === []): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'clock', 'title' => 'تغییری ثبت نشده است', 'message' => 'به‌محض تغییر قیمت، موجودی، نرخ دستمزد یا پورسانت، ردیف ممیزی ساخته می‌شود.']) ?></div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>زمان</th><th>کاربر</th><th>موجودیت</th><th>کد</th><th>فیلد</th><th>از</th><th>به</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td class="text-sm"><?= jdt($r['created_at'], 'Y/m/d', true) ?></td>
        <td class="text-sm"><?= e((string) ($r['user_name'] ?? 'سامانه')) ?></td>
        <td><span class="badge badge--muted"><?= e($labels[$r['entity']] ?? $r['entity']) ?></span></td>
        <td><?= $r['entity_code'] ? '<span class="code-chip" style="font-size:.62rem">' . e($r['entity_code']) . '</span>' : '<span class="text-muted text-sm">#' . fa((string) $r['entity_id']) . '</span>' ?></td>
        <td class="text-sm fw-bold"><?= e($fields[$r['field']] ?? $r['field']) ?></td>
        <td class="num text-sm text-danger"><?= $r['old_value'] !== '' && $r['old_value'] !== null ? e($r['old_value']) : '—' ?></td>
        <td class="num text-sm text-success"><?= $r['new_value'] !== '' && $r['new_value'] !== null ? e($r['new_value']) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $paginator->links(url('admin/audits')) ?>
<?php endif; ?>
