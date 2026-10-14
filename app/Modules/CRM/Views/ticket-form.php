<?php
/** ویوی فرم تیکت جدید — CRM::ticket-form */
$this->extend('layouts.panel', ['panel' => 'account', 'panelTitle' => 'پنل کاربر']);
$preOrder = $_GET['order'] ?? '';
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('my/tickets') ?>">تیکت‌ها</a> <span>/</span> <span>جدید</span></div>
    <h1>ثبت تیکت پشتیبانی</h1>
    <div class="page-header__sub">کد تیکت (<span class="code-chip">TKT-…</span>) خودکار ساخته می‌شود</div>
  </div>
</div>

<form method="post" action="<?= url('my/tickets') ?>" style="max-width:680px">
  <?= csrf_field() ?>
  <div class="card stack">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="subject">موضوع <span class="required">*</span></label>
        <input class="form-control" id="subject" name="subject" value="<?= e(old('subject')) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="category">دسته <span class="required">*</span></label>
        <select class="form-control" id="category" name="category" required>
          <option value="order">سفارش و ارسال</option>
          <option value="product">محصول و اصالت</option>
          <option value="payment">پرداخت</option>
          <option value="complaint">شکایت</option>
          <option value="other">سایر</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="priority">اولویت</label>
        <select class="form-control" id="priority" name="priority">
          <option value="low">کم</option>
          <option value="normal" selected>عادی</option>
          <option value="high">زیاد</option>
          <option value="urgent">فوری</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="order_code">کد سفارش مرتبط</label>
        <select class="form-control" id="order_code" name="order_code">
          <option value="">—</option>
          <?php foreach ($myOrders as $o): ?>
            <option value="<?= e($o['code']) ?>" <?= $preOrder === $o['code'] ? 'selected' : '' ?>><?= e($o['code']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="body">شرح پیام <span class="required">*</span></label>
      <textarea class="form-control" id="body" name="body" rows="6" required><?= e(old('body')) ?></textarea>
    </div>
    <div class="flex gap-2">
      <button class="btn btn--primary" type="submit"><?= icon('ticket', '', 16) ?> ثبت تیکت</button>
      <a class="btn btn--outline" href="<?= url('my/tickets') ?>">انصراف</a>
    </div>
  </div>
</form>
