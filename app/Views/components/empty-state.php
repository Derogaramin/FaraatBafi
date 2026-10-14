<?php
/**
 * کامپوننت وضعیت خالی
 * داده‌ها: icon, title, message, action_url?, action_label?
 */
?>
<div class="empty-state">
  <?= icon($icon ?? 'box', '', 56) ?>
  <h3><?= e($title ?? 'موردی یافت نشد') ?></h3>
  <?php if (!empty($message)): ?><p class="text-sm"><?= e($message) ?></p><?php endif; ?>
  <?php if (!empty($action_url)): ?>
    <a class="btn btn--primary btn--sm mt-3" href="<?= e($action_url) ?>"><?= icon('plus', '', 16) ?> <?= e($action_label ?? 'افزودن') ?></a>
  <?php endif; ?>
</div>
