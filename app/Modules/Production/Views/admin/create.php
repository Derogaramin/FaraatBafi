<?php
/** ویوی ساخت سفارش تولید — Production::admin.create */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin/production') ?>">تولید</a> <span>/</span> <span>جدید</span></div>
    <h1>سفارش تولید جدید</h1>
    <div class="page-header__sub">کد <span class="code-chip">WO-…</span> خودکار ساخته می‌شود</div>
  </div>
</div>

<form method="post" action="<?= url('admin/production') ?>" style="max-width:680px">
  <?= csrf_field() ?>
  <div class="card stack">
    <div class="form-group">
      <label class="form-label" for="variant_id">محصول (واریانت) <span class="required">*</span></label>
      <select class="form-control" id="variant_id" name="variant_id" required>
        <option value="">انتخاب کنید…</option>
        <?php foreach ($variants as $v): ?>
          <option value="<?= $v['id'] ?>"><?= e($v['name_fa']) ?> — <?= e($v['color_name']) ?> — <?= e($v['sku']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row-3">
      <div class="form-group">
        <label class="form-label" for="quantity">تعداد <span class="required">*</span></label>
        <input class="form-control num" id="quantity" name="quantity" type="number" min="1" max="500" value="1" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="priority">اولویت</label>
        <select class="form-control" id="priority" name="priority">
          <?php foreach ($priorities as $key => $p): ?>
            <option value="<?= e($key) ?>"><?= e($p['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="due_date">موعد تحویل (شمسی)</label>
        <input class="form-control ltr jalali-date" id="due_date" name="due_date" placeholder="1405/08/15">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="weaver_id">بافنده <span class="text-muted">(اختیاری — بعداً هم قابل تخصیص است)</span></label>
        <select class="form-control" id="weaver_id" name="weaver_id">
          <option value="">— بدون تخصیص —</option>
          <?php foreach ($weavers as $w): ?>
            <option value="<?= $w->id() ?>"><?= e($w->code) ?> — <?= e($w->name) ?> (<?= e($w->skillLabel()) ?><?= $w->specialty ? '، ' . e($w->specialty) : '' ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="unit_wage">دستمزد هر واحد (تومان)</label>
        <input class="form-control num" id="unit_wage" name="unit_wage" type="number" min="0" step="1000" placeholder="خودکار از نرخ بافنده">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="note">یادداشت</label>
      <textarea class="form-control" id="note" name="note" rows="2" placeholder="مثلاً: سفارش فوری نمایشگاه نوروزی"></textarea>
    </div>
    <div class="flex gap-2">
      <button class="btn btn--primary" type="submit"><?= icon('check', '', 16) ?> ثبت سفارش تولید</button>
      <a class="btn btn--outline" href="<?= url('admin/production') ?>">انصراف</a>
    </div>
  </div>
</form>
