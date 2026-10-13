<?php $title = 'بازارگاه فرت‌بافی'; ?>
<h1 class="mb-4 text-center">محصولات دست‌باف فرت</h1>
<div class="row g-4">
    <?php foreach ($products as $product): ?>
        <div class="col-md-4 col-lg-3">
            <?php include VIEWS_PATH . 'components/product-card.php'; ?>
        </div>
    <?php endforeach; ?>
</div>