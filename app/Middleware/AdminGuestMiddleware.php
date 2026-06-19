<?php

namespace App\Middleware;

use App\Core\Session;

class AdminGuestMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        if (Session::isAdminLoggedIn()) {
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }
    }
}
