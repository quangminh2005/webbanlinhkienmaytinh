<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->view('auth/login');
    }

    public function registerForm(): void
    {
        $this->view('auth/register');
    }

    public function register(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            $_SESSION['error'] = 'Vui long dien day du thong tin.';
            $this->redirect('/auth/register');
        }

        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            $_SESSION['error'] = 'Email da ton tai.';
            $this->redirect('/auth/register');
        }

        $userModel->create($name, $email, $password);
        $_SESSION['success'] = 'Dang ky thanh cong, vui long dang nhap.';
        $this->redirect('/auth/login');
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $user = (new User())->findByEmail($email);

        $passwordOk = false;
        if ($user) {
            if (str_starts_with($user['password_hash'], '$2y$') || str_starts_with($user['password_hash'], '$argon2')) {
                $passwordOk = password_verify($password, $user['password_hash']);
            } else {
                $passwordOk = hash_equals($user['password_hash'], $password);
            }
        }

        if (!$user || !$passwordOk) {
            $_SESSION['error'] = 'Sai email hoac mat khau.';
            $this->redirect('/auth/login');
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'phone' => $user['phone'] ?? '',
            'address' => $user['address'] ?? '',
        ];
        $this->redirect('/');
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        $this->redirect('/');
    }
}

