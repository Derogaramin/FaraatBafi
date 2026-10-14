<?php
/**
 * دانه‌گذار ۰۵ — محتوا و پلتفرم: وبلاگ، تیکت‌ها، اعلان‌ها،
 * جلسات/مصوبات، رأی‌گیری، مکاتبات و تنظیمات
 */

declare(strict_types=1);

use App\Modules\Admin\Models\InternalLetter;
use App\Modules\Admin\Models\Meeting;
use App\Modules\Admin\Models\Resolution;
use App\Modules\Admin\Models\Setting;
use App\Modules\Admin\Models\Vote;
use App\Modules\Blog\Models\Post;
use App\Modules\CRM\Models\Notification;
use App\Modules\CRM\Models\Ticket;
use App\Modules\CRM\Models\TicketReply;
use Core\Auth;
use Core\CodeGenerator;
use Core\Database;
use Core\Jalali;

return static function (): void {
    if ((int) (Database::selectOne('SELECT COUNT(*) AS c FROM posts')['c'] ?? 0) > 0) {
        echo "  (محتوا پیش‌تر دانه‌گذاری شده — رد شد)\n";
        return;
    }

    $year = Jalali::currentYear();
    $adminUser = Database::selectOne("SELECT id FROM users WHERE code = 'M001'");
    $staffUser = Database::selectOne("SELECT id FROM users WHERE code = 'S001'");
    $customerUser = Database::selectOne("SELECT id FROM users WHERE code LIKE 'C%' ORDER BY id LIMIT 1");
    $weaverUser = Database::selectOne("SELECT id FROM users WHERE code = 'W001'");

    /* ── نوشته‌های وبلاگ ── */
    $posts = [
        [
            'title' => 'روناس، نیل و پوست پیاز؛ رنگ‌های زندهٔ فرت',
            'slug' => 'natural-dyes-of-faraat',
            'category' => 'رنگرزی طبیعی',
            'excerpt' => 'در کارگاه رنگرزی تعاونی، ریشهٔ روناس پس از سه سال گیاه‌چینی، قرمزی عمیق به نخ می‌دهد که هیچ رنگ شیمیایی بازتولیدش نمی‌کند.',
            'body' => "رنگرزی طبیعی بخشی جدایی‌ناپذیر از هویت پارچه‌های فرت است. ریشهٔ روناس را اواخر پاییز از حاشیهٔ کشتزارها بیرون می‌آوریم، می‌شوییم، می‌کوبیم و در دیگ مسی با آب و دانه‌های انار می‌جوشانیم.\n\nنخ‌های پنبهٔ رنگ‌شده با روناس، پس از هر شست‌وشو زنده‌تر می‌شوند؛ چیزی که رنگ‌های شیمیایی هرگز به آن نمی‌رسند. نیل را از خمیر سنتی نیل‌سازان خراسان تهیه می‌کنیم؛ رنگی که در هوای آزاد «جان می‌گیرد» و از سبز به آبی عمیق می‌رسد.\n\nپوست پیاز، زردچوبه و پوست گردو نیز پالت گرم ما را کامل می‌کنند. هر رنگ در کتابخانهٔ رنگ تعاونی کد سه‌نویسه‌ای دارد (مثل R01 برای قرمز روناس) و در شناسنامهٔ هر محصول ثبت می‌شود تا مشتری بداند پارچه‌اش با چه گیاهی رنگ شده است.",
            'tags' => 'رنگرزی,روناس,نیل,خراسان',
            'days' => 12,
        ],
        [
            'title' => 'دار بافتی که سه نسل پای آن نشسته‌اند',
            'slug' => 'three-generations-at-the-loom',
            'category' => 'روایت کارگاه',
            'excerpt' => 'بی‌بی‌گل مرادی، استادکار تعاونی، از روزهایی می‌گوید که چله‌کشی را از مادربزرگش آموخت و حالا خودش شاگرد تربیت می‌کند.',
            'body' => "دارِ بی‌بی‌گل هنوز همان دارِ چوبی قدیمی است؛ فقط پایه‌هایش را تازه کرده‌ایم. «چله‌کشی را که یاد بگیری، نصف بافت را یاد گرفته‌ای» می‌گوید و شانه را محکم می‌کوبد تا پودها بنشینند.\n\nاو حالا یکی از ارشدترین بافندگان تعاونی است و هر هفته دو کارآموز جوان روستاهای اطراف پای دارش می‌نشینند. در سامانهٔ فرت، سطح مهارت هر بافنده از «کارآموز» تا «استادکار» ثبت می‌شود و نرخ دستمزد بر پایهٔ همین سطح و کیفیت بازرسی‌ها تعیین می‌گردد.\n\nامتیاز کیفیت هر بافنده میانگین نمرات بازرسی QC اوست؛ عددی شفاف که هم دستمزد عادلانه می‌سازد و هم انگیزهٔ ظرافت.",
            'tags' => 'بافنده,استادکار,دار بافت',
            'days' => 34,
        ],
        [
            'title' => 'کد اصالت FB چیست و چطور راستی‌آزمایی می‌شود؟',
            'slug' => 'what-is-fb-authenticity-code',
            'category' => 'راهنمای خرید',
            'excerpt' => 'هر دست‌بافتهٔ فرت یک شناسنامهٔ دیجیتال دارد: کدی که با پویش QR، شما را به صفحهٔ نام بافنده و مشخصات بافت می‌برد.',
            'body' => "روی برچسب هر محصول فرت، کدی مثل FB-1405-0012-W003 و یک QR می‌بینید. این «کد اصالت» است؛ یکتا، غیرقابل تکرار و متصل به شناسنامهٔ محصول در سامانهٔ تعاونی.\n\nبا پویش QR یا وارد کردن کد در صفحهٔ «استعلام اصالت»، نام بافنده، خاستگاه او، تاریخ بافت، مشخصات فنی (چله، پودگذاری، گره، دوخت)، رنگ و طرح محصول را می‌بینید.\n\nبخش اول کد سال شمسی تولید است، بخش دوم سری ساخت و بخش سوم کد بافنده (W###). اگر کدی در سامانه ثبت نشده باشد، صفحهٔ «اصالت تأیید نشد» نمایش داده می‌شود — یعنی آن محصول فرت اصل نیست.",
            'tags' => 'اصالت,QR,راهنما',
            'days' => 5,
        ],
        [
            'title' => 'چله و پود به زبان ساده؛ مشخصات فنی حولهٔ دست‌باف',
            'slug' => 'warp-and-weft-explained',
            'category' => 'آموزش بافت',
            'excerpt' => 'چله ۱۲٫۵ یعنی چه؟ پودگذاری دولا چه فرقی با یک‌لا دارد؟ کدهای فنی محصولات فرت را با هم بخوانیم.',
            'body' => "کد فنی هر محصول فرت پنج بخش دارد؛ مثلاً HT-2LBSTD-CH125 را بخوانیم:\n\n• HT = حولهٔ حمام\n• 2L = پودگذاری دولا (در هر ردیف دو پود عبور می‌کند؛ ضخامت و جذب آب بیشتر)\n• B = بی‌گره (بافت حوله‌ای بدون گرهٔ فرش‌گونه)\n• STD = دوخت استاندارد لبه\n• CH125 = چله ۱۲٫۵ (تعداد تارها در هر سانتی‌متر × ضریب تراکم)\n\nهرچه چله بیشتر باشد، بافت ریزتر و متراکم‌تر است. همین کد در SKU نهایی با رنگ و طرح ترکیب می‌شود؛ مثل HT-2LBSTD-CH125-R01-P0001 یعنی همان حوله با رنگ قرمز روناس و طرح ترنج.",
            'tags' => 'چله,پود,مشخصات فنی',
            'days' => 21,
        ],
    ];
    foreach ($posts as $i => $p) {
        Post::create([
            'title' => $p['title'], 'slug' => $p['slug'], 'excerpt' => $p['excerpt'],
            'body' => $p['body'], 'category' => $p['category'], 'tags' => $p['tags'],
            'author_id' => $staffUser['id'] ?? null,
            'status' => 'published', 'views' => random_int(40, 900),
            'published_at' => date('Y-m-d H:i:s', strtotime('-' . $p['days'] . ' days')),
        ]);
    }

    /* ── تیکت‌های نمونه ── */
    if ($customerUser) {
        $t1 = Ticket::create([
            'code' => CodeGenerator::ticketCode($year), 'user_id' => (int) $customerUser['id'],
            'subject' => 'زمان ارسال سفارش حولهٔ سه‌لا', 'body' => 'سلام، سفارش من با کد ORD چه زمانی ارسال می‌شود؟ برای هدیه لازم دارم و عجله دارم. ممنون از بافت زیبای شما.',
            'category' => 'order', 'priority' => 'high', 'status' => 'answered',
        ]);
        if ($staffUser) {
            TicketReply::create([
                'ticket_id' => (int) $t1->id(), 'user_id' => (int) $staffUser['id'],
                'body' => 'سلام و عرض ادب؛ سفارش شما در مرحلهٔ آماده‌سازی محموله است و حداکثر تا سه روز کاری با پست پیشتاز ارسال می‌شود. کد رهگیری در صفحهٔ پیگیری سفارش نمایش داده خواهد شد. 🌿',
                'is_staff' => 1, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 days')),
            ]);
        }
        Ticket::create([
            'code' => CodeGenerator::ticketCode($year), 'user_id' => (int) $customerUser['id'],
            'subject' => 'سؤال دربارهٔ شست‌وشوی شال پشمی', 'body' => 'شال گره‌دار خریدم؛ برای شست‌وشو چه دمایی مناسب است که رنگ نیلی‌اش پخش نشود؟',
            'category' => 'product', 'priority' => 'normal', 'status' => 'open',
        ]);
    }

    /* ── جلسات و مصوبات ── */
    $meeting = Meeting::create([
        'code' => CodeGenerator::meetingCode($year),
        'title' => 'جلسهٔ ماهانهٔ هیئت‌مدیره — مهر',
        'meeting_date' => date('Y-m-d', strtotime('-8 days')),
        'location' => 'دفتر مرکزی تعاونی، تربت جام',
        'agenda' => "۱. بررسی عملکرد فروش نیمهٔ اول سال\n۲. طرح خرید دو دستگاه بافت جدید\n۳. تسویهٔ معوقات دستمزد بافندگان\n۴. برنامهٔ نمایشگاه صنایع دستی مشهد",
        'minutes' => "جلسه با حضور هر سه عضو اصلی رأس ساعت ۱۶ تشکیل شد.\n\nپس از قرائت گزارش فروش، مقرر گردید دو دستگاه بافت نیمه‌صنعتی با اعتبار ۵۰۰ میلیون تومان خریداری شود (مصوبهٔ ۱).\n\nمعوقات دستمزد پنج بافنده تا پایان ماه تسویه گردد (مصوبهٔ ۲).\n\nغرفهٔ نمایشگاه مشهد با هماهنگی نمایندگی مرکز رزرو شد.",
        'status' => 'held', 'created_by' => $adminUser['id'] ?? null,
    ]);
    Resolution::create(['code' => sprintf('RES-%d-01-01', $year), 'meeting_id' => (int) $meeting->id(), 'sequence' => 1,
        'title' => 'خرید دو دستگاه بافت نیمه‌صنعتی', 'body' => 'با اختصاص ۵۰۰ میلیون تومان از محل سود انباشته موافقت شد.', 'status' => 'approved']);
    Resolution::create(['code' => sprintf('RES-%d-01-02', $year), 'meeting_id' => (int) $meeting->id(), 'sequence' => 2,
        'title' => 'تسویهٔ معوقات دستمزد', 'body' => 'دستمزد معوق پنج بافنده تا پایان مهر پرداخت شود.', 'status' => 'approved']);

    /* ── رأی‌گیری باز ── */
    Vote::create([
        'code' => CodeGenerator::voteCode($year),
        'title' => 'شرکت در نمایشگاه بین‌المللی صنایع دستی تهران — بهمن',
        'description' => 'هزینهٔ غرفه حدود ۱۲۰ میلیون تومان برآورد شده است. آیا تعاونی در نمایشگاه شرکت کند؟',
        'options' => json_encode(['موافق — با غرفهٔ ۲۴ متری', 'موافق — با غرفهٔ اشتراکی ۱۲ متری', 'مخالف — تمرکز بر فروش آنلاین'], JSON_UNESCAPED_UNICODE),
        'starts_at' => date('Y-m-d H:i:s'),
        'ends_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
        'status' => 'open',
    ]);

    /* ── مکاتبات داخلی ── */
    if ($adminUser && $staffUser) {
        InternalLetter::create([
            'code' => CodeGenerator::letterCode($year),
            'from_user_id' => (int) $adminUser['id'], 'to_user_id' => (int) $staffUser['id'],
            'subject' => 'گزارش عملکرد فروش نیم‌سال',
            'body' => "سرکار خانم کریمی\nلطفاً گزارش عملکرد فروش و پورسانت نمایندگی‌های پنج‌گانه را تا پایان هفته برای ارائه به هیئت‌مدیره آماده فرمایید.\nبا تشکر — م. دروگر",
            'kind' => 'letter', 'sent_at' => date('Y-m-d H:i:s', strtotime('-6 days')),
        ]);
    }

    /* ── اعلان‌های نمونه ── */
    if ($weaverUser) {
        Notification::push((int) $weaverUser['id'], 'به پنل بافندگان فرت خوش آمدید',
            'سفارش‌های بافت، مواد دریافتی و دستمزدها همیشه اینجا در دسترس شماست.', url('weaver'), 'success');
    }
    if ($adminUser) {
        Notification::push((int) $adminUser['id'], 'یک بافت تحویل انبار شد',
            'برای ثبت بازرسی کیفیت به بخش تولید مراجعه کنید.', url('admin/production?status=delivered'), 'warning');
    }

    /* ── تنظیمات تعاونی ── */
    Setting::put('coop_name', 'تعاونی تولیدی صنایع دستی نقشینه بافت تربت جام');
    Setting::put('brand_name', 'فرت');
    Setting::put('phone', '051-34567890');
    Setting::put('address', 'خراسان رضوی، تربت جام، بلوار امام خمینی، کارگاه نقشینه بافت');
    Setting::put('shipping_flat', '85000');
    Setting::put('free_shipping', '3000000');

    echo "  → وبلاگ (۴ نوشته)، تیکت‌ها، جلسه و مصوبات، رأی‌گیری باز، مکاتبات، اعلان‌ها و تنظیمات\n";
};
