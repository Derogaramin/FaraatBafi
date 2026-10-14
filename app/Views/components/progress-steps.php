<?php
/**
 * کامپوننت نوار پیشرفت چندمرحله‌ای — ردیابی بافت/سفارش
 * داده‌ها:
 *   steps:   [کلید وضعیت‌ها به‌ترتیب]
 *   labels:  [برچسب فارسی هر مرحله]
 *   current: وضعیت جاری
 *   rejected: (bool) آیا در وضعیت رد کیفیت هستیم
 */
$steps = $steps ?? \Core\Statuses::WORK_ORDER_STEPS;
$labels = $labels ?? [];
$current = $current ?? '';
$rejected = $rejected ?? false;
$currentIndex = array_search($current, $steps, true);
?>
<div class="steps" role="list">
  <?php foreach ($steps as $i => $step): ?>
    <?php
      $cls = '';
      if ($rejected && $i === max(0, $currentIndex)) {
          $cls = 'is-rejected';
      } elseif ($currentIndex !== false && $i < $currentIndex) {
          $cls = 'is-done';
      } elseif ($i === $currentIndex) {
          $cls = 'is-current';
      }
      $label = $labels[$step] ?? \Core\Statuses::label('work_order', $step);
    ?>
    <div class="step <?= $cls ?>" role="listitem">
      <span class="step__dot"><?= $cls === 'is-done' ? icon('check', '', 15) : fa((string) ($i + 1)) ?></span>
      <span class="step__label"><?= e($label) ?></span>
    </div>
  <?php endforeach; ?>
</div>
