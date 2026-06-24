<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;

class GiphyController extends Controller
{
    public function gifs(): void
    {
        if (!Session::isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'error' => 'unauthorized'], 401);
        }

        if (GIPHY_API_KEY === '') {
            $this->jsonResponse([
                'success' => false,
                'error' => 'giphy_not_configured',
                'message' => 'GIF search is not configured. Add GIPHY_API_KEY to your .env file.',
            ], 503);
        }

        $query = trim((string)($_GET['q'] ?? ''));
        $limit = min(30, max(1, (int)($_GET['limit'] ?? 20)));

        $params = [
            'api_key' => GIPHY_API_KEY,
            'limit' => $limit,
            'rating' => 'pg-13',
        ];

        if ($query !== '') {
            $params['q'] = $query;
            $endpoint = 'https://api.giphy.com/v1/gifs/search?' . http_build_query($params);
        } else {
            $endpoint = 'https://api.giphy.com/v1/gifs/trending?' . http_build_query($params);
        }

        $response = $this->requestGiphy($endpoint);
        if ($response === null) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'upstream_failed',
                'message' => 'Could not reach Giphy. Try again shortly.',
            ], 502);
        }

        $payload = json_decode($response, true);
        if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'invalid_response',
                'message' => 'Unexpected response from Giphy.',
            ], 502);
        }

        $gifs = [];
        foreach ($payload['data'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $images = $item['images'] ?? [];
            $url = $images['fixed_height']['url']
                ?? $images['downsized_medium']['url']
                ?? $images['original']['url']
                ?? '';

            if ($url === '') {
                continue;
            }

            $gifs[] = [
                'url' => $url,
                'preview' => $images['fixed_height_small']['url'] ?? $url,
                'title' => (string)($item['title'] ?? ''),
            ];
        }

        $this->jsonResponse([
            'success' => true,
            'gifs' => $gifs,
        ]);
    }

    private function requestGiphy(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false || $status < 200 || $status >= 300) {
                return null;
            }

            return $body;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 12,
                'header' => "Accept: application/json\r\n",
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        return $body === false ? null : $body;
    }
}
