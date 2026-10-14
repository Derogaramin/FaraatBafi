<?php
/**
 * کامپوننت toasts — تبدیل فلش‌های سرور به Toast کلاینت
 * داده‌ها: flashes (نوع → متن), errors (فیلد → خطا)
 */
$flashes = $flashes ?? [];
$errors = $errors ?? [];
foreach ($flashes as $type => $message): ?>
<div data-flash="<?= e($type) ?>" data-flash-message="<?= e($message) ?>"></div>
<?php endforeach; ?>
<?php if ($errors !== []): ?>
<div data-flash="error" data-flash-message="<?= e(reset($errors)) ?>"></div>
<?php endif; ?>
