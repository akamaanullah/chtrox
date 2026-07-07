<?php

namespace App\Core;

class Session
{
    private const USER_KEY = 'chatrox_user';
    private const ADMIN_KEY = 'chatrox_admin';

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');

            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', '1');
            }

            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return $flash;
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION[self::USER_KEY]);
    }

    public static function user(): ?array
    {
        return $_SESSION[self::USER_KEY] ?? null;
    }

    public static function login(array $userData): void
    {
        session_regenerate_id(true);
        $_SESSION[self::USER_KEY] = array_merge($userData, [
            'logged_in_at' => time(),
        ]);
    }

    public static function logout(): void
    {
        unset($_SESSION[self::USER_KEY]);
    }

    public static function isAdminLoggedIn(): bool
    {
        return !empty($_SESSION[self::ADMIN_KEY]);
    }

    public static function adminUser(): ?array
    {
        return $_SESSION[self::ADMIN_KEY] ?? null;
    }

    public static function adminLogin(array $adminData): void
    {
        session_regenerate_id(true);
        $_SESSION[self::ADMIN_KEY] = array_merge($adminData, [
            'logged_in_at' => time(),
        ]);
    }

    public static function adminLogout(): void
    {
        unset($_SESSION[self::ADMIN_KEY]);
    }

    // ── CSRF Protection ──────────────────────────────────────

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . self::csrfToken() . '">';
    }

    public static function verifyCsrf(?string $token = null, bool $singleUse = true): bool
    {
        if ($token === null) {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        if ($token === '' || !hash_equals(self::csrfToken(), $token)) {
            return false;
        }

        if ($singleUse) {
            unset($_SESSION['_csrf_token']);
        }

        return true;
    }
}
