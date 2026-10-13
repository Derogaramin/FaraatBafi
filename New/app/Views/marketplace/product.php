<?php $title = 'محصول ' . $product->sku; ?>
<div class="row">
    <div class="col-md-6">
        <div class="bg-linen rounded-3 p-4 mb-3">
            <img src="<?= asset('uploads/products/' . $product->sku . '.jpg') ?>"
                 class="img-fluid rounded" alt="<?= $product->sku ?>">
        </div>
    </div>
    <div class="col-md-6">
        <h2><?= $product->type_code ?> <?= $product->color_name ?> <?= $product->pattern_name ?></h2>
        <p class="text-muted">کد محصول: <strong><?= $product->sku ?></strong></p>

        <div class="card card-custom p-3 mb-3">
            <h5 class="h6">مشخصات فنی:</h5>
            <ul class="list-unstyled mb-0 small">
                <li>نوع بافت: <?= $product->weave ?> (<?= $product->weave == '1L' ? 'تک‌لا' : ($product->weave == '2L' ? 'دولا' : 'سه‌لا') ?>)</li>
                <li>گره: <?= $product->knot == 'G' ? 'گره‌دار' : 'بی‌گره' ?></li>
                <li>نوع دوخت: <?= $product->stitch ?></li>
                <li>چله: <?= $product->getChalehDisplay() ?></li>
                <li>وزن تقریبی: <?= $product->base_weight_grams ?> گرم</li>
                <li>عرض: <?= $product->width_cm ?> سانتی‌متر</li>
            </ul>
        </div>

        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="fs-4 fw-bold text-primary"><?= number_format($product->finalPrice()) ?> تومان</span>
            <?php if ($product->stock_quantity > 0): ?>
                <span class="badge bg-success">موجود در انبار</span>
            <?php else: ?>
                <span class="badge bg-danger">ناموجود</span>
            <?php endif; ?>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-lg add-to-cart" data-sku="<?= $product->sku ?>">
                <i class="fas fa-cart-plus ms-1"></i> افزودن به سبد خرید
            </button>
            <button class="btn btn-outline-crimson btn-lg">
                <i class="fas fa-qrcode ms-1"></i> کد اصالت
            </button>
        </div>
    </div>
</div>