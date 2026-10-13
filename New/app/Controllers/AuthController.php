<?php
namespace App\Controllers;
use Core\Controller;
use Core\View;
use Core\Application;
use App\Models\User;
use Core\Session;

class AuthController extends Controller
{
    public function loginForm(): string
    {
        return View::render('auth.login', [], 'auth');
    }
    public function login(): never
    {
        $mobile = $_POST['mobile'] ?? '';
        $password = $_POST['password'] ?? '';
        $user = User::findByMobile($mobile);
        if (!$user || !password_verify($password, $user->password)) {
            Session::setFlash('error', 'شماره موبایل یا رمز عبور اشتباه است.');
            redirect('/login');
        }
        Application::$app->auth->login([
            'id' => $user->id,
            'code' => $user->code,
            'full_name' => $user->full_name,
            'role' => $user->role,
        ]);
        redirect('/dashboard');
    }
    public function registerForm(): string
    {
        return View::render('auth.register', [], 'auth');
    }
    public function register(): never
    {
        $data = $_POST;
        // اعتبارسنجی ...
        $user = User::createCustomer($data);
        Application::$app->auth->login([
            'id' => $user->id,
            'code' => $user->code,
            'full_name' => $user->full_name,
            'role' => 'customer',
        ]);
        redirect('/');
    }
    public function logout(): never
    {
        Application::$app->auth->logout();
        redirect('/');
    }
}