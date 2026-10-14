<?php
/**
 * کنترلر اصالت محصول — صفحهٔ عمومی استعلام کد FB + خروجی QR (SVG)
 *
 * طبق مستندات: QR باید حاوی «آدرس صفحهٔ اصالت» باشد، نه کد خام.
 */

declare(strict_types=1);

namespace App\Modules\Marketplace\Controllers;

use App\Modules\Marketplace\Models\AuthenticityCode;
use Core\Controller;
use Core\QR;
use Core\Request;
use Core\Response;
use Core\Session;

final class AuthenticityController extends Controller
{
    /** فرم استعلام */
    public function form(Request $request): Response
    {
        return $this->view('Marketplace::authenticity-form', ['title' => 'استعلام اصالت محصول']);
    }

    public function lookup(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'code' => 'required|max:24',
        ], ['code' => 'کد اصالت']);

        $code = strtoupper(trim(en_digits((string) $data['code'])));
        if (!\Core\CodeGenerator::validate($code, 'authenticity')) {
            Session::flash('error', 'قالب کد اصالت نامعتبر است. نمونهٔ صحیح: FB-1403-0521-W023');
            Session::flashInput(['code' => $code]);
            return $this->redirect(url('authenticity'));
        }
        return $this->redirect(url('authenticity/' . rawurlencode($code)));
    }

    /** صفحهٔ اصالت: مشخصات بافت، بافنده، محصول + QR */
    public function show(Request $request): Response
    {
        $code = strtoupper($request->params['code'] ?? '');
        $detail = AuthenticityCode::detail($code);

        if ($detail === null) {
            return $this->view('Marketplace::authenticity-invalid', [
                'title' => 'کد اصالت نامعتبر',
                'code'  => $code,
            ], 404);
        }

        return $this->view('Marketplace::authenticity', [
            'title'  => 'اصالت محصول — ' . $detail['code'],
            'detail' => $detail,
            'qrSvg'  => QR::authenticitySvg($code),
        ]);
    }

    /** خروجی SVG مستقیم QR (برای چاپ/برچسب) */
    public function qr(Request $request): Response
    {
        $code = strtoupper($request->params['code'] ?? '');
        if (!\Core\CodeGenerator::validate($code, 'authenticity')) {
            throw new \Core\HttpException(404);
        }
        return Response::svg(QR::authenticitySvg($code, 8));
    }
}
