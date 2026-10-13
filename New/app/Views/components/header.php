<nav class="navbar navbar-expand-lg bg-linen">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('/') ?>">
            <img src="<?= asset('images/logo.svg') ?>" alt="فرت" height="40">
            فرت‌بافی
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>">بازارگاه</a></li>
                <li class="nav-item"><a class="nav-link" href="#">وبلاگ</a></li>
            </ul>
            <div class="d-flex gap-2">
                <?php if ($auth = \Core\Application::$app->auth): ?>
                    <?php if ($auth->check()): ?>
                        <a href="<?= url('/dashboard') ?>" class="btn btn-outline-primary btn-sm">پنل من</a>
                        <a href="<?= url('/logout') ?>" class="btn btn-outline-secondary btn-sm">خروج</a>
                    <?php else: ?>
                        <a href="<?= url('/login') ?>" class="btn btn-primary btn-sm">ورود</a>
                        <a href="<?= url('/register') ?>" class="btn btn-outline-primary btn-sm">ثبت‌نام</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="<?= url('/cart') ?>" class="btn btn-outline-crimson btn-sm position-relative">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-crimson">0</span>
                </a>
            </div>
        </div>
    </div>
</nav>