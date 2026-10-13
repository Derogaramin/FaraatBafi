<?php $title = 'داشبورد مدیریت'; ?>
<h2 class="mb-4">پیشخوان</h2>
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 text-center">
            <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
            <div class="fs-4 fw-bold"><?= $kpi['orders_today'] ?></div>
            <div class="text-muted small">سفارش‌های امروز</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 text-center">
            <i class="fas fa-chart-line fa-2x text-primary mb-2"></i>
            <div class="fs-4 fw-bold"><?= number_format($kpi['monthly_income']) ?> ت</div>
            <div class="text-muted small">درآمد این ماه</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 text-center">
            <i class="fas fa-user-check fa-2x text-primary mb-2"></i>
            <div class="fs-4 fw-bold"><?= $kpi['active_weavers'] ?></div>
            <div class="text-muted small">بافندگان فعال</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 text-center">
            <i class="fas fa-microscope fa-2x text-warning mb-2"></i>
            <div class="fs-4 fw-bold"><?= $kpi['pending_qc'] ?></div>
            <div class="text-muted small">نیازمند بازرسی</div>
        </div>
    </div>
</div>

<div class="card card-custom p-3">
    <h5>فروش ۷ روز اخیر</h5>
    <canvas id="salesChart" height="100"></canvas>
</div>
<script>
// استفاده از Chart.js برای نمودار (لوکال)
</script>