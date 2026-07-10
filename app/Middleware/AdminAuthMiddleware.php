<?php

namespace App\Middleware;

use App\Core\Session;

class AdminAuthMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        if (!Session::isAdminLoggedIn()) {
            $user = Session::user();
            if ($user && in_array(strtolower($user['role'] ?? ''), ['admin', 'owner'])) {
                Session::adminLogin($user);
            } else {
                Session::setFlash('error', 'Please sign in to access the admin panel.');
                header('Location: ' . BASE_URL . '/admin/login');
                exit;
            }
        }

        $admin = Session::adminUser();
        $token = $admin['session_token'] ?? '';

        if ($token === '' || !\App\Models\UserSession::isValid($token)) {
            Session::adminLogout();
            Session::setFlash('error', 'Your session has expired or is invalid. Please sign in again.');
            header('Location: ' . BASE_URL . '/admin/login');
            exit;
        }
    }
}
