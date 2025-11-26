<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/Csrf.php';
require_once __DIR__ . '/../Models/User.php';

class AuthController
{
    public function loginForm(string $error = ''): void
    {
        $content = $this->renderView(__DIR__ . '/../Views/auth/login.php', ['error' => $error]);
        require __DIR__ . '/../Views/layouts/auth.php';
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->loginForm('Invalid session token. Please try again.');
            return;
        }

        if ($email === '' || $password === '') {
            $this->loginForm('Email and password are required.');
            return;
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->loginForm('Akses Gagal. ID atau Katasandi salah.');
            return;
        }

        Auth::login($user);
        header('Location: ?r=admin/dashboard');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ?r=login');
        exit;
    }

    private function renderView(string $view, array $params = []): string
    {
        extract($params, EXTR_SKIP);
        ob_start();
        include $view;
        return ob_get_clean();
    }
}
