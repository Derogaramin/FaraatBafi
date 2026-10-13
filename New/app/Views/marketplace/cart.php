<?php $title = 'سبد خرید'; ?>
<h2>سبد خرید</h2>
<div id="cart-container">
    <!-- dynamic via JS -->
    <p class="text-muted">سبد خرید شما خالی است.</p>
</div>
<div class="text-end mt-3" id="cart-total" style="display:none;">
    <h4>جمع کل: <span id="total-amount">0</span> تومان</h4>
    <a href="<?= url('/checkout') ?>" class="btn btn-crimson btn-lg">تسویه و ثبت سفارش</a>
</div>