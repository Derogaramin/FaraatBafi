<?php
/** فیش چاپی دستمزد بافنده — Finance::admin.print-payment */
$this->extend('layouts.print', ['docCode' => $payment['code']]);
$statusLabel = \Core\Statuses::label('payment', (string) $payment['status']);
?>
<div class="print-info-grid">
  <div><span class="lbl">بافنده:</span> <b><?= e($weaver['name'] ?? '—') ?></b></div>
  <div><span class="lbl">کد بافندگی:</span> <span class="ltr"><?= e($weaver['code'] ?? '—') ?></span></div>
  <div><span class="lbl">کد فیش:</span> <span class="ltr"><?= e($payment['code']) ?></span></div>
  <div><span class="lbl">دوره:</span> <?= $payment['period'] ? fa(str_replace('-', '/', (string) $payment['period'])) : '—' ?></div>
  <div><span class="lbl">وضعیت:</span> <b><?= e($statusLabel) ?></b><?= $payment['paid_at'] ? ' — ' . \Core\Jalali::formatSql($payment['paid_at'], 'Y/m/d', true) : '' ?></div>
  <div><span class="lbl">روش:</span> <?= ['cash' => 'نقدی', 'bank' => 'انتقال بانکی', 'check' => 'چک'][$payment['method']] ?? $payment['method'] ?></div>
</div>

<?php if ($wo): ?>
<div class="print-section-title">جزئیات سفارش تولید مرتبط</div>
<table class="print-table">
  <thead><tr><th>کد WO</th><th>تعداد بافت</th><th>دستمزد هر واحد</th><th>جمع دستمزد</th><th>تاریخ تحویل</th></tr></thead>
  <tbody>
    <tr>
      <td class="ltr"><?= e($wo['code']) ?></td>
      <td><?= fa((string) $wo['quantity']) ?></td>
      <td><?= fa(number_format((int) $wo['unit_wage'])) ?></td>
      <td><?= fa(number_format((int) $wo['quantity'] * (int) $wo['unit_wage'])) ?></td>
      <td><?= \Core\Jalali::formatSql($wo['delivered_at'] ?? null) ?></td>
    </tr>
  </tbody>
</table>
<?php endif; ?>

<div class="print-totals">
  <table>
    <tr class="grand"><td>مبلغ قابل پرداخت</td><td><?= fa(number_format((int) $payment['amount'])) ?> تومان</td></tr>
  </table>
</div>

<?php if (!empty($payment['note'])): ?>
  <p class="print-note">توضیحات: <?= e($payment['note']) ?></p>
<?php endif; ?>

<p class="print-note">
  این فیش به‌صورت خودکار توسط سامانهٔ جامع تعاونی صادر شده و پس از پرداخت، ماندهٔ حساب بافنده به همان میزان تسویه می‌گردد.
  خدا قوت — دست‌های شما روایتگر اصالت است. 🌿
</p>

<div class="print-signatures">
  <div class="print-signature"><div class="line">امضای بافنده (تحویل‌گیرنده)</div></div>
  <div class="print-signature"><div class="line">امضای مسئول مالی</div></div>
  <div class="print-signature"><div class="line">مهر تعاونی</div></div>
</div>
