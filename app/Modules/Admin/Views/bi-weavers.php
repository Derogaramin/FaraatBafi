<?php
/** ویوی عملکرد بافندگان — Admin::bi-weavers */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/bi') ?>">هوش تجاری</a> <span>/</span> <span>بافندگان</span></div>
    <h1>عملکرد بافندگان</h1>
    <div class="page-header__sub"><?= fa((string) count($rows)) ?> بافنده</div>
  </div>
</div>

<div class="card mb-4">
  <input class="form-control" data-table-search="#weaver-perf tbody tr" placeholder="<?= icon('search', '', 14) ?> جست‌وجوی زنده در جدول…">
</div>

<div class="table-wrap">
  <table class="table" id="weaver-perf">
    <thead>
      <tr><th>کد</th><th>نام</th><th>تخصص</th><th>مهارت</th><th>کل WO</th><th>فعال</th><th>تکمیل</th><th>میانگین QC</th><th>امتیاز</th><th>دستمزد پرداخت‌شده</th><th>مانده</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $w): ?>
      <tr>
        <td><a class="code-chip" href="<?= url('admin/weavers/' . (int) $w['id']) ?>"><?= e($w['code']) ?></a></td>
        <td class="fw-bold"><?= e($w['name']) ?></td>
        <td class="text-sm"><?= e((string) ($w['specialty'] ?: '—')) ?></td>
        <td><span class="badge badge--action"><?= e(\Core\Statuses::SKILL[$w['skill_level']] ?? '') ?></span></td>
        <td class="num"><?= fa((string) $w['total_wos']) ?></td>
        <td class="num"><?= fa((string) $w['active']) ?></td>
        <td class="num"><?= fa((string) $w['completed']) ?></td>
        <td><?= $w['avg_score'] !== null ? fa((string) $w['avg_score']) : '—' ?></td>
        <td><?= fa((string) $w['rating']) ?> ⭐</td>
        <td class="num text-sm"><?= toman((int) $w['total_paid'], false) ?></td>
        <td class="num"><?= (int) $w['balance'] > 0 ? '<span class="badge badge--warning">' . toman_short((int) $w['balance']) . '</span>' : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
