<?php

namespace App\Controllers\Front;

use App\Models\ActivityFeed;
use App\Core\Session;
use App\Core\Model;

class ActivityController extends FrontController
{
    public function index(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId > 0 && $memberId > 0) {
            $db = Model::db();
            $stmt = $db->prepare("
                UPDATE notifications 
                SET read_at = CURRENT_TIMESTAMP 
                WHERE recipient_id = ? AND workspace_id = ? AND read_at IS NULL
            ");
            $stmt->execute([$memberId, $workspaceId]);
        }

        $activityItems = ActivityFeed::items();

        $this->renderApp('activity', [
            'activity_items' => $activityItems,
            'activity_updates' => $activityItems,
        ]);
    }
}
