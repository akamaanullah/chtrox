<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Models\WebSocketTicket;

class WsTicketController extends Controller
{
    /**
     * Generate a new single-use WebSocket ticket for the authenticated user.
     */
    public function getTicket(): void
    {
        $user = Session::user();
        if (!$user) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $userId = (int)($user['id'] ?? 0);
        $workspaceMemberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $sessionToken = $user['session_token'] ?? '';

        if ($userId === 0 || $workspaceMemberId === 0 || $workspaceId === 0 || $sessionToken === '') {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $ticket = WebSocketTicket::createTicket($userId, $workspaceMemberId, $workspaceId, $sessionToken);

        $this->jsonResponse([
            'ticket' => $ticket,
            'workspace_id' => $workspaceId
        ]);
    }
}
