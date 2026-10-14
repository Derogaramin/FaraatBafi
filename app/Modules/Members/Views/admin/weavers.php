<?php
/** ویوی فهرست بافندگان — Members::admin.weavers */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>بافندگان</span></div>
    <h1>بافندگان تعاونی</h1>
    <div class="page-header__sub">مجموع <?= fa((string) $paginator->total) ?> بافنده — کدگذاری <span class="code-chip">W045</span></div>
  </div>
  <a class="btn btn--primary" href="<?= url('admin/weavers/create') ?>"><?= icon('plus', '', 16) ?> بافندهٔ جدید</a>
</div>

<form class="filter-bar" method="get" action="<?= url('admin/weavers') ?>">
  <input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="جست‌وجو: نام / کد / موبایل / شهر">
  <select class="form-control" name="status" data-auto-submit>
    <option value="">همه</option>
    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>فعال</option>
    <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
  </select>
  <button class="btn btn--sm btn--outline" type="submit"><?= icon('search', '', 15) ?></button>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>کد</th><th>نام</th><th>تخصص</th><th>مهارت</th><th>شهر</th><th>بافت فعال</th><th>تکمیل‌شده</th><th>ماندهٔ دستمزد</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($weavers as $w): ?>
      <tr>
        <td><span class="code-chip"><?= e($w['code']) ?></span></td>
        <td class="fw-bold"><?= e($w['name']) ?><?php if (!empty($w['phone'])): ?><div class="text-sm text-muted num ltr"><?= fa($w['phone']) ?></div><?php endif; ?></td>
        <td><?= e((string) ($w['specialty'] ?: '—')) ?></td>
        <td><span class="badge badge--action"><?= e(\Core\Statuses::SKILL[$w['skill_level']] ?? '') ?></span></td>
        <td class="text-sm"><?= e((string) ($w['city'] ?: '—')) ?></td>
        <td class="num"><?= fa((string) $w['active_wos']) ?></td>
        <td class="num"><?= fa((string) $w['done_wos']) ?></td>
        <td class="num"><?= (int) $w['balance'] > 0 ? toman((int) $w['balance'], false) : '—' ?></td>
        <td><?= $w['status'] === 'active' ? '<span class="badge badge--success badge-dot">فعال</span>' : '<span class="badge badge--muted badge-dot">غیرفعال</span>' ?></td>
        <td class="actions">
          <a class="btn btn--sm btn--outline" href="<?= url('admin/weavers/' . $w['id']) ?>"><?= icon('eye', '', 13) ?></a>
          <a class="btn btn--sm btn--ghost" href="<?= url('admin/weavers/' . $w['id'] . '/edit') ?>"><?= icon('edit', '', 13) ?></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $paginator->links(url('admin/weavers')) ?>
