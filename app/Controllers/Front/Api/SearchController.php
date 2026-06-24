<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Models\WorkspaceSearch;

class SearchController extends Controller
{
    public function index(): void
    {
        if (!Session::isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'error' => 'unauthorized'], 401);
        }

        $query = trim((string)($_GET['q'] ?? ''));
        $limit = (int)($_GET['limit'] ?? 6);
        $conversationId = isset($_GET['conversation_id']) && (int)$_GET['conversation_id'] > 0 ? (int)$_GET['conversation_id'] : null;

        $this->jsonResponse([
            'success' => true,
            'query' => $query,
            'results' => WorkspaceSearch::search($query, $limit, $conversationId),
        ]);
    }
}
