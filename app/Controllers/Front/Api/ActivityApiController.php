<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Model;

class ActivityApiController extends Controller
{
    public function delete(string $id): void
    {
        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $notifId = (int)$id;

        if ($memberId === 0 || $workspaceId === 0 || $notifId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $db = Model::db();
        $stmt = $db->prepare("
            UPDATE notifications 
            SET deleted_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND recipient_id = ? AND workspace_id = ?
        ");
        $stmt->execute([$notifId, $memberId, $workspaceId]);

        $this->jsonResponse(['success' => true]);
    }

    public function clear(): void
    {
        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($memberId === 0 || $workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $db = Model::db();
        $stmt = $db->prepare("
            UPDATE notifications 
            SET deleted_at = CURRENT_TIMESTAMP 
            WHERE recipient_id = ? AND workspace_id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$memberId, $workspaceId]);

        $this->jsonResponse(['success' => true]);
    }
}
