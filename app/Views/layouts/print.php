<?php
/**
 * لایهٔ چاپ — مینیمال، سیاه‌وسفید، با سربرگ تعاونی
 * متغیرها: $content, $title, $docCode
 */
use App\Modules\Admin\Models\Setting;

$coopName = Setting::get('coop_name', 'تعاونی تولیدی صنایع دستی نقشینه بافت تربت جام');
$brand = Setting::get('brand_name', 'فرت');
$phone = Setting::get('phone', '051-34567890');
$address = Setting::get('address', 'خراسان رضوی، تربت جام');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<title><?= e(($title ?? 'چاپ') . ' — ' . $brand) ?></title>
<link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
<link rel="stylesheet" href="<?= asset('css/main.css') ?>">
<link rel="stylesheet" href="<?= asset('css/print-doc.css') ?>">
</head>
<body class="print-body">
<div class="print-toolbar no-print">
  <button class="btn btn--primary btn--sm" onclick="window.print()">چاپ سند</button>
  <a class="btn btn--outline btn--sm" href="javascript:history.back()">بازگشت</a>
</div>

<div class="print-sheet">
  <header class="print-letterhead">
    <div class="print-logo"><?= icon('shuttle', '', 30) ?></div>
    <div>
      <h1><?= e($coopName) ?></h1>
      <div class="print-letterhead__meta">
        برند «<?= e($brand) ?>» — <?= e($address) ?> — تلفن: <span class="ltr"><?= e($phone) ?></span>
      </div>
    </div>
    <div class="print-doc-badge">
      <b><?= e($title ?? '') ?></b>
      <?php if (!empty($docCode)): ?><span class="ltr"><?= e($docCode) ?></span><?php endif; ?>
    </div>
  </header>

<?= $content ?>

  <footer class="print-footer">
    <span>سامانهٔ جامع مدیریت تعاونی فرت‌بافی — نسخهٔ <?= e((string) \Core\Config::get('app.version')) ?></span>
    <span>تاریخ چاپ: <?= e(\Core\Jalali::format('Y/m/d — H:i')) ?></span>
  </footer>
</div>
<script>window.addEventListener('afterprint', function(){ /* پس از چاپ در همان صفحه می‌مانیم */ });</script>
</body>
</html>
