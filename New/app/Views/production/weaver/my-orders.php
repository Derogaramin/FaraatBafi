<?php $title = 'سفارش‌های من'; ?>
<h2 class="mb-3">سفارش‌های من</h2>
<?php if (empty($orders)): ?>
    <div class="alert alert-info">شما در حال حاضر سفارش فعالی ندارید.</div>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <div class="card card-custom mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold"><?= $order->sku ?> (<?= $order->color_name ?> <?= $order->pattern_name ?>)</span>
                    <span class="badge bg-<?= $order->status == 'completed' ? 'success' : 'warning' ?>">
                        <?= $order->status ?>
                    </span>
                </div>
                <?php
                $steps = ['pending' => 'سفارش ثبت شد', 'materials_delivered' => 'مواد تحویل شد', 'weaving' => 'در دست بافت', 'completed' => 'تحویل انبار'];
                $currentStep = array_search($order->status, array_keys($steps));
                ?>
                <div class="progress-thread d-flex align-items-center justify-content-between position-relative mb-2">
                    <?php foreach ($steps as $key => $label): $i = array_search($key, array_keys($steps)); ?>
                        <div class="text-center position-relative" style="z-index:2;">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                 style="width:30px;height:30px;background:<?= $i <= $currentStep ? 'var(--color-primary)' : '#ddd' ?>;color:white;font-size:14px;">
                                <?php if ($i < $currentStep): ?><i class="fas fa-check"></i><?php else: ?><?= $i+1 ?><?php endif; ?>
                            </div>
                            <div class="small mt-1"><?= $label ?></div>
                        </div>
                        <?php if ($i < count($steps)-1): ?>
                            <div class="flex-grow-1 mx-1" style="height:3px;background:<?= $i < $currentStep ? 'var(--color-primary)' : '#ddd' ?>;"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if ($order->status == 'pending'): ?>
                        <button class="btn btn-primary btn-lg finger-btn w-100-sm update-status" data-id="<?= $order->id ?>" data-status="materials_delivered">دریافت مواد اولیه</button>
                    <?php elseif ($order->status == 'materials_delivered'): ?>
                        <button class="btn btn-primary btn-lg finger-btn w-100-sm update-status" data-id="<?= $order->id ?>" data-status="weaving">شروع بافت</button>
                    <?php elseif ($order->status == 'weaving'): ?>
                        <button class="btn btn-success btn-lg finger-btn w-100-sm update-status" data-id="<?= $order->id ?>" data-status="completed">تحویل به انبار</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>