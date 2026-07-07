<?php

namespace App\Middleware;

use App\Core\Session;

class RateLimitMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        // Only rate limit state-changing/POST requests
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'POST') {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $ip = $this->getClientIp();

        // 1. Determine rate limit parameters based on the route
        if (str_contains($uri, '/login') || str_contains($uri, '/register')) {
            // Strict rate limit for authentication (5 requests per minute)
            $key = 'rate:auth:' . $ip;
            $limit = 5;
            $window = 60;
        } else {
            // Standard API rate limit (60 requests per minute)
            $user = Session::user();
            $userId = $user['id'] ?? 0;
            
            if ($userId > 0) {
                $key = 'rate:api:user:' . $userId;
            } else {
                $key = 'rate:api:ip:' . $ip;
            }
            $limit = 60;
            $window = 60;
        }

        // 2. Check and enforce the rate limit
        if (!$this->checkRateLimit($key, $limit, $window)) {
            $this->respondTooManyRequests();
        }
    }

    /**
     * Increment hits and verify if the rate limit has been exceeded.
     */
    private function checkRateLimit(string $key, int $limit, int $seconds): bool
    {
        $db = \App\Core\Model::db();

        // Opportunistic cleanup of expired limits (1% chance per request)
        if (mt_rand(1, 100) === 1) {
            $db->exec("DELETE FROM rate_limits WHERE expires_at < CURRENT_TIMESTAMP()");
        }

        $expiresAt = date('Y-m-d H:i:s', time() + $seconds);

        // Atomic insert or increment if window not expired
        $stmt = $db->prepare("
            INSERT INTO rate_limits (`key`, `hits`, `expires_at`)
            VALUES (?, 1, ?)
            ON DUPLICATE KEY UPDATE
                hits = IF(expires_at < CURRENT_TIMESTAMP(), 1, hits + 1),
                expires_at = IF(expires_at < CURRENT_TIMESTAMP(), VALUES(expires_at), expires_at)
        ");
        $stmt->execute([$key, $expiresAt]);

        // Fetch current hit count
        $stmt = $db->prepare("SELECT hits FROM rate_limits WHERE `key` = ?");
        $stmt->execute([$key]);
        $hits = (int)$stmt->fetchColumn();

        return $hits <= $limit;
    }

    /**
     * Extract client's true IP address.
     * Only trusts X-Forwarded-For when the request comes from a known trusted proxy.
     */
    private function getClientIp(): string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            $remoteAddr = '127.0.0.1';
        }

        // Only trust forwarded headers if the direct connection is from a trusted proxy
        $trustedProxies = defined('TRUSTED_PROXIES') ? TRUSTED_PROXIES : [];
        if (!empty($trustedProxies) && in_array($remoteAddr, $trustedProxies, true)) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
                foreach ($ips as $candidate) {
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }
        }

        return $remoteAddr;
    }

    /**
     * Respond with a 429 Too Many Requests status.
     */
    private function respondTooManyRequests(): never
    {
        http_response_code(429);
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        if (
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
            str_starts_with($uri, '/api/')
        ) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too many requests. Please try again in a minute.']);
        } else {
            Session::setFlash('error', 'Too many requests. Please try again in a minute.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        }
        exit;
    }
}
