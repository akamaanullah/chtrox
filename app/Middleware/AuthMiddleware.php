<?php

namespace App\Middleware;

use App\Core\Session;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        if (!Session::isLoggedIn()) {
            Session::setFlash('error', 'Please sign in to access your workspace.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $user = Session::user();
        $token = $user['session_token'] ?? '';

        if ($token === '' || !\App\Models\UserSession::isValid($token)) {
            Session::logout();
            Session::setFlash('error', 'Your session has expired or is invalid. Please sign in again.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
}
