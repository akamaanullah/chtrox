<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Model;
use App\Models\AuditLog;
use App\Models\User;
use PDO;

class SettingsController extends Controller
{
    public function getSettings(): void
    {
        $user = Session::user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $db = Model::db();
        $stmt = $db->prepare("
            SELECT theme_color, notification_settings, timezone
            FROM user_preferences
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $notifSettings = json_decode($prefs['notification_settings'] ?? '{}', true);

        // Fetch preferred presence status
        $stmtStatus = $db->prepare("SELECT preferred_status FROM user_presence WHERE user_id = ? LIMIT 1");
        $stmtStatus->execute([$userId]);
        $statusRow = $stmtStatus->fetch(PDO::FETCH_ASSOC);
        $presenceStatus = $statusRow['preferred_status'] ?? 'online';

        $this->jsonResponse([
            'success' => true,
            'preferences' => [
                'theme_color' => $prefs['theme_color'] ?? 'indigo',
                'notification_settings' => array_merge([
                    'all' => true,
                    'dm' => true,
                    'channels' => true,
                    'channel_requests' => true,
                    'mentions' => true,
                    'tone' => 'default'
                ], $notifSettings),
                'timezone' => $prefs['timezone'] ?? 'UTC',
                'presence_status' => $presenceStatus
            ]
        ]);
    }

    public function updateSettings(): void
    {
        $user = Session::user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();

        // 1. Validate Theme Color
        $themeColor = trim((string)($input['theme_color'] ?? ''));
        $allowedThemes = [
            'indigo', 'blue', 'violet', 'emerald', 'rose', 'sky',
            'teal', 'amber', 'cyan', 'fuchsia', 'lime', 'orange'
        ];

        if ($themeColor !== '' && !in_array($themeColor, $allowedThemes, true)) {
            $this->jsonResponse(['error' => 'Invalid theme color choice'], 400);
        }

        // 2. Validate and clean Notification Settings
        $notifInput = $input['notification_settings'] ?? [];
        if (!is_array($notifInput)) {
            $notifInput = [];
        }

        // Clean & cast notification toggles
        $cleanNotif = [
            'all' => !empty($notifInput['all']),
            'dm' => !empty($notifInput['dm']),
            'channels' => !empty($notifInput['channels']),
            'channel_requests' => !empty($notifInput['channel_requests']),
            'mentions' => !empty($notifInput['mentions']),
            'tone' => trim((string)($notifInput['tone'] ?? 'default'))
        ];

        // Allowed tones validation
        $allowedTones = ['default', 'chime', 'pop', 'ping', 'none'];
        if (!in_array($cleanNotif['tone'], $allowedTones, true)) {
            $cleanNotif['tone'] = 'default';
        }

        $db = Model::db();
        $db->beginTransaction();

        try {
            if ($themeColor !== '') {
                $stmt = $db->prepare("
                    UPDATE user_preferences 
                    SET theme_color = ?, notification_settings = ? 
                    WHERE user_id = ?
                ");
                $stmt->execute([$themeColor, json_encode($cleanNotif), $userId]);
                $_SESSION['chatrox_user']['theme_color'] = $themeColor;
            } else {
                $stmt = $db->prepare("
                    UPDATE user_preferences 
                    SET notification_settings = ? 
                    WHERE user_id = ?
                ");
                $stmt->execute([json_encode($cleanNotif), $userId]);
            }

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Preferences updated successfully',
                'preferences' => [
                    'theme_color' => $themeColor ?: ($_SESSION['chatrox_user']['theme_color'] ?? 'indigo'),
                    'notification_settings' => $cleanNotif
                ]
            ]);
        } catch (\Throwable $e) {
            $db->rollBack();
            \App\Core\ErrorHandler::logError($e);
            $this->jsonResponse(['error' => 'Failed to save settings. Please try again.'], 500);
        }
    }

    public function changePassword(): void
    {
        $user = Session::user();
        $userId = (int)($user['id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $displayName = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');

        if ($userId === 0 || $memberId === 0 || $workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $currentPassword = (string)($input['current_password'] ?? '');
        $newPassword = (string)($input['new_password'] ?? '');
        $confirmPassword = (string)($input['confirm_password'] ?? '');

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->jsonResponse(['error' => 'All password fields are required.'], 400);
        }

        if (strlen($newPassword) < 6) {
            $this->jsonResponse(['error' => 'New password must be at least 6 characters.'], 400);
        }

        if ($newPassword !== $confirmPassword) {
            $this->jsonResponse(['error' => 'New password and confirmation do not match.'], 400);
        }

        $db = Model::db();

        // 1. Fetch user's current password hash
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$userId]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userRow || !password_verify($currentPassword, $userRow['password_hash'])) {
            $this->jsonResponse(['error' => 'Incorrect current password.'], 400);
        }

        // 2. Hash new password and update
        $newHash = User::hashPassword($newPassword);

        $db->beginTransaction();
        try {
            // Update users table
            $stmtUser = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmtUser->execute([$newHash, $userId]);

            // Update user_security table
            $stmtSecurity = $db->prepare("
                INSERT INTO user_security (user_id, password_changed_at)
                VALUES (?, NOW())
                ON DUPLICATE KEY UPDATE password_changed_at = NOW(), updated_at = NOW()
            ");
            $stmtSecurity->execute([$userId]);

            // Write to Audit Log
            AuditLog::log(
                $workspaceId,
                $memberId,
                $displayName,
                'password_change',
                'Changed account password securely'
            );

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Password updated successfully.'
            ]);
        } catch (\Throwable $e) {
            $db->rollBack();
            \App\Core\ErrorHandler::logError($e);
            $this->jsonResponse(['error' => 'Failed to change password. Please try again.'], 500);
        }
    }

    public function getSessions(): void
    {
        $user = Session::user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $db = Model::db();
        $stmt = $db->prepare("
            SELECT id, ip_address, device_name, last_seen_at, created_at, session_token
            FROM user_sessions
            WHERE user_id = ? AND revoked_at IS NULL
            ORDER BY last_seen_at DESC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sessions = [];
        $currentSessionToken = $user['session_token'] ?? '';

        foreach ($rows as $row) {
            $sessions[] = [
                'id' => (int)$row['id'],
                'ip_address' => $row['ip_address'],
                'device_name' => $row['device_name'],
                'last_seen' => $row['last_seen_at'],
                'created_at' => $row['created_at'],
                'is_current' => ($row['session_token'] === $currentSessionToken)
            ];
        }

        $this->jsonResponse([
            'success' => true,
            'sessions' => $sessions
        ]);
    }

    public function deleteSession(): void
    {
        $user = Session::user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $input = $this->getRequestInput();
        $sessionId = (int)($input['session_id'] ?? 0);

        if ($sessionId === 0) {
            $this->jsonResponse(['error' => 'Invalid session ID'], 400);
            return;
        }

        $db = Model::db();
        
        // Find session to see if it belongs to user and retrieve its token
        $stmt = $db->prepare("SELECT session_token FROM user_sessions WHERE id = ? AND user_id = ? AND revoked_at IS NULL");
        $stmt->execute([$sessionId, $userId]);
        $sessionRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sessionRow) {
            $this->jsonResponse(['error' => 'Session not found or unauthorized'], 404);
            return;
        }

        $targetToken = $sessionRow['session_token'];

        // Soft-delete / revoke session in database
        $stmtUpdate = $db->prepare("UPDATE user_sessions SET revoked_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$sessionId]);

        // If revoked the current session, log out on PHP session side too
        $isCurrent = ($targetToken === ($user['session_token'] ?? ''));
        if ($isCurrent) {
            Session::logout();
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Session revoked successfully',
            'is_current' => $isCurrent
        ]);
    }

    public function updatePresence(): void
    {
        $user = Session::user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $input = $this->getRequestInput();
        $status = trim((string)($input['status'] ?? ''));

        if (!in_array($status, ['online', 'away', 'dnd'], true)) {
            $this->jsonResponse(['error' => 'Invalid status choice'], 400);
            return;
        }

        $success = \App\Models\UserPresence::setStatus($userId, $status);

        if ($success) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Presence status updated successfully',
                'status' => $status
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update presence status'], 500);
        }
    }
}
