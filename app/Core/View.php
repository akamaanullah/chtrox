<?php

namespace App\Core;

class View
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function asset(string $path): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }

    public static function adminAsset(string $path): string
    {
        return BASE_URL . '/admin_assets/' . ltrim($path, '/');
    }

    public static function url(string $route = 'home'): string
    {
        if ($route === '' || $route === 'home') {
            return BASE_URL . '/';
        }

        return BASE_URL . '/' . ltrim($route, '/');
    }

    public static function appUrl(string $route = 'home'): string
    {
        return self::url($route);
    }

    public static function adminUrl(string $route = 'home'): string
    {
        if ($route === '' || $route === 'home') {
            return BASE_URL . '/admin';
        }

        return BASE_URL . '/admin/' . ltrim($route, '/');
    }

    public static function dashboardUrl(string $route = 'home'): string
    {
        return self::adminUrl($route);
    }

    public static function dashboardAsset(string $path): string
    {
        return self::adminAsset($path);
    }

    public static function render(string $path, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = VIEW_DIR . '/front/' . ltrim($path, '/');

        if (is_file($file)) {
            include $file;
        }
    }

    public static function renderAdmin(string $path, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = VIEW_DIR . '/admin/' . ltrim($path, '/');

        if (is_file($file)) {
            include $file;
        }
    }

    public static function renderDashboard(string $path, array $data = []): void
    {
        self::renderAdmin($path, $data);
    }

    public static function exists(string $path): bool
    {
        return is_file(VIEW_DIR . '/front/' . ltrim($path, '/'));
    }

    public static function adminExists(string $path): bool
    {
        return is_file(VIEW_DIR . '/admin/' . ltrim($path, '/'));
    }

    public static function dashboardExists(string $path): bool
    {
        return self::adminExists($path);
    }
}
