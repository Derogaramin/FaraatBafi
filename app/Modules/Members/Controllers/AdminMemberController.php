<?php
/**
 * کنترلر اعضا و پرسنل (حاکمیت تعاونی)
 */

declare(strict_types=1);

namespace App\Modules\Members\Controllers;

use App\Modules\Members\Services\UserService;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

final class AdminMemberController extends Controller
{
    public function members(Request $request): Response
    {
        return $this->view('Members::admin.members', [
            'title'   => 'اعضای تعاونی',
            'members' => Database::select(
                'SELECT m.*, u.name, u.phone, u.email, u.status AS user_status, u.last_login_at
                 FROM members m JOIN users u ON u.id = m.user_id ORDER BY m.code'
            ),
            'totalShares' => (int) (Database::selectOne('SELECT COALESCE(SUM(shares),0) AS s FROM members')['s'] ?? 0),
        ]);
    }

    public function storeMember(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name'        => 'required|min:3|max:120',
            'phone'       => 'required|phone|unique:users,phone',
            'email'       => 'email|unique:users,email',
            'national_id' => 'melli',
            'position'    => 'max:120',
            'shares'      => 'required|integer|min:1',
            'share_value' => 'numeric|min:0',
            'password'    => 'min:8',
        ], ['name' => 'نام عضو', 'phone' => 'موبایل', 'shares' => 'تعداد سهام']);

        $member = UserService::createMember($data);
        Session::flash('success', 'عضو با کد «' . $member->code . '» ثبت شد. (id=' . $member->id() . ')');
        return $this->redirect(url('admin/hr/members'));
    }

    public function staff(Request $request): Response
    {
        return $this->view('Members::admin.staff', [
            'title' => 'پرسنل اجرایی',
            'staff' => Database::select(
                'SELECT s.*, u.name, u.phone, u.email, u.status AS user_status
                 FROM staff s JOIN users u ON u.id = s.user_id ORDER BY s.code'
            ),
        ]);
    }

    public function storeStaff(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name'        => 'required|min:3|max:120',
            'phone'       => 'required|phone|unique:users,phone',
            'email'       => 'email|unique:users,email',
            'national_id' => 'melli',
            'position'    => 'max:120',
            'base_salary' => 'numeric|min:0',
            'password'    => 'min:8',
        ], ['name' => 'نام', 'phone' => 'موبایل', 'base_salary' => 'حقوق پایه']);

        $staff = UserService::createStaff($data);
        Session::flash('success', 'پرسنل با کد «' . $staff->code . '» ثبت شد. (id=' . $staff->id() . ')');
        return $this->redirect(url('admin/hr/staff'));
    }
}
