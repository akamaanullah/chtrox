<?php

namespace App\Middleware;

use App\Core\Session;

class GuestMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        if (Session::isAdminLoggedIn() && !Session::isLoggedIn()) {
            Session::login(Session::adminUser());
        }

        if (Session::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }
}
