<?php
/**
 * کنترلر کاتالوگ عمومی بازارگاه — فهرست با فیلتر و صفحهٔ جزئیات محصول
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Controllers;

use Core\Controller;
use Core\Database;
use Core\Jalali;
use Core\Request;
use Core\Response;

final class CatalogController extends Controller
{
    /** فهرست محصولات با فیلتر نوع/رنگ/طرح/جست‌وجو/مرتب‌سازی + صفحه‌بندی */
    public function index(Request $request): Response
    {
        $q        = $request->str('q');
        $type     = $request->str('type');
        $color    = $request->str('color');
        $pattern  = $request->str('pattern');
        $sort     = $request->str('sort', 'newest');
        $inStock  = $request->str('stock') === '1';

        $where = ['pv.is_active = 1'];
        $bindings = [];

        if ($q !== '') {
            $where[] = '(pm.name_fa LIKE ? OR pv.sku LIKE ? OR p.name_fa LIKE ? OR pt.name_fa LIKE ?)';
            $like = '%' . $q . '%';
            array_push($bindings, $like, $like, $like, $like);
        }
        if ($type !== '') {
            $where[] = 'pt.code = ?';
            $bindings[] = $type;
        }
        if ($color !== '') {
            $where[] = 'c.code = ?';
            $bindings[] = $color;
        }
        if ($pattern !== '') {
            $where[] = 'p.code = ?';
            $bindings[] = $pattern;
        }
        if ($inStock) {
            $where[] = 'pv.stock > 0';
        }

        $orderSql = match ($sort) {
            'cheap'   => 'pv.price ASC',
            'expensive' => 'pv.price DESC',
            'popular' => 'pv.is_featured DESC, pv.id DESC',
            default   => 'pv.id DESC',
        };

        $perPage = 12;
        $page = max(1, $request->int('page', 1));
        $whereSql = implode(' AND ', $where);

        $countRow = Database::selectOne(
            "SELECT COUNT(*) AS c
             FROM product_variants pv
             JOIN product_models pm ON pm.id = pv.model_id
             JOIN product_types pt ON pt.id = pm.type_id
             JOIN colors c ON c.id = pv.color_id
             JOIN patterns p ON p.id = pv.pattern_id
             WHERE {$whereSql}",
            $bindings
        );
        $total = (int) ($countRow['c'] ?? 0);

        $products = Database::select(
            "SELECT pv.id, pv.sku, pv.price, pv.stock, pv.image, pv.is_featured,
                    pm.name_fa, pm.size_cm, pm.chelle, pm.weave, pm.knot, pm.stitch,
                    pt.name_fa AS type_name,
                    c.name_fa AS color_name, c.hex AS color_hex, c.code AS color_code,
                    p.name_fa AS pattern_name, p.code AS pattern_code
             FROM product_variants pv
             JOIN product_models pm ON pm.id = pv.model_id
             JOIN product_types pt ON pt.id = pm.type_id
             JOIN colors c ON c.id = pv.color_id
             JOIN patterns p ON p.id = pv.pattern_id
             WHERE {$whereSql}
             ORDER BY {$orderSql}
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $bindings
        );

        // تعداد محصولات هر نوع (برای چیپ‌ها)
        $typeCounts = array_column(
            Database::select('SELECT pt.code, COUNT(*) AS c FROM product_variants pv JOIN product_models pm ON pm.id = pv.model_id JOIN product_types pt ON pt.id = pm.type_id WHERE pv.is_active = 1 GROUP BY pt.code'),
            'c',
            'code'
        );

        return $this->view('Marketplace::catalog', [
            'title'    => 'بازارگاه فرت — خرید دست‌بافته‌های اصیل',
            'products' => $products,
            'types'    => Database::select('SELECT code, name_fa FROM product_types WHERE is_active = 1 ORDER BY sort_order'),
            'typeCounts' => $typeCounts,
            'colors'   => Database::select('SELECT code, name_fa, hex FROM colors WHERE is_active = 1 ORDER BY code'),
            'patterns' => Database::select('SELECT code, name_fa FROM patterns WHERE is_active = 1 ORDER BY code'),
            'filters'  => compact('q', 'type', 'color', 'pattern', 'sort', 'inStock'),
            'paginator' => new \Core\Paginator($products, $total, $perPage, $page),
        ]);
    }

    /** جزئیات محصول — اطلاعات فنی کامل، رنگ‌های هم‌مدل، اصالت */
    public function show(Request $request): Response
    {
        $sku = strtoupper($request->params['sku'] ?? '');
        $variant = Database::selectOne('SELECT id FROM product_variants WHERE sku = ?', [$sku]);
        if ($variant === null) {
            throw new \Core\HttpException(404, 'محصول مورد نظر در بازارگاه فرت یافت نشد.');
        }

        $product = \App\Modules\Catalog\Models\ProductVariant::detailed((int) $variant['id']);
        if ($product === null || (int) $product['is_active'] !== 1) {
            throw new \Core\HttpException(404, 'این محصول در دسترس نیست.');
        }

        // سایر رنگ/طرح‌های همین مدل فنی (واریانت‌های خواهر)
        $siblings = Database::select(
            'SELECT pv.sku, pv.stock, pv.price, pv.image, c.name_fa AS color_name, c.hex, p.name_fa AS pattern_name
             FROM product_variants pv
             JOIN colors c ON c.id = pv.color_id
             JOIN patterns p ON p.id = pv.pattern_id
             WHERE pv.model_id = ? AND pv.id != ? AND pv.is_active = 1
             LIMIT 8',
            [$product['model_id'], $product['id']]
        );

        // نمونهٔ کد اصالت برای نمایش
        $sampleAuth = Database::selectOne(
            'SELECT ac.code, w.name AS weaver_name FROM authenticity_codes ac
             JOIN weavers w ON w.id = ac.weaver_id
             WHERE ac.variant_id = ? ORDER BY ac.id DESC LIMIT 1',
            [$product['id']]
        );

        return $this->view('Marketplace::product', [
            'title'      => $product['model_name'] . ' — ' . $product['sku'],
            'product'    => $product,
            'siblings'   => $siblings,
            'sampleAuth' => $sampleAuth,
            'weaveLabels' => \Core\CodeGenerator::WEAVES,
            'knotLabels'  => \Core\CodeGenerator::KNOTS,
            'stitchLabels'=> \Core\CodeGenerator::STITCHES,
        ]);
    }
}
