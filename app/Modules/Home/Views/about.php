<?php
/** ویوی دربارهٔ ما — Home::about */
$this->extend('layouts.public');
?>
<section class="container-narrow" style="padding:var(--space-7) 0">
  <div class="breadcrumb"><a href="<?= url('/') ?>">خانه</a> <span>/</span> <span>دربارهٔ ما</span></div>
  <div class="card card--pattern">
    <h1>تعاونی تولیدی صنایع دستی نقشینه بافت تربت جام</h1>
    <p class="text-lg text-secondary">
      «فرت» در گویش خراسانی به پارچهٔ دست‌بافِ پنبه‌ای می‌گویند؛ همان بافتی که بیش از پانزده سال است
      در کارگاه‌های تربت جام روی چله می‌نشیند و با دستان هنرمند بافندگان محلی جان می‌گیرد.
    </p>

    <h2 class="mt-5">داستان ما</h2>
    <p>
      تعاونی نقشینه بافت با هدف احیای نساجی سنتی و ایجاد اشتغال پایدار برای بافندان منطقه تأسیس شد.
      امروز حدود <b>۱۰۰ بافندهٔ فعال</b>، <b>۱۰ عضو اصلی</b>، <b>۵ نمایندگی فروش</b> و بیش از
      <b>۲۰۰ مشتری همیشگی</b> در کنار ما هستند. حوله، دستمال، شال، پوشاک و پارچه‌های فرت با
      رنگ‌های طبیعی (روناس، نیل، پوست پیاز) و نقش‌مایه‌های هندسی خراسان بافته می‌شوند.
    </p>

    <h2 class="mt-5">اصول بافت در فرت</h2>
    <div class="grid grid-2 mt-3">
      <div class="card">
        <h3 class="text-primary-brand"><?= icon('thread', '', 20) ?> چله و پود</h3>
        <p class="text-sm text-secondary m-0">تراکم چله (مثلاً ۱۲٫۵) و پودگذاری (یک‌لا/دولا) در کد فنی هر محصول ثبت می‌شود تا اصالت بافت قابل راستی‌آزمایی باشد.</p>
      </div>
      <div class="card">
        <h3 class="text-primary-brand"><?= icon('palette', '', 20) ?> رنگ‌های طبیعی</h3>
        <p class="text-sm text-secondary m-0">رنگرزی با مواد گیاهی و معدنی؛ کد هر رنگ (مثل R01 قرمز روناس) در کتابخانهٔ رنگ تعاونی ثبت شده است.</p>
      </div>
      <div class="card">
        <h3 class="text-primary-brand"><?= icon('quality', '', 20) ?> بازرسی کیفیت</h3>
        <p class="text-sm text-secondary m-0">هر بافت پیش از ورود به انبار بازرسی و امتیازدهی می‌شود؛ عیب‌ها با کدهای استاندارد (DEF) ثبت می‌گردند.</p>
      </div>
      <div class="card">
        <h3 class="text-primary-brand"><?= icon('handshake', '', 20) ?> دستمزد عادلانه</h3>
        <p class="text-sm text-secondary m-0">دستمزد هر بافنده بر پایهٔ نرخ توافقی و کیفیت بافت، با فیش‌های شفاف (PAY) پرداخت می‌شود.</p>
      </div>
    </div>

    <div class="card mt-5" style="background:var(--color-secondary-light);border-color:transparent">
      <h3><?= icon('qr', '', 18) ?> نظام کدگذاری یکپارچهٔ فرت (FUCS)</h3>
      <p class="text-sm m-0">
        همهٔ موجودیت‌های سامانه — از بافنده (W045) تا سفارش (ORD-1403-000125)، مدل فنی (HT-2LBSTD-CH125)،
        SKU (HT-2LBSTD-CH125-R01-P0003) و کد اصالت (FB-1403-0521-W023) — بر پایهٔ نظام کدگذاری یکپارچهٔ
        فرت تولید و با الگوهای مرجع اعتبارسنجی می‌شوند.
      </p>
    </div>
  </div>
</section>
