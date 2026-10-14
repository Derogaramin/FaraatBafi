<?php
/** ویوی اسناد حسابداری — Finance::admin.documents */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$canManage = \Core\Auth::can('finance.manage');
$kindLabels = ['income' => ['درآمد', 'success'], 'expense' => ['هزینه', 'danger'], 'transfer' => ['انتقال', 'info']];
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>اسناد حسابداری</span></div>
    <h1>اسناد حسابداری</h1>
    <div class="page-header__sub">کدگذاری <span class="code-chip">ACC-1403-05-003</span></div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 2.4fr;gap:var(--space-5);align-items:start" id="doc-grid">
  <?php if ($canManage): ?>
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> سند جدید</h3>
    <form method="post" action="<?= url('admin/finance/documents') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0"><label class="form-label">شرح <span class="required">*</span></label><input class="form-control" name="title" required></div>
      <div class="form-row">
        <div class="form-group m-0">
          <label class="form-label">نوع <span class="required">*</span></label>
          <select class="form-control" name="kind" required>
            <option value="income">درآمد</option><option value="expense">هزینه</option><option value="transfer">انتقال</option>
          </select>
        </div>
        <div class="form-group m-0"><label class="form-label">مبلغ (تومان) <span class="required">*</span></label><input class="form-control num" name="amount" type="number" min="1" required></div>
      </div>
      <div class="form-group m-0">
        <label class="form-label">تاریخ سند (شمسی) <span class="required">*</span></label>
        <input class="form-control ltr jalali-date" name="doc_date" value="<?= e(\Core\Jalali::format('Y/m/d')) ?>" required>
      </div>
      <div class="form-group m-0"><label class="form-label">یادداشت</label><input class="form-control" name="note"></div>
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ثبت سند</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>کد سند</th><th>شرح</th><th>نوع</th><th>مبلغ</th><th>تاریخ</th><th>ثبت‌کننده</th></tr></thead>
      <tbody>
        <?php foreach ($documents as $d): ?>
        <tr>
          <td><span class="code-chip"><?= e($d['code']) ?></span></td>
          <td><?= e($d['title']) ?><?php if ($d['note']): ?><div class="text-sm text-muted"><?= e($d['note']) ?></div><?php endif; ?></td>
          <td><span class="badge badge--<?= $kindLabels[$d['kind']][1] ?? 'muted' ?>"><?= e($kindLabels[$d['kind']][0] ?? $d['kind']) ?></span></td>
          <td class="num fw-bold"><?= toman((int) $d['amount'], false) ?></td>
          <td class="text-sm"><?= jdt($d['doc_date']) ?></td>
          <td class="text-sm"><?= e((string) ($d['creator'] ?? '—')) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?= $paginator->links(url('admin/finance/documents')) ?>
<style>@media (max-width:900px){#doc-grid{grid-template-columns:1fr !important}}</style>
