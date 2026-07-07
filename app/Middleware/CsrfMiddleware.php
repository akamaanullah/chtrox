<?php

namespace App\Middleware;

use App\Core\Session;

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!Session::verifyCsrf($token, false)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'CSRF validation failed']);
            exit;
        }
    }
}
