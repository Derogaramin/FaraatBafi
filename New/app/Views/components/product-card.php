<?php
// expects $product variable (ProductVariant instance)
$finalPrice = $product->finalPrice();
?>
<div class="card card-custom h-100">
    <img src="<?= asset('uploads/products/' . ($product->sku ?? 'placeholder') . '.jpg') ?>"
         class="card-img-top" alt="<?= $product->sku ?>" style="height: 200px; object-fit: cover;">
    <div class="card-body d-flex flex-column">
        <h5 class="card-title h6"><?= $product->color_name ?? '' ?> <?= $product->pattern_name ?? '' ?></h5>
        <p class="card-text small text-muted mb-2">
            <?= $product->type_code ?> | چله <?= $product->getChalehDisplay() ?>
        </p>
        <p class="fw-bold text-primary mt-auto"><?= number_format($finalPrice) ?> تومان</p>
        <a href="<?= url('/product/' . $product->sku) ?>" class="btn btn-sm btn-outline-primary mt-2">مشاهده</a>
    </div>
</div>