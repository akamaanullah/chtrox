<?php

namespace App\Middleware;

use App\Core\Session;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        if (!Session::isLoggedIn()) {
            if (Session::isAdminLoggedIn()) {
                Session::login(Session::adminUser());
            } else {
                Session::setFlash('error', 'Please sign in to access your workspace.');
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }

        $user = Session::user();
        $token = $user['session_token'] ?? '';

        // Absolute session timeout check (30 days)
        $loginTime = $user['logged_in_at'] ?? 0;
        if ($loginTime > 0 && (time() - $loginTime) > 86400 * 30) {
            if ($token !== '') {
                \App\Models\UserSession::revoke($token);
            }
            Session::logout();
            Session::setFlash('error', 'Your session has expired. Please sign in again.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Cache session token validity in PHP session for 5 minutes to reduce DB reads
        $lastChecked = $user['session_verified_at'] ?? 0;
        $needsRecheck = (time() - $lastChecked) > 300;

        if ($token === '' || ($needsRecheck && !\App\Models\UserSession::isValid($token))) {
            Session::logout();
            Session::setFlash('error', 'Your session has expired or is invalid. Please sign in again.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        if ($needsRecheck) {
            $_SESSION['chatrox_user']['session_verified_at'] = time();
        }
    }
}
