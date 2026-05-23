<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class ProfileController extends Controller
{
    private function ensureLogin(): array
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            $_SESSION['error'] = 'Vui long dang nhap truoc.';
            $this->redirect('/auth/login');
        }
        return $user;
    }

    public function show(): void
    {
        $sessionUser = $this->ensureLogin();
        $user = (new User())->findById((int) $sessionUser['id']);
        if (!$user) {
            unset($_SESSION['user']);
            $_SESSION['error'] = 'Tai khoan khong ton tai.';
            $this->redirect('/auth/login');
        }

        $this->view('profile/show', ['user' => $user]);
    }

    public function update(): void
    {
        $sessionUser = $this->ensureLogin();
        $userId = (int) $sessionUser['id'];

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));

        if ($name === '' || $email === '') {
            $_SESSION['error'] = 'Ho ten va email khong duoc de trong.';
            $this->redirect('/profile');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Email khong hop le.';
            $this->redirect('/profile');
        }

        $userModel = new User();
        if ($userModel->emailExistsForOtherUser($email, $userId)) {
            $_SESSION['error'] = 'Email da duoc tai khoan khac su dung.';
            $this->redirect('/profile');
        }

        try {
            $userModel->updateProfile($userId, $name, $email, $phone, $address);
            $updatedUser = $userModel->findById($userId);
            if ($updatedUser) {
                $_SESSION['user'] = [
                    'id' => $updatedUser['id'],
                    'name' => $updatedUser['name'],
                    'email' => $updatedUser['email'],
                    'role' => $updatedUser['role'],
                    'phone' => $updatedUser['phone'] ?? '',
                    'address' => $updatedUser['address'] ?? '',
                ];
            }
            $_SESSION['success'] = 'Cap nhat thong tin ca nhan thanh cong.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/profile');
    }
}
