<?php

namespace App\Middleware;

use App\Core\Session;

class GuestMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }
}
