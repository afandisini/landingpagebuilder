<?php
class Auth
{
    private static bool $sessionStarted = false;

    public static function startSession(): void
    {
        if (!self::$sessionStarted) {
            session_start();
            self::$sessionStarted = true;
        }
    }

    public static function login(array $user): void
    {
        self::startSession();
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'] ?? $user['email'] ?? null,
            'role' => $user['role'] ?? 'admin',
        ];
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$sessionStarted = false;
    }

    public static function user(): ?array
    {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION['user']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ?r=login');
            exit;
        }
    }
}
