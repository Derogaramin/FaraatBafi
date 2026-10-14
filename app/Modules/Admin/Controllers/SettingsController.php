<?php
/**
 * کنترلر تنظیمات سامانه
 */

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\Setting;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

final class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $this->authorizeCan('governance.view');
        return $this->view('Admin::settings', [
            'title'    => 'تنظیمات سامانه',
            'settings' => [
                'coop_name'    => Setting::get('coop_name', 'تعاونی تولیدی صنایع دستی نقشینه بافت تربت جام'),
                'brand_name'   => Setting::get('brand_name', 'فرت'),
                'phone'        => Setting::get('phone', '051-34567890'),
                'address'      => Setting::get('address', 'خراسان رضوی، تربت جام'),
                'free_shipping'=> Setting::get('free_shipping', '3000000'),
                'shipping_flat'=> Setting::get('shipping_flat', '85000'),
            ],
        ]);
    }

    public function update(Request $request): Response
    {
        $this->authorizeCan('governance.manage');
        $data = $this->validate($request->all(), [
            'coop_name'     => 'required|max:190',
            'brand_name'    => 'required|max:60',
            'phone'         => 'max:20',
            'address'       => 'max:255',
            'free_shipping' => 'numeric|min:0',
            'shipping_flat' => 'numeric|min:0',
        ], ['coop_name' => 'نام تعاونی', 'brand_name' => 'نام برند']);

        foreach ($data as $key => $value) {
            Setting::put($key, (string) $value);
        }
        Session::flash('success', 'تنظیمات ذخیره شد.');
        return $this->redirect(url('admin/settings'));
    }
}
