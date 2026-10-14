<?php
/**
 * کامپوننت کارت KPI داشبورد
 * داده‌ها: label, value, icon, color (primary|secondary|success|warning|action|gold), hint, url?
 */
$color = $color ?? 'primary';
?>
<a class="kpi kpi--<?= e($color) ?>" <?= !empty($url) ? 'href="' . e($url) . '"' : '' ?> style="<?= empty($url) ? 'cursor:default' : 'text-decoration:none;color:inherit' ?>">
  <div class="kpi__label"><?= icon($icon ?? 'chart', '', 16) ?> <?= e($label) ?></div>
  <div class="kpi__value"><?= $value ?></div>
  <?php if (!empty($hint)): ?><div class="kpi__hint"><?= $hint ?></div><?php endif; ?>
</a>
