<?php

namespace App\Controllers;

use Core\Controller;
use Core\Application;
use App\Models\User;
use Core\CodeGenerator;

class MemberController extends Controller
{
    public function index(): string
    {
        $members = User::findByRole('member');
        return $this->render('members.list', ['members' => $members]);
    }

    public function create(): string
    {
        return $this->render('members.create');
    }

    public function store(): never
    {
        $data = $_POST;
        $code = CodeGenerator::generateWeaverCode(); // یا تابع اختصاصی برای اعضا
        $stmt = Application::$app->db->pdo->prepare(
            "INSERT INTO users (code, full_name, email, mobile, password, role) VALUES (:code, :name, :email, :mobile, :pass, 'member')"
        );
        $stmt->execute([
            'code' => $code,
            'name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'mobile' => $data['mobile'],
            'pass' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
        redirect('/members');
    }
}