<?php
/** ویوی جزئیات سفارش — پنل مدیر (Marketplace::admin.order-detail) */
$this->extend('layouts.panel', ['panel' => 'admin', 'panelTitle' => 'مدیریت تعاونی']);
$steps = ['pending', 'confirmed', 'in_production', 'ready', 'shipped', 'delivered'];
$stepLabels = ['pending' => 'ثبت', 'confirmed' => 'تأیید', 'in_production' => 'بافت', 'ready' => 'آماده', 'shipped' => 'ارسال', 'delivered' => 'تحویل'];
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('admin') ?>">داشبورد</a> <span>/</span> <a href="<?= url('admin/orders') ?>">سفارش‌ها</a> <span>/</span> <span><?= e($order['code']) ?></span></div>
    <h1>سفارش <span class="mono-code"><?= e($order['code']) ?></span></h1>
    <div class="page-header__sub">ثبت <?= jdt($order['placed_at'], 'Y/m/d', true) ?> • کانال: <?= e(\Core\Statuses::CHANNEL[$order['channel']] ?? $order['channel']) ?></div>
  </div>
  <div class="flex gap-2">
    <a class="btn btn--sm btn--outline no-print" target="_blank" href="<?= url('admin/orders/' . $order['id'] . '/print') ?>"><?= icon('print', '', 14) ?> چاپ فاکتور</a>
    <?= component('status-pill', ['group' => 'order', 'status' => $order['status'], 'size' => 'lg']) ?>
    <?php if ($invoice && !$invoice['paid']): ?>
      <form method="post" action="<?= url('admin/orders/' . $order['id'] . '/pay') ?>" data-confirm="پرداخت کامل این سفارش ثبت و فاکتور تسویه شود؟">
        <?= csrf_field() ?>
        <button class="btn btn--sm btn--success" type="submit"><?= icon('coin', '', 14) ?> ثبت دریافت وجه</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if (!in_array($order['status'], ['cancelled', 'returned'], true)): ?>
<div class="card mb-4"><?= component('progress-steps', ['steps' => $steps, 'labels' => $stepLabels, 'current' => $order['status']]) ?></div>
<?php endif; ?>

<div class="grid" style="grid-template-columns:1.6fr 1fr;gap:var(--space-5);align-items:start" id="order-detail-grid">
  <div class="stack">
    <!-- اقلام -->
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('box', '', 18) ?> اقلام سفارش</h3>
      <table class="table" style="min-width:0">
        <thead><tr><th>محصول</th><th>SKU</th><th>تعداد</th><th>فی</th><th>جمع</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td><?= e($item['title']) ?></td>
            <td><span class="code-chip" style="font-size:.7rem"><?= e($item['sku']) ?></span></td>
            <td class="num"><?= fa((string) $item['quantity']) ?></td>
            <td class="num"><?= toman((int) $item['unit_price'], false) ?></td>
            <td class="num"><b><?= toman((int) $item['total'], false) ?></b></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- سفارش‌های تولید متصل -->
    <?php if ($workOrders !== []): ?>
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('loom', '', 18) ?> سفارش‌های تولید متصل</h3>
      <?php foreach ($workOrders as $wo): ?>
        <div class="flex-between" style="padding:var(--space-2) 0;border-bottom:1px dashed var(--color-border)">
          <div>
            <a href="<?= url('admin/production/' . $wo['id']) ?>" class="mono-code fw-bold"><?= e($wo['code']) ?></a>
            <div class="text-sm text-muted"><?= e($wo['sku']) ?><?php if ($wo['weaver_name']): ?> — <?= e($wo['weaver_name']) ?> (<?= e($wo['weaver_code']) ?>)<?php endif; ?></div>
          </div>
          <?= component('status-pill', ['group' => 'work_order', 'status' => $wo['status']]) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- اقدامات -->
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('settings', '', 18) ?> تغییر وضعیت</h3>
      <?php if ($allowed === []): ?>
        <p class="text-sm text-muted m-0">این سفارش در وضعیت پایانی است؛ گذار دیگری مجاز نیست.</p>
      <?php else: ?>
      <form method="post" action="<?= url('admin/orders/' . $order['id'] . '/status') ?>" class="flex gap-2" style="flex-wrap:wrap">
        <?= csrf_field() ?>
        <select class="form-control" name="status" style="width:auto;min-width:180px">
          <?php foreach ($allowed as $key): ?>
            <option value="<?= e($key) ?>"><?= e($statuses[$key]['label'] ?? $key) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn--primary btn--sm" type="submit">اعمال وضعیت</button>
      </form>
      <p class="form-hint mt-2 mb-0">فقط گذارهای مجازِ ماشین حالت سفارش نمایش داده می‌شوند.</p>
      <?php endif; ?>

      <?php if ($order['status'] === 'ready'): ?>
        <hr style="border:none;border-top:1px dashed var(--color-border);margin:var(--space-4) 0">
        <h3 class="card__title mb-3"><?= icon('truck', '', 18) ?> ثبت محمولهٔ ارسال</h3>
        <form method="post" action="<?= url('admin/orders/' . $order['id'] . '/ship') ?>" class="stack">
          <?= csrf_field() ?>
          <div class="form-row">
            <div class="form-group m-0">
              <label class="form-label">شرکت حمل <span class="required">*</span></label>
              <input class="form-control" name="carrier" required placeholder="پست پیشتاز / تیپاکس / باربری">
            </div>
            <div class="form-group m-0">
              <label class="form-label">کد رهگیری</label>
              <input class="form-control ltr" name="tracking_no">
            </div>
          </div>
          <div class="form-group m-0" style="max-width:220px">
            <label class="form-label">هزینهٔ ارسال (تومان)</label>
            <input class="form-control num" name="cost" type="number" min="0" value="0">
          </div>
          <button class="btn btn--secondary btn--sm" type="submit"><?= icon('truck', '', 14) ?> ارسال و صدور بارنامه</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- ستون کنار -->
  <div class="stack">
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('user', '', 18) ?> گیرنده</h3>
      <dl class="spec-list">
        <dt>نام</dt><dd><?= e($order['receiver']) ?></dd>
        <dt>موبایل</dt><dd class="num ltr"><?= fa($order['phone']) ?></dd>
        <?php if ($customer): ?>
          <dt>کد مشتری</dt><dd><a class="mono-code" href="<?= url('admin/customers/' . $customer['id']) ?>"><?= e($customer['code']) ?></a></dd>
        <?php endif; ?>
        <?php if ($agency): ?>
          <dt>نمایندگی</dt><dd><a href="<?= url('admin/agencies/' . $agency['id']) ?>"><?= e($agency['name']) ?> (<?= e($agency['code']) ?>)</a></dd>
        <?php endif; ?>
        <dt>نشانی</dt><dd class="text-sm" style="font-weight:400"><?= e(trim(($order['province'] ?? '') . ' ' . ($order['city'] ?? '') . ' ' . ($order['address'] ?? ''))) ?></dd>
        <?php if (!empty($order['postal_code'])): ?><dt>کد پستی</dt><dd class="num ltr"><?= e($order['postal_code']) ?></dd><?php endif; ?>
        <?php if (!empty($order['note'])): ?><dt>یادداشت</dt><dd class="text-sm" style="font-weight:400"><?= e($order['note']) ?></dd><?php endif; ?>
      </dl>
    </div>

    <div class="card">
      <h3 class="card__title mb-3"><?= icon('coin', '', 18) ?> مالی</h3>
      <dl class="spec-list">
        <dt>جمع اقلام</dt><dd class="num"><?= toman((int) $order['subtotal']) ?></dd>
        <?php if ((int) $order['discount'] > 0): ?><dt>تخفیف</dt><dd class="num text-success">− <?= toman((int) $order['discount']) ?></dd><?php endif; ?>
        <dt>ارسال</dt><dd class="num"><?= (int) $order['shipping_cost'] === 0 ? 'رایگان' : toman((int) $order['shipping_cost']) ?></dd>
        <dt>مبلغ کل</dt><dd class="num text-primary-brand"><b><?= toman((int) $order['total']) ?></b></dd>
        <?php if ($invoice): ?>
          <dt>فاکتور</dt><dd><span class="code-chip"><?= e($invoice['code']) ?></span> <?= $invoice['paid'] ? '<span class="badge badge--success">تسویه</span>' : '<span class="badge badge--warning">باز</span>' ?></dd>
        <?php endif; ?>
      </dl>
      <?php if ($receipts !== []): ?>
        <div class="mt-3">
          <div class="text-sm fw-bold mb-2">رسیدها</div>
          <?php foreach ($receipts as $r): ?>
            <div class="flex-between text-sm" style="padding:4px 0">
              <span class="code-chip"><?= e($r['code']) ?></span>
              <span class="num"><?= toman((int) $r['amount'], false) ?></span>
              <span class="text-muted"><?= jdt($r['received_at']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($audits)): ?>
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('clock', '', 18) ?> تاریخچهٔ تغییرات</h3>
      <?php foreach ($audits as $a): ?>
        <div class="text-sm" style="padding:6px 0;border-bottom:1px dashed var(--color-border)">
          <b><?= e($a['field']) ?></b>:
          <span class="text-danger"><?= e((string) $a['old_value']) ?></span> ←
          <span class="text-success"><?= e((string) $a['new_value']) ?></span>
          <div class="text-muted" style="font-size:.72rem"><?= e((string) ($a['user_name'] ?? 'سامانه')) ?> — <?= jrelative($a['created_at']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($shipment): ?>
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('truck', '', 18) ?> محموله</h3>
      <dl class="spec-list">
        <dt>بارنامه</dt><dd class="mono-code"><?= e($shipment['code']) ?></dd>
        <dt>حمل</dt><dd><?= e($shipment['carrier']) ?></dd>
        <?php if (!empty($shipment['tracking_no'])): ?><dt>رهگیری</dt><dd class="mono-code"><?= e($shipment['tracking_no']) ?></dd><?php endif; ?>
        <dt>وضعیت</dt><dd><?= component('status-pill', ['group' => 'shipment', 'status' => $shipment['status']]) ?></dd>
      </dl>
    </div>
    <?php endif; ?>
  </div>
</div>
<style>@media (max-width:992px){#order-detail-grid{grid-template-columns:1fr !important}}</style>
