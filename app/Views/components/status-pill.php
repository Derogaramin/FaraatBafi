<?php
/**
 * کامپوننت status-pill — نشان وضعیت یکپارچه
 * داده‌ها: ['group' => 'order', 'status' => 'pending'] یا ['label' => '...', 'color' => 'success']
 */
$label = $label ?? \Core\Statuses::label($group ?? '', (string) $status);
$color = $color ?? \Core\Statuses::color($group ?? '', (string) $status);
$size = ($size ?? '') === 'lg' ? ' badge--lg' : '';
?>
<span class="badge badge--<?= e($color) ?><?= $size ?> badge-dot"><?= e($label) ?></span>
