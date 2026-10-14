<?php
/** ویوی رأی‌گیری — Admin::governance.votes */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$canManage = \Core\Auth::can('governance.manage');
$canVote = \Core\Auth::can('voting.participate');
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>رأی‌گیری</span></div>
    <h1>رأی‌گیری اعضای تعاونی</h1>
    <div class="page-header__sub">کدگذاری <span class="code-chip">VOT-1403-001</span> — فقط اعضای اصلی (M) رأی می‌دهند</div>
  </div>
</div>

<?php if ($canManage): ?>
<div class="card mb-4">
  <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> رأی‌گیری جدید</h3>
  <form method="post" action="<?= url('admin/governance/votes') ?>" class="stack">
    <?= csrf_field() ?>
    <div class="form-row">
      <div class="form-group m-0"><label class="form-label">عنوان <span class="required">*</span></label><input class="form-control" name="title" required placeholder="تصویب خرید دستگاه بافت جدید"></div>
      <div class="form-group m-0"><label class="form-label">پایان رأی‌گیری (شمسی) <span class="required">*</span></label><input class="form-control ltr jalali-date" name="ends_at" required placeholder="<?= e(\Core\Jalali::format('Y/m/d')) ?>"></div>
    </div>
    <div class="form-group m-0"><label class="form-label">توضیحات</label><textarea class="form-control" name="description" rows="2"></textarea></div>
    <div class="form-group m-0">
      <label class="form-label">گزینه‌ها (هرکدام در یک خط) <span class="required">*</span></label>
      <textarea class="form-control" name="options" rows="3" required placeholder="موافق خرید با اعتبار ۵۰۰ میلیون تومان&#10;موافق اجارهٔ دستگاه&#10;مخالف — به تعویق بیفتد"></textarea>
    </div>
    <button class="btn btn--primary" type="submit"><?= icon('vote', '', 15) ?> ایجاد و بازکردن رأی‌گیری</button>
  </form>
</div>
<?php endif; ?>

<?php if (count($votes) === 0): ?>
  <div class="card"><?= component('empty-state', ['icon' => 'vote', 'title' => 'رأی‌گیری فعالی وجود ندارد']) ?></div>
<?php else: ?>
<div class="stack">
  <?php foreach ($votes as $v): ?>
    <?php
    $options = $v->options ?: [];
    $tally = $tallies[$v->id()] ?? [];
    $counts = array_column($tally, 'c', 'choice');
    $totalVotes = array_sum(array_map('intval', $counts));
    ?>
    <div class="card">
      <div class="flex-between mb-2" style="flex-wrap:wrap;gap:8px">
        <div>
          <span class="code-chip"><?= e($v->code) ?></span>
          <b class="ms-2"><?= e($v->title) ?></b>
        </div>
        <span class="badge badge--<?= ['open' => 'success', 'closed' => 'muted', 'draft' => 'warning'][$v->status] ?? 'muted' ?>">
          <?= ['open' => 'باز', 'closed' => 'بسته', 'draft' => 'پیش‌نویس'][$v->status] ?? '' ?>
        </span>
      </div>
      <?php if ($v->description): ?><p class="text-sm text-secondary"><?= e($v->description) ?></p><?php endif; ?>
      <div class="text-sm text-muted mb-3">پایان: <?= jdt($v->ends_at, 'Y/m/d', true) ?> • <?= fa((string) $totalVotes) ?> رأی</div>

      <?php foreach ($options as $i => $opt): ?>
        <?php $pct = $totalVotes > 0 ? (int) round(((int) ($counts[$i] ?? 0) / $totalVotes) * 100) : 0; ?>
        <div class="mb-3">
          <div class="flex-between text-sm mb-1">
            <span><?= e((string) $opt) ?></span>
            <b class="num"><?= fa((string) ($counts[$i] ?? 0)) ?> رأی (<?= fa((string) $pct) ?>٪)</b>
          </div>
          <div class="progress"><div class="progress__bar" style="width:<?= $pct ?>%"></div></div>
        </div>
      <?php endforeach; ?>

      <?php if ($canVote && $v->status === 'open' && strtotime((string) $v->ends_at) >= time()): ?>
        <form method="post" action="<?= url('admin/governance/votes/' . $v->id() . '/ballot') ?>" class="flex gap-2 mt-3" style="flex-wrap:wrap">
          <?= csrf_field() ?>
          <select class="form-control" name="choice" required style="width:auto;flex:1;min-width:200px">
            <option value="">رأی شما…</option>
            <?php foreach ($options as $i => $opt): ?><option value="<?= $i ?>"><?= e((string) $opt) ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn--secondary btn--sm" type="submit" data-confirm-vote><?= icon('check', '', 14) ?> ثبت رأی</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
