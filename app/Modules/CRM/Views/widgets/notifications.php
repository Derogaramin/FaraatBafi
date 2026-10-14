<?php
/**
 * ویجت زنگولهٔ اعلان‌ها — CRM::widgets.notifications
 * توسط Modules::run('CRM','NotificationController','widget') در لایهٔ پنل رندر می‌شود (HMVC)
 */
?>
<div class="notif-bell">
  <button class="theme-toggle" data-dropdown="#notif-pop" aria-label="اعلان‌ها" style="position:relative">
    <?= icon('bell', '', 17) ?>
    <?php if ($count > 0): ?><span class="notif-bell__badge"><?= fa((string) min(99, $count)) ?></span><?php endif; ?>
  </button>
  <div class="notif-popover" id="notif-pop">
    <div class="notif-popover__head">
      <span>اعلان‌ها <?= $count > 0 ? '(' . fa((string) $count) . ')' : '' ?></span>
      <?php if ($count > 0): ?>
        <form method="post" action="<?= url('notifications/read-all') ?>" class="m-0">
          <?= csrf_field() ?>
          <button class="btn btn--sm btn--ghost" type="submit">خواندن همه</button>
        </form>
      <?php endif; ?>
    </div>
    <div class="notif-list">
      <?php if ($items === []): ?>
        <div class="empty-state p-4"><span class="text-sm">اعلان خوانده‌نشده‌ای ندارید 🌿</span></div>
      <?php else: foreach ($items as $n): ?>
        <a class="notif-item notif-item--<?= e($n['kind']) ?>" href="<?= e($n['link'] ?? url('notifications')) ?>">
          <span class="notif-item__icon"><?= icon(match ($n['kind']) { 'success' => 'check', 'warning' => 'alert', 'danger' => 'x', default => 'info' }, '', 16) ?></span>
          <span style="flex:1">
            <span class="notif-item__title"><?= e($n['title']) ?></span>
            <?php if (!empty($n['body'])): ?><span class="notif-item__body"><?= e(mb_substr((string) $n['body'], 0, 90)) ?>…</span><?php endif; ?>
            <span class="notif-item__time"><?= jrelative($n['created_at']) ?></span>
          </span>
        </a>
      <?php endforeach; endif; ?>
    </div>
    <a class="notif-item text-center text-sm" href="<?= url('notifications') ?>" style="justify-content:center;font-weight:700">همهٔ اعلان‌ها</a>
  </div>
</div>
