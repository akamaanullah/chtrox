<?php

namespace App\Controllers\Front;

use App\Core\Session;
use App\Core\Model;
use PDO;

class SettingsController extends FrontController
{
    public function index(): void
    {
        $user = Session::user();
        $userId = (int)($user['id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($userId === 0 || $memberId === 0 || $workspaceId === 0) {
            $this->redirect('/login');
            return;
        }

        $db = Model::db();

        // 1. Query personal file usage size
        $stmtMyUsage = $db->prepare("
            SELECT SUM(size_bytes) AS total_size
            FROM files
            WHERE uploaded_by = ? AND workspace_id = ? AND deleted_at IS NULL
        ");
        $stmtMyUsage->execute([$memberId, $workspaceId]);
        $myUsageBytes = (int)($stmtMyUsage->fetchColumn() ?: 0);

        // 2. Query workspace storage quota and usage
        $stmtWorkspaceUsage = $db->prepare("
            SELECT quota_bytes, used_bytes
            FROM workspace_storage_quotas
            WHERE workspace_id = ?
            LIMIT 1
        ");
        $stmtWorkspaceUsage->execute([$workspaceId]);
        $quotaRow = $stmtWorkspaceUsage->fetch(PDO::FETCH_ASSOC);

        $workspaceQuotaBytes = (int)($quotaRow['quota_bytes'] ?? 16106127360); // default 15GB
        $workspaceUsedBytes = (int)($quotaRow['used_bytes'] ?? 0);

        // 3. Query user preferences
        $stmtPrefs = $db->prepare("
            SELECT theme_color, notification_settings, timezone
            FROM user_preferences
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmtPrefs->execute([$userId]);
        $prefsRow = $stmtPrefs->fetch(PDO::FETCH_ASSOC);

        $themeColor = $prefsRow['theme_color'] ?? 'indigo';
        $timezone = $prefsRow['timezone'] ?? 'UTC';
        $notifSettings = json_decode($prefsRow['notification_settings'] ?? '{}', true);

        // Provide defaults for notification preferences if empty
        $notificationPreferences = array_merge([
            'all' => true,
            'dm' => true,
            'channels' => true,
            'channel_requests' => true,
            'mentions' => true,
            'tone' => 'default'
        ], $notifSettings);

        $viewData = [
            'theme_color' => $themeColor,
            'timezone' => $timezone,
            'notification_settings' => $notificationPreferences,
            'my_usage_bytes' => $myUsageBytes,
            'workspace_used_bytes' => $workspaceUsedBytes,
            'workspace_quota_bytes' => $workspaceQuotaBytes,
            'name' => 'Settings'
        ];

        $this->renderApp('settings', $viewData, 'tabs/settings/main.php');
    }
}
