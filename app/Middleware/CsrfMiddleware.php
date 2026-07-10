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
            // Differentiate between AJAX/API requests and standard HTML form requests
            $isJson = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || 
                      (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) ||
                      (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

            if ($isJson) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'CSRF validation failed']);
                exit;
            } else {
                Session::init();
                Session::setFlash('error', 'Your session expired or form submission was invalid. Please try again.');
                $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
                header('Location: ' . $referer);
                exit;
            }
        }

        // Set response header with newly generated single-use CSRF token
        header('X-CSRF-Token: ' . Session::csrfToken());
    }
}
