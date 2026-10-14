<?php
/** فاکتور چاپی سفارش — Marketplace::admin.print-invoice */
$this->extend('layouts.print', ['docCode' => $invoice['code'] ?? $order['code']]);
?>
<div class="print-info-grid">
  <div><span class="lbl">خریدار:</span> <b><?= e($order['receiver']) ?></b><?= $customer ? ' — کد مشتری: <span class="ltr">' . e($customer['code']) . '</span>' : '' ?></div>
  <div><span class="lbl">کد سفارش:</span> <span class="ltr"><?= e($order['code']) ?></span></div>
  <div><span class="lbl">تلفن:</span> <span class="ltr"><?= e($order['phone']) ?></span></div>
  <div><span class="lbl">تاریخ سفارش:</span> <?= e(\Core\Jalali::formatSql($order['placed_at'], 'Y/m/d', true)) ?></div>
  <div style="grid-column:1/-1"><span class="lbl">نشانی:</span> <?= e(trim(($order['province'] ?? '') . '، ' . ($order['city'] ?? '') . '، ' . ($order['address'] ?? ''))) ?></div>
</div>

<div class="print-section-title">شرح اقلام</div>
<table class="print-table">
  <thead>
    <tr><th>#</th><th>محصول</th><th>کد SKU</th><th>تعداد</th><th>فی (تومان)</th><th>جمع (تومان)</th></tr>
  </thead>
  <tbody>
    <?php foreach ($items as $i => $item): ?>
    <tr>
      <td><?= fa((string) ($i + 1)) ?></td>
      <td><?= e($item['title']) ?></td>
      <td class="ltr" style="font-size:10px"><?= e($item['sku']) ?></td>
      <td><?= fa((string) $item['quantity']) ?></td>
      <td><?= fa(number_format((int) $item['unit_price'])) ?></td>
      <td><?= fa(number_format((int) $item['total'])) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="print-totals">
  <table>
    <tr><td>جمع اقلام</td><td><?= fa(number_format((int) $order['subtotal'])) ?> تومان</td></tr>
    <?php if ((int) $order['discount'] > 0): ?>
      <tr><td>تخفیف</td><td>− <?= fa(number_format((int) $order['discount'])) ?> تومان</td></tr>
    <?php endif; ?>
    <tr><td>هزینهٔ ارسال</td><td><?= (int) $order['shipping_cost'] === 0 ? 'رایگان' : fa(number_format((int) $order['shipping_cost'])) . ' تومان' ?></td></tr>
    <tr class="grand"><td>مبلغ قابل پرداخت</td><td><?= fa(number_format((int) $order['total'])) ?> تومان</td></tr>
    <?php if ($invoice): ?>
      <tr><td>وضعیت پرداخت</td><td><?= $invoice['paid'] ? 'پرداخت‌شده ✓' : 'پرداخت‌نشده' ?><?= $invoice['paid_at'] ? ' — ' . \Core\Jalali::formatSql($invoice['paid_at']) : '' ?></td></tr>
    <?php endif; ?>
  </table>
</div>

<p class="print-note">
  کلیهٔ محصولات دارای کد اصالت یکتا (FB) و برچسب QR هستند؛ اصالت را از نشانی
  <span class="ltr">faraat.ir/authenticity</span> استعلام کنید. کالاهای دست‌باف تا ۷ روز با حفظ برچسب قابل مرجوعی‌اند.
</p>

<div class="print-signatures">
  <div class="print-signature"><div class="line">امضای تحویل‌گیرنده</div></div>
  <div class="print-signature"><div class="line">امضای مسئول فروش</div></div>
  <div class="print-signature"><div class="line">مهر تعاونی</div></div>
</div>
