<?php
/** ویوی فروش حضوری (POS) — Agency::pos */
$this->extend('layouts.panel', ['panel' => 'agency', 'panelTitle' => 'نمایندگی ' . ($agency['code'] ?? '')]);
$items = $cart['items'];
?>
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= url('agency') ?>">داشبورد</a> <span>/</span> <span>فروش حضوری</span></div>
    <h1><?= icon('barcode', '', 22) ?> فروش حضوری (POS)</h1>
    <div class="page-header__sub">بارکد یا SKU محصول را اسکن/وارد کنید و Enter بزنید</div>
  </div>
</div>

<div class="pos-layout">
  <div class="stack">
    <div class="pos-scan">
      <?= icon('barcode', '', 28) ?>
      <input class="form-control" data-pos-input="<?= url('agency/pos/add') ?>" placeholder="HT-2LBSTD-CH125-R01-P0003" dir="ltr" autofocus>
      <span class="text-sm text-muted">Enter = افزودن</span>
    </div>

    <div class="card">
      <h3 class="card__title mb-3">سبد فروش (<?= fa((string) $cart['count']) ?> قلم)</h3>
      <?php if ($items === []): ?>
        <p class="text-sm text-muted m-0">سبد خالی است — SKU را اسکن کنید.</p>
      <?php else: ?>
        <table class="table" style="min-width:0">
          <thead><tr><th>محصول</th><th>SKU</th><th>تعداد</th><th>فی</th><th>جمع</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
              <td><?= e($it['product_name']) ?></td>
              <td><span class="code-chip" style="font-size:.62rem"><?= e($it['sku']) ?></span></td>
              <td class="num"><?= fa((string) $it['qty']) ?></td>
              <td class="num text-sm"><?= toman((int) $it['price'], false) ?></td>
              <td class="num"><b><?= toman((int) $it['line_total'], false) ?></b></td>
              <td>
                <form method="post" action="<?= url('cart/remove') ?>" class="m-0">
                  <?= csrf_field() ?><input type="hidden" name="sku" value="<?= e($it['sku']) ?>">
                  <button class="btn btn--sm btn--ghost text-danger" type="submit"><?= icon('x', '', 13) ?></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <?php if ($consignment !== []): ?>
    <div class="card">
      <h3 class="card__title mb-3"><?= icon('warehouse', '', 18) ?> کالای امانی شما (برای انتخاب سریع)</h3>
      <div class="chip-row">
        <?php foreach ($consignment as $c): ?>
          <button class="chip" type="button" data-quick-add="<?= e($c['sku']) ?>" <?= $c['quantity'] <= 0 ? 'disabled style="opacity:.45"' : '' ?>>
            <?= e($c['product_name']) ?> — <?= fa((string) $c['quantity']) ?> عدد
          </button>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="card" style="position:sticky;top:calc(var(--topbar-h) + 16px)">
    <h3 class="card__title mb-3">اطلاعات خریدار</h3>
    <form method="post" action="<?= url('agency/pos/sell') ?>" class="stack">
      <?= csrf_field() ?>
      <div class="form-group m-0">
        <label class="form-label">نام خریدار <span class="required">*</span></label>
        <input class="form-control" name="receiver" required placeholder="مشتری حضوری">
      </div>
      <div class="form-group m-0">
        <label class="form-label">موبایل <span class="required">*</span></label>
        <input class="form-control ltr" name="phone" inputmode="tel" required placeholder="09xxxxxxxxx">
      </div>
      <div class="form-group m-0">
        <label class="form-label">شهر</label>
        <input class="form-control" name="city" value="<?= e((string) ($agency['city'] ?? '')) ?>">
      </div>

      <div style="border-top:2px dashed var(--color-border);padding-top:var(--space-3)">
        <div class="flex-between mb-2"><span>جمع اقلام</span><b class="num"><?= toman((int) $cart['subtotal']) ?></b></div>
        <div class="flex-between mb-3 text-sm text-muted"><span>پورسانت شما (<?= fa((string) $agency['commission_rate']) ?>٪)</span><b class="num"><?= toman((int) round($cart['subtotal'] * ((float) $agency['commission_rate'] / 100))) ?></b></div>
        <div class="flex-between mb-4" style="border-top:1px solid var(--color-border);padding-top:var(--space-2)">
          <b>مبلغ قابل دریافت</b><b class="price text-lg"><?= toman((int) $cart['subtotal']) ?></b>
        </div>
      </div>

      <button class="btn btn--success btn--block btn--lg" type="submit" <?= $items === [] ? 'disabled' : '' ?>>
        <?= icon('check', '', 18) ?> ثبت فروش و صدور فاکتور
      </button>
      <?php if ($items !== []): ?>
      <a class="btn btn--ghost btn--block btn--sm" href="<?= url('agency/pos/clear') ?>" onclick="return confirm('سبد فروش خالی شود؟')">پاک‌کردن سبد</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('[data-quick-add]').forEach(function (chip) {
  chip.addEventListener('click', async function () {
    const res = await faraatPost('<?= url('agency/pos/add') ?>', { sku: chip.getAttribute('data-quick-add'), qty: 1 });
    if (res.json && res.json.ok) { location.reload(); }
    else { faraatToast((res.json && res.json.message) || 'خطا', 'error'); }
  });
});
</script>
