<?php

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = VIEW_DIR . '/' . ltrim($view, '/') . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException('View does not exist: ' . $view);
        }

        require $viewFile;
    }

    protected function redirect(string $url): never
    {
        if (!str_starts_with($url, 'http')) {
            $url = BASE_URL . '/' . ltrim($url, '/');
        }

        header('Location: ' . $url);
        exit;
    }

    protected function jsonResponse(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function renderAuth(string $view, string $title, array $data = []): void
    {
        $this->view('front/layouts/auth', array_merge([
            'auth_view' => "front/{$view}.php",
            'auth_title' => $title,
        ], $data));
    }

    protected function getRequestInput(int $maxBytes = 2097152): array
    {
        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength > $maxBytes) {
            $this->jsonResponse(['error' => 'Request body too large'], 413);
        }

        $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
        if (strlen($raw) > $maxBytes) {
            $this->jsonResponse(['error' => 'Request body too large'], 413);
        }

        return json_decode($raw, true) ?? $_POST ?? [];
    }
}
