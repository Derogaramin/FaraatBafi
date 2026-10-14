<?php
/** ویوی انبار مواد اولیه — Production::admin.materials */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$canManage = \Core\Auth::can('inventory.manage');
$unitLabels = ['kg' => 'کیلوگرم', 'gr' => 'گرم', 'm' => 'متر', 'count' => 'عدد', 'l' => 'لیتر'];
$catLabels = ['yarn' => 'نخ', 'dye' => 'رنگ', 'cotton' => 'پنبه', 'wool' => 'پشم', 'silk' => 'ابریشم', 'packaging' => 'بسته‌بندی', 'other' => 'سایر'];
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <span>انبار مواد اولیه</span></div>
    <h1>مواد اولیه</h1>
    <div class="page-header__sub">کدگذاری <span class="code-chip">RM-COT-R01</span> و موقعیت <span class="code-chip">LOC-MAIN-A3-01</span></div>
  </div>
  <a class="btn btn--sm btn--outline" href="<?= url('admin/inventory/movements') ?>"><?= icon('refresh', '', 14) ?> حرکت‌های انبار</a>
</div>

<div class="grid" style="grid-template-columns:1fr 2.2fr;gap:var(--space-5);align-items:start" id="mat-grid">
  <?php if ($canManage): ?>
  <div class="card">
    <h3 class="card__title mb-3"><?= icon('plus', '', 18) ?> مادهٔ جدید</h3>
    <form method="post" action="<?= url('admin/inventory/materials') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0">
        <label class="form-label">نام ماده <span class="required">*</span></label>
        <input class="form-control" name="name_fa" placeholder="نخ پنبهٔ رنگ‌شده — قرمز روناس" required>
      </div>
      <div class="form-row">
        <div class="form-group m-0">
          <label class="form-label">دسته <span class="required">*</span></label>
          <select class="form-control" name="category" required>
            <?php foreach ($catLabels as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group m-0">
          <label class="form-label">واحد <span class="required">*</span></label>
          <select class="form-control" name="unit" required>
            <?php foreach ($unitLabels as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group m-0">
          <label class="form-label">خانواده (۳ حرف) <span class="required">*</span></label>
          <input class="form-control ltr" name="family" pattern="[A-Z]{3}" placeholder="COT" required style="text-transform:uppercase">
        </div>
        <div class="form-group m-0">
          <label class="form-label">حرف+رقم رنگ <span class="required">*</span></label>
          <div class="flex gap-1">
            <input class="form-control ltr" name="color_letter" pattern="[A-Za-z]" maxlength="1" placeholder="R" required style="width:56px;text-transform:uppercase">
            <input class="form-control num" name="color_number" type="number" min="1" max="99" value="1" required style="width:80px">
          </div>
        </div>
      </div>
      <div class="form-group m-0">
        <label class="form-label">قیمت هر واحد (تومان) <span class="required">*</span></label>
        <input class="form-control num" name="price_per_unit" type="number" min="0" step="1000" required>
      </div>
      <div class="form-row">
        <div class="form-group m-0">
          <label class="form-label">موجودی اولیه</label>
          <input class="form-control num" name="stock" type="number" step="0.001" min="0" value="0">
        </div>
        <div class="form-group m-0">
          <label class="form-label">حد سفارش</label>
          <input class="form-control num" name="min_stock" type="number" step="0.001" min="0" value="0">
        </div>
      </div>
      <div class="form-group m-0">
        <label class="form-label">موقعیت انبار</label>
        <input class="form-control ltr" name="location_code" placeholder="LOC-MAIN-A3-01" pattern="LOC-[A-Z]+-[A-Z][0-9]-[0-9]{2}">
      </div>
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 15) ?> ثبت ماده</button>
      <p class="form-hint m-0">کد RM به‌صورت خودکار از خانواده + کد رنگ ساخته می‌شود.</p>
    </form>
  </div>
  <?php endif; ?>

  <div class="stack">
    <?php if (count($materials) === 0): ?>
      <div class="card"><?= component('empty-state', ['icon' => 'warehouse', 'title' => 'مادهٔ اولیه‌ای ثبت نشده است']) ?></div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>کد</th><th>نام</th><th>دسته</th><th>موجودی</th><th>حد سفارش</th><th>فی</th><th>موقعیت</th><?php if ($canManage): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
          <?php foreach ($materials as $m): ?>
          <tr>
            <td><span class="code-chip"><?= e($m->code) ?></span></td>
            <td class="fw-bold"><?= e($m->name_fa) ?></td>
            <td><span class="badge badge--muted"><?= e($catLabels[$m->category] ?? $m->category) ?></span></td>
            <td>
              <?php if ($m->isLow()): ?>
                <span class="badge badge--danger badge-dot"><?= fa(rtrim(rtrim(number_format((float) $m->stock, 2), '0'), '.')) ?> <?= e($m->unitLabel()) ?></span>
              <?php else: ?>
                <span class="num"><?= fa(rtrim(rtrim(number_format((float) $m->stock, 2), '0'), '.')) ?></span> <span class="text-sm text-muted"><?= e($m->unitLabel()) ?></span>
              <?php endif; ?>
            </td>
            <td class="num text-sm"><?= fa(rtrim(rtrim(number_format((float) $m->min_stock, 2), '0'), '.')) ?></td>
            <td class="num text-sm"><?= toman((int) $m->price_per_unit, false) ?></td>
            <td class="text-sm mono-code"><?= e((string) $m->location_code ?: '—') ?></td>
            <?php if ($canManage): ?>
            <td>
              <button class="btn btn--sm btn--ghost" type="button" data-open-adjust="<?= $m->id() ?>" data-name="<?= e($m->name_fa) ?>" data-stock="<?= e((string) $m->stock) ?>" title="اصلاح موجودی"><?= icon('edit', '', 13) ?></button>
              <form method="post" action="<?= url('admin/inventory/materials/' . $m->id() . '/adjust') ?>" class="hidden" id="adjust-<?= $m->id() ?>">
                <?= csrf_field() ?>
                <input type="number" name="stock" step="0.001" min="0" value="<?= e((string) $m->stock) ?>" data-adjust-stock>
                <input type="text" name="note" placeholder="علت اصلاح" data-adjust-note>
                <button type="submit">ثبت</button>
              </form>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<style>@media (max-width:900px){#mat-grid{grid-template-columns:1fr !important}}</style>

<?php if ($canManage): ?>
<div class="modal-backdrop" id="adjust-modal">
  <div class="modal">
    <div class="modal__title">اصلاح موجودی — <span id="adjust-name"></span></div>
    <form method="post" id="adjust-form">
      <?= csrf_field() ?>
      <div class="modal__body">
        <div class="form-group">
          <label class="form-label" for="adjust-stock">موجودی جدید</label>
          <input class="form-control num" id="adjust-stock" name="stock" type="number" step="0.001" min="0" required>
        </div>
        <div class="form-group m-0">
          <label class="form-label" for="adjust-note">علت اصلاح</label>
          <input class="form-control" id="adjust-note" name="note" placeholder="انبارگردانی / ضایعات / …">
        </div>
      </div>
      <div class="modal__footer">
        <button class="btn btn--primary" type="submit">ثبت اصلاح</button>
        <button class="btn btn--outline" type="button" data-close-modal="adjust-modal">انصراف</button>
      </div>
    </form>
  </div>
</div>
<script>
document.querySelectorAll('[data-open-adjust]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.getElementById('adjust-name').textContent = btn.getAttribute('data-name');
    document.getElementById('adjust-stock').value = btn.getAttribute('data-stock');
    document.getElementById('adjust-form').action = '<?= url('admin/inventory/materials') ?>/' + btn.getAttribute('data-open-adjust') + '/adjust';
    document.getElementById('adjust-modal').classList.add('is-open');
  });
});
document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
  btn.addEventListener('click', function () { document.getElementById(btn.getAttribute('data-close-modal')).classList.remove('is-open'); });
});
document.getElementById('adjust-modal').addEventListener('click', function (e) { if (e.target === this) this.classList.remove('is-open'); });
</script>
<?php endif; ?>
