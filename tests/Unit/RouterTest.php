<?php
/**
 * تست واحد مسیریاب و رویدادها و کمک‌توابع
 */

declare(strict_types=1);

namespace Tests\Unit;

use Core\Events;
use Core\Request;
use Core\Response;
use Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private function router(): Router
    {
        $r = new Router();
        $r->get('/', fn () => Response::html('home'), 'home');
        $r->get('/marketplace/product/{sku}', fn () => Response::html('product'), 'product');
        $r->post('/cart/add', fn () => Response::html('add'), null, ['csrf']);
        $r->get('/admin/orders/{id:int}', fn () => Response::html('order'));
        $r->group(['prefix' => '/weaver', 'middleware' => ['auth']], function (Router $r): void {
            $r->get('', fn () => Response::html('weaver-home'));
            $r->get('/orders/{id:int}', fn () => Response::html('weaver-order'));
        });
        return $r;
    }

    public function test_exact_match(): void
    {
        $m = $this->router()->match(Request::create('GET', '/'));
        $this->assertArrayHasKey('route', $m);
        $this->assertSame('home', $m['route']['name']);
    }

    public function test_param_extraction(): void
    {
        $m = $this->router()->match(Request::create('GET', '/marketplace/product/HT-2LBSTD-CH125-R01-P0003'));
        $this->assertSame('HT-2LBSTD-CH125-R01-P0003', $m['params']['sku']);
    }

    public function test_int_constraint_rejects_non_numeric(): void
    {
        $m = $this->router()->match(Request::create('GET', '/admin/orders/abc'));
        $this->assertSame(['error' => 404], $m);
        $m = $this->router()->match(Request::create('GET', '/admin/orders/15'));
        $this->assertSame('15', $m['params']['id']);
    }

    public function test_method_mismatch_gives_405(): void
    {
        $this->assertSame(['error' => 405], $this->router()->match(Request::create('POST', '/')));
        $this->assertSame(['error' => 405], $this->router()->match(Request::create('GET', '/cart/add')));
    }

    public function test_unknown_path_404(): void
    {
        $this->assertSame(['error' => 404], $this->router()->match(Request::create('GET', '/nope')));
    }

    public function test_group_prefix_and_middleware(): void
    {
        $m = $this->router()->match(Request::create('GET', '/weaver'));
        $this->assertSame(['auth'], $m['route']['middleware']);
        $m = $this->router()->match(Request::create('GET', '/weaver/orders/7'));
        $this->assertSame('7', $m['params']['id']);
        $this->assertSame(['auth'], $m['route']['middleware']);
    }

    public function test_route_middleware_merge(): void
    {
        $m = $this->router()->match(Request::create('POST', '/cart/add'));
        $this->assertSame(['csrf'], $m['route']['middleware']);
    }

    public function test_trailing_slash_normalized(): void
    {
        $m = $this->router()->match(Request::create('GET', '/weaver/'));
        $this->assertArrayHasKey('route', $m);
    }

    public function test_events_dispatch_and_listeners(): void
    {
        // توجه: فقط رویدادهای ثبت‌شدهٔ خود آزمون پاک می‌شوند؛
        // Events::reset() شنونده‌های ماژول‌ها را نیز از بین می‌برد و مجاز نیست.
        $hits = [];
        Events::on('test.ping', function (array $payload) use (&$hits): void { $hits[] = $payload['n']; });
        Events::on('test.ping', function (array $payload) use (&$hits): void { $hits[] = $payload['n'] * 2; });
        Events::dispatch('test.ping', ['n' => 21]);
        $this->assertSame([21, 42], $hits);
        $this->assertTrue(Events::hasListeners('test.ping'));
        $this->assertNotEmpty(Events::dispatched('test.ping'));
        Events::flush();
    }

    public function test_helpers(): void
    {
        $this->assertSame('۱,۲۳۴', fa(number_format(1234)));
        $this->assertSame('1405', en_digits('۱۴۰۵'));
        $this->assertSame('09151234567', normalize_phone('+989151234567'));
        $this->assertSame('09151234567', normalize_phone('۹۱۵۱۲۳۴۵۶۷'));
        $this->assertSame('natural-dyes', str_slug('Natural Dyes!!'));
        $this->assertSame('رنگ-طبیعی', str_slug('رنگ طبیعی'));
        $this->assertSame('۱,۵۰۰,۰۰۰ تومان', toman(1500000));
        $this->assertStringContainsString('میلیون', toman_short(25_000_000));
    }
}
