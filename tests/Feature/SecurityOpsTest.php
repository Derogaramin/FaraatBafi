<?php
/**
 * تست ویژگی — امنیت عملیاتی: Throttle، صف رویداد، ETag، رد SVG، جست‌وجو
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Marketplace\Services\CartService;
use Core\App;
use Core\Audit;
use Core\Auth;
use Core\Database;
use Core\Events;
use Core\QueueWorker;
use Core\Request;
use Core\Session;
use Core\Throttle;
use Core\Upload;
use PHPUnit\Framework\TestCase;

final class SecurityOpsTest extends TestCase
{
    protected function tearDown(): void
    {
        Auth::logout();
        Auth::resetResolution();
        Session::destroy();
        Events::flush();
        unset($_SERVER['HTTP_IF_NONE_MATCH']);
    }

    /* ── محدودسازی نرخ ورود ── */

    public function test_throttle_locks_after_five_failures(): void
    {
        $ident = 'locked-user-' . uniqid();
        $this->assertSame(0, Throttle::lockedSeconds($ident, '10.9.8.7'));

        for ($i = 0; $i < 5; $i++) {
            Throttle::hit($ident, '10.9.8.7', false);
        }
        $locked = Throttle::lockedSeconds($ident, '10.9.8.7');
        $this->assertGreaterThan(0, $locked);
        $this->assertLessThanOrEqual(900, $locked);
        $this->assertSame(0, Throttle::attemptsLeft($ident, '10.9.8.7'));
        $this->assertStringContainsString('مسدود', Throttle::lockMessage($locked));
    }

    public function test_successful_login_clears_failures(): void
    {
        $ident = 'recover-user-' . uniqid();
        Throttle::hit($ident, '10.9.8.6', false);
        Throttle::hit($ident, '10.9.8.6', false);
        Throttle::hit($ident, '10.9.8.6', true); // ورود موفق
        $this->assertSame(0, Throttle::lockedSeconds($ident, '10.9.8.6'));
        $this->assertSame(5, Throttle::attemptsLeft($ident, '10.9.8.6'));
    }

    public function test_login_endpoint_returns_lock_message_via_http(): void
    {
        $ident = 'http-lock-' . uniqid();
        for ($i = 0; $i < 5; $i++) {
            Throttle::hit($ident, '10.7.7.7', false);
        }
        $token = Session::csrfToken();
        $response = App::handle(Request::create('POST', '/login', [
            '_token' => $token, 'identifier' => $ident, 'password' => 'whatever',
        ]));
        $this->assertSame(302, $response->status());
        $flash = Session::pullFlashes();
        $this->assertStringContainsString('مسدود', $flash['error'] ?? '');
    }

    /* ── صف رویدادها ── */

    public function test_event_queue_roundtrip(): void
    {
        $hits = [];
        Events::on('test.queued', function (array $payload) use (&$hits): void { $hits[] = $payload['v']; });

        Events::queue('test.queued', ['v' => 42]);
        $row = Database::selectOne("SELECT * FROM event_queue WHERE event = 'test.queued' AND status = 'pending' ORDER BY id DESC LIMIT 1");
        $this->assertNotNull($row);

        $processed = (new QueueWorker())->runBatch(10);
        $this->assertGreaterThanOrEqual(1, $processed);
        $this->assertSame([42], $hits);

        $done = Database::selectOne('SELECT status FROM event_queue WHERE id = ?', [$row['id']]);
        $this->assertSame('done', $done['status']);
    }

    public function test_queue_failure_retries_then_fails(): void
    {
        Events::on('test.boom', function (): void { throw new \RuntimeException('boom'); });
        Events::queue('test.boom', []);
        $row = Database::selectOne("SELECT * FROM event_queue WHERE event = 'test.boom' AND status = 'pending' ORDER BY id DESC LIMIT 1");

        (new QueueWorker())->runBatch(5); // تلاش ۱
        $r = Database::selectOne('SELECT status, attempts FROM event_queue WHERE id = ?', [$row['id']]);
        $this->assertSame('pending', $r['status']); // قابل تلاش دوباره
        $this->assertStringContainsString('boom', (string) Database::selectOne('SELECT last_error FROM event_queue WHERE id = ?', [$row['id']])['last_error']);

        (new QueueWorker())->runBatch(5); // تلاش ۲
        (new QueueWorker())->runBatch(5); // تلاش ۳ → شکست دائم
        $r = Database::selectOne('SELECT status, attempts FROM event_queue WHERE id = ?', [$row['id']]);
        $this->assertSame('failed', $r['status']);
        $this->assertSame(3, (int) $r['attempts']);
    }

    /* ── ETag / 304 ── */

    public function test_etag_returns_304_on_second_request(): void
    {
        $first = App::handle(Request::create('GET', '/marketplace'));
        $this->assertSame(200, $first->status());
        $etag = $first->headers()['ETag'] ?? '';
        $this->assertNotSame('', $etag);

        $_SERVER['HTTP_IF_NONE_MATCH'] = $etag;
        $second = App::handle(Request::create('GET', '/marketplace'));
        unset($_SERVER['HTTP_IF_NONE_MATCH']);
        $this->assertSame(304, $second->status());
        $this->assertSame('', $second->body());
    }

    /* ── آپلود: SVG باید رد شود (نقد S1) ── */

    public function test_svg_upload_rejected(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'svgtest');
        file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $file = ['name' => 'evil.svg', 'type' => 'image/svg+xml', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];

        // finfo باید mime واقعی را svg تشخیص دهد → رد
        $this->expectException(\RuntimeException::class);
        try {
            Upload::image($file, 'products');
        } finally {
            @unlink($tmp);
        }
    }

    /* ── ممیزی ── */

    public function test_audit_diff_records_only_changes(): void
    {
        $variant = Database::selectOne('SELECT id, sku, price FROM product_variants ORDER BY id LIMIT 1');
        Audit::diff('variant', (int) $variant['id'], (string) $variant['sku'],
            ['price' => 1000, 'stock' => 5],
            ['price' => 1200, 'stock' => 5],   // فقط price تغییر کرده
            ['price', 'stock']
        );
        $rows = Database::select(
            'SELECT * FROM field_audits WHERE entity = "variant" AND entity_id = ? AND field IN ("price","stock") ORDER BY id DESC LIMIT 5',
            [$variant['id']]
        );
        $fields = array_column($rows, 'field');
        $this->assertContains('price', $fields);
        $this->assertNotContains('stock', $fields); // تغییر نیافته → ثبت نشده
        $latest = $rows[0];
        $this->assertSame('1000', $latest['old_value']);
        $this->assertSame('1200', $latest['new_value']);
    }

    /* ── سقف اعتبار نمایندگی ── */

    public function test_pos_blocked_when_over_credit_limit(): void
    {
        $agencyRow = Database::selectOne("SELECT * FROM agencies WHERE code = 'A03'");
        $agency = \App\Modules\Members\Models\Agency::hydrate($agencyRow);
        $variant = Database::selectOne('SELECT sku FROM product_variants WHERE is_active=1 AND stock>0 ORDER BY price DESC LIMIT 1');

        // سقف اعتبار را عمداً بسیار کم کن
        $originalLimit = (int) $agency->credit_limit;
        Database::update('UPDATE agencies SET credit_limit = 1000 WHERE id = ?', [$agency->id()]);
        $agency = \App\Modules\Members\Models\Agency::find((int) $agency->id());

        CartService::clear();
        CartService::add($variant['sku'], 1);
        $result = \App\Modules\Agency\Services\PosService::sell($agency, [
            'receiver' => 'تست سقف اعتبار', 'phone' => '09127776655', 'city' => 'نیشابور',
        ]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('سقف اعتبار', $result['error']);

        // بازگرداندن وضعیت
        Database::update('UPDATE agencies SET credit_limit = ? WHERE id = ?', [$originalLimit, $agency->id()]);
        CartService::clear();
    }

    /* ── جست‌وجوی جهانی ── */

    public function test_search_page_and_code_jump(): void
    {
        $sku = (string) Database::selectOne('SELECT sku FROM product_variants WHERE is_active=1 LIMIT 1')['sku'];

        // جست‌وجوی متنی → صفحهٔ نتایج
        $response = App::handle(Request::create('GET', '/search?q=' . rawurlencode('حوله')));
        $this->assertSame(200, $response->status());

        // کد SKU → پرش مستقیم به صفحهٔ محصول
        $response = App::handle(Request::create('GET', '/search?q=' . rawurlencode($sku)));
        $this->assertSame(302, $response->status());
        $this->assertStringContainsString(rawurlencode($sku), $response->headers()['Location']);

        // کد اصالت → پرش به صفحهٔ اصالت
        $fb = Database::selectOne('SELECT code FROM authenticity_codes LIMIT 1');
        if ($fb) {
            $response = App::handle(Request::create('GET', '/search?q=' . rawurlencode($fb['code'])));
            $this->assertSame(302, $response->status());
            $this->assertStringContainsString('authenticity', $response->headers()['Location']);
        }
    }

    /* ── هدرهای امنیتی جدید ── */

    public function test_security_headers_include_csp(): void
    {
        $headers = App::handle(Request::create('GET', '/'))->headers();
        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $this->assertStringContainsString("default-src 'self'", $headers['Content-Security-Policy']);
        $this->assertSame('1; mode=block', $headers['X-XSS-Protection']);
    }
}
