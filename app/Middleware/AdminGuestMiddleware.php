<?php

namespace App\Middleware;

use App\Core\Session;

class AdminGuestMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        if (Session::isLoggedIn()) {
            $user = Session::user();
            if ($user && in_array(strtolower($user['role'] ?? ''), ['admin', 'owner']) && !Session::isAdminLoggedIn()) {
                Session::adminLogin($user);
            }
        }

        if (Session::isAdminLoggedIn()) {
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }
    }
}
