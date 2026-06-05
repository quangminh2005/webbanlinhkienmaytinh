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

        $this->loginUser($user);
        $this->redirect('/');
    }

    public function googleRedirect(): void
    {
        $config = $this->googleConfig();
        if (empty($config['enabled']) || empty($config['client_id']) || empty($config['client_secret'])) {
            $_SESSION['error'] = 'Dang nhap Google chua duoc cau hinh.';
            $this->redirect('/auth/login');
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $params = [
            'client_id' => (string) $config['client_id'],
            'redirect_uri' => $this->googleRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ];

        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
        exit;
    }

    public function googleCallback(): void
    {
        $config = $this->googleConfig();
        $state = (string) ($_GET['state'] ?? '');
        if ($state === '' || empty($_SESSION['google_oauth_state']) || !hash_equals((string) $_SESSION['google_oauth_state'], $state)) {
            unset($_SESSION['google_oauth_state']);
            $_SESSION['error'] = 'Phien dang nhap Google khong hop le.';
            $this->redirect('/auth/login');
        }
        unset($_SESSION['google_oauth_state']);

        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            $_SESSION['error'] = 'Google khong tra ve ma xac thuc.';
            $this->redirect('/auth/login');
        }

        try {
            $token = $this->googleToken($code, $config);
            $profile = $this->googleProfile((string) ($token['access_token'] ?? ''));

            $googleId = (string) ($profile['sub'] ?? '');
            $email = trim((string) ($profile['email'] ?? ''));
            $name = trim((string) ($profile['name'] ?? ''));
            $emailVerified = (bool) ($profile['email_verified'] ?? false);

            if ($googleId === '' || $email === '' || !$emailVerified) {
                throw new \Exception('Tai khoan Google khong hop le hoac email chua xac minh.');
            }

            if ($name === '') {
                $name = $email;
            }

            $userModel = new User();
            $user = $userModel->findByGoogleId($googleId);
            if (!$user) {
                $user = $userModel->findByEmail($email);
                if ($user) {
                    $userModel->linkGoogleAccount((int) $user['id'], $googleId);
                    $user = $userModel->findById((int) $user['id']);
                } else {
                    $userId = $userModel->createFromGoogle($name, $email, $googleId);
                    $user = $userModel->findById($userId);
                }
            }

            if (!$user) {
                throw new \Exception('Khong the tao hoac lay tai khoan.');
            }

            $this->loginUser($user);
            $this->redirect('/');
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/auth/login');
        }
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        $this->redirect('/');
    }

    private function loginUser(array $user): void
    {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'phone' => $user['phone'] ?? '',
            'address' => $user['address'] ?? '',
        ];
    }

    private function googleConfig(): array
    {
        $path = dirname(__DIR__, 2) . '/config/google_oauth.php';
        if (!is_file($path)) {
            return [];
        }

        $config = require $path;
        return is_array($config) ? $config : [];
    }

    private function googleRedirectUri(): string
    {
        $scheme = 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = (string) $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . app_url('/auth/google/callback');
    }

    private function googleToken(string $code, array $config): array
    {
        $response = $this->postForm('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => (string) $config['client_id'],
            'client_secret' => (string) $config['client_secret'],
            'redirect_uri' => $this->googleRedirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new \Exception('Khong lay duoc access token tu Google.');
        }

        return $data;
    }

    private function googleProfile(string $accessToken): array
    {
        if ($accessToken === '') {
            throw new \Exception('Access token Google rong.');
        }

        $response = $this->getJson('https://openidconnect.googleapis.com/v1/userinfo', [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
        ]);

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \Exception('Khong lay duoc thong tin Google.');
        }

        return $data;
    }

    private function postForm(string $url, array $fields): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($fields),
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $code < 200 || $code >= 300) {
                throw new \Exception('Google token request that bai.');
            }
            return (string) $body;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($fields),
                'timeout' => 20,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new \Exception('Google token request that bai.');
        }
        return (string) $body;
    }

    private function getJson(string $url, array $headers): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $code < 200 || $code >= 300) {
                throw new \Exception('Google profile request that bai.');
            }
            return (string) $body;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers) . "\r\n",
                'timeout' => 20,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new \Exception('Google profile request that bai.');
        }
        return (string) $body;
    }
}

