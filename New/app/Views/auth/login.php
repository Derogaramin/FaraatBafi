<?php $title = 'ورود'; ?>
<div class="card card-custom p-4">
    <h3 class="text-center mb-4">ورود به فرت‌بافی</h3>
    <form method="post" action="<?= url('/login') ?>">
        <div class="mb-3">
            <label class="form-label">شماره موبایل</label>
            <input type="text" name="mobile" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">رمز عبور</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">ورود</button>
    </form>
</div>