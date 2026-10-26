<?php
namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\Flash;
use Admin\Core\View;
use App\Core\Request;
use App\Core\AdminNotifier;

class AuthController
{
    public function showLogin(Request $request, array $params = []): void
    {
        if (AdminAuth::check()) { header('Location: /pasabuy/dashboard'); exit; }
        View::render('auth.login', ['title' => 'Sign in'], null);
    }

    public function login(Request $request, array $params = []): void
    {
        $email    = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            Flash::set('error', 'Email and password are required');
            header('Location: /pasabuy/login'); exit;
        }
        
        if (!AdminAuth::attempt($email, $password)) {
            Flash::set('error', 'Invalid credentials or not an admin');
            header('Location: /pasabuy/login'); exit;
        }
        Flash::set('success', 'Welcome back!');
        header('Location: /pasabuy/dashboard'); exit;
    }

    public function logout(Request $request, array $params = []): void
    {
        AdminAuth::logout();
        Flash::set('success', 'Logged out.');
        header('Location: /pasabuy/login'); exit;
    }
}