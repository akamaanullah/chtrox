<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;
use PDO;

class AdminOverview extends Model
{
    public static function greeting(): array
    {
        $admin = Session::adminUser();
        $name = $admin ? ($admin['first_name'] . ' ' . $admin['last_name']) : 'ChatRox Admin';
        return [
            'user_name' => $name,
            'date_label' => date('l, M j, Y'),
        ];
    }

    public static function stats(): array
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);
        $memberId = (int)($admin['workspace_member_id'] ?? 0);

        if ($workspaceId === 0 || $memberId === 0) {
            return [
                'members' => 0,
                'channels' => 0,
                'online' => 0,
                'unread' => 0,
                'files' => 0,
                'activity_new' => 0,
            ];
        }

        $cacheKey = "admin_stats_{$workspaceId}_{$memberId}";
        $cached = \App\Helpers\Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $db = self::db();

        // Consolidate main counts into a single query
        $stmt = $db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM workspace_members WHERE workspace_id = :ws_id AND status = 'active') as members_count,
                (SELECT COUNT(*) FROM channels WHERE workspace_id = :ws_id AND status = 'active') as channels_count,
                (SELECT COUNT(*) FROM workspace_members wm WHERE wm.workspace_id = :ws_id AND wm.status = 'active' AND (SELECT status FROM user_presence WHERE user_id = wm.user_id ORDER BY last_seen_at DESC, updated_at DESC LIMIT 1) = 'online') as online_count,
                (SELECT COUNT(*) FROM files WHERE workspace_id = :ws_id AND deleted_at IS NULL) as files_count,
                (SELECT COUNT(*) FROM audit_logs WHERE workspace_id = :ws_id) as activity_count
        ");
        $stmt->execute(['ws_id' => $workspaceId]);
        $countsRow = $stmt->fetch(PDO::FETCH_ASSOC);

        $membersCount = (int)($countsRow['members_count'] ?? 0);
        $channelsCount = (int)($countsRow['channels_count'] ?? 0);
        $onlineCount = (int)($countsRow['online_count'] ?? 0);
        $filesCount = (int)($countsRow['files_count'] ?? 0);
        $activityCount = (int)($countsRow['activity_count'] ?? 0);

        // 5. Unread messages count for the admin
        $unreadMessages = 0;

        // Unread in channels
        $stmt = $db->prepare("
            SELECT COUNT(m.id) 
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            JOIN channel_members cm ON cm.channel_id = c.channel_id AND cm.workspace_member_id = ? AND cm.left_at IS NULL
            LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = c.id AND crc.workspace_member_id = ?
            WHERE c.workspace_id = ? AND m.deleted_for_everyone_at IS NULL AND m.sender_id != ?
              AND (crc.last_read_message_id IS NULL OR m.id > crc.last_read_message_id)
        ");
        $stmt->execute([$memberId, $memberId, $workspaceId, $memberId]);
        $unreadMessages += (int)$stmt->fetchColumn();

        // Unread in DMs
        $stmt = $db->prepare("
            SELECT COUNT(m.id) 
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            JOIN conversation_participants cp ON cp.conversation_id = c.id AND cp.workspace_member_id = ? AND cp.left_at IS NULL
            LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = c.id AND crc.workspace_member_id = ?
            WHERE c.workspace_id = ? AND m.deleted_for_everyone_at IS NULL AND m.sender_id != ?
              AND (crc.last_read_message_id IS NULL OR m.id > crc.last_read_message_id)
        ");
        $stmt->execute([$memberId, $memberId, $workspaceId, $memberId]);
        $unreadMessages += (int)$stmt->fetchColumn();

        $stats = [
            'members' => $membersCount,
            'channels' => $channelsCount,
            'online' => $onlineCount,
            'unread' => $unreadMessages,
            'files' => $filesCount,
            'activity_new' => $activityCount,
        ];

        \App\Helpers\Cache::set($cacheKey, $stats, 60);

        return $stats;
    }

    public static function announcements(int $limit = 50, int $offset = 0): array
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            return [];
        }

        $limit = (int)$limit;
        $offset = (int)$offset;
        $stmt = self::db()->prepare("
            SELECT a.*, u.first_name, u.last_name, u.avatar_path,
                   CONCAT(u.first_name, ' ', u.last_name) as admin_name
            FROM announcements a
            LEFT JOIN workspace_members wm ON wm.id = a.created_by
            LEFT JOIN users u ON u.id = wm.user_id
            WHERE a.workspace_id = ? AND a.deleted_at IS NULL
            ORDER BY a.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function members(int $limit = 50, int $offset = 0): array
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            return [];
        }

        $limit = (int)$limit;
        $offset = (int)$offset;
        $stmt = self::db()->prepare("
            SELECT wm.id as member_id, wm.role as workspace_role, wm.job_title, wm.created_at as joined_date,
                   u.id as user_id, u.username, u.email, CONCAT(u.first_name, ' ', u.last_name) as display_name,
                   u.avatar_path, 
                   COALESCE((
                       SELECT status 
                       FROM user_presence 
                       WHERE user_id = u.id 
                       ORDER BY last_seen_at DESC, updated_at DESC 
                       LIMIT 1
                   ), 'offline') as presence_status
            FROM workspace_members wm
            JOIN users u ON wm.user_id = u.id
            WHERE wm.workspace_id = ? AND wm.status = 'active'
            ORDER BY display_name ASC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$workspaceId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => $row['member_id'],
                'user_id' => $row['user_id'],
                'username' => $row['username'],
                'name' => $row['display_name'],
                'email' => $row['email'],
                'role' => ucfirst($row['workspace_role']),
                'status' => ucfirst($row['presence_status']),
                'join_date' => date('M d, Y', strtotime($row['joined_date'])),
                'avatar' => \App\Core\View::avatar($row['avatar_path']),
            ];
        }

        return $items;
    }

    public static function channels(int $limit = 50, int $offset = 0): array
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            return [];
        }

        $limit = (int)$limit;
        $offset = (int)$offset;
        $stmt = self::db()->prepare("
            SELECT c.*, 
                   (SELECT GROUP_CONCAT(workspace_member_id) FROM channel_members cm WHERE cm.channel_id = c.id AND cm.left_at IS NULL) as member_ids
            FROM channels c
            WHERE c.workspace_id = ? AND c.status = 'active'
            ORDER BY c.name ASC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function files(int $limit = 50, int $offset = 0): array
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);
        $memberId = (int)($admin['workspace_member_id'] ?? 0);

        if ($workspaceId === 0) {
            return [];
        }

        $limit = (int)$limit;
        $offset = (int)$offset;
        $stmt = self::db()->prepare("
            SELECT f.*, CONCAT(u.first_name, ' ', u.last_name) as shared_by, u.avatar_path as shared_avatar,
                   (f.uploaded_by = ?) as shared_by_you
            FROM files f
            LEFT JOIN workspace_members wm ON wm.id = f.uploaded_by
            LEFT JOIN users u ON u.id = wm.user_id
            WHERE f.workspace_id = ? AND f.deleted_at IS NULL
            ORDER BY f.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$memberId, $workspaceId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $files = [];
        foreach ($rows as $row) {
            $ext = strtolower($row['extension']);
            $icon = 'file';
            $iconClass = 'bg-gray';

            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'fig', 'webp'])) {
                $icon = 'file-image';
                $iconClass = 'bg-orange';
            } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'])) {
                $icon = 'file-text';
                $iconClass = 'bg-blue';
            } elseif (in_array($ext, ['xls', 'xlsx', 'csv', 'ods'])) {
                $icon = 'file-spreadsheet';
                $iconClass = 'bg-green';
            } elseif (in_array($ext, ['zip', 'tar', 'gz', 'rar', '7z'])) {
                $icon = 'file-archive';
                $iconClass = 'bg-orange';
            } elseif (in_array($ext, ['html', 'css', 'js', 'php', 'py', 'sh', 'sql', 'json'])) {
                $icon = 'file-code';
                $iconClass = 'bg-blue';
            }

            $files[] = [
                'id' => $row['id'],
                'name' => $row['original_name'],
                'extension' => $row['extension'],
                'mime_type' => $row['mime_type'],
                'category' => $row['category'],
                'icon' => $icon,
                'icon_class' => $iconClass,
                'shared_by' => $row['shared_by'] ?: 'System',
                'shared_avatar' => \App\Core\View::avatar($row['shared_avatar']),
                'shared_by_you' => (bool)$row['shared_by_you'],
                'date' => date('M d, Y', strtotime($row['created_at'])),
                'size' => \App\Helpers\FileUploadPolicy::formatSize($row['size_bytes']),
                'size_bytes' => $row['size_bytes'],
            ];
        }

        return $files;
    }

    public static function activity(int $limit = 100, int $offset = 0): array
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            return [];
        }

        $limit = (int)$limit;
        $offset = (int)$offset;
        $stmt = self::db()->prepare("
            SELECT al.*, u.avatar_path, u.username as actor_handle
            FROM audit_logs al
            LEFT JOIN workspace_members wm ON wm.id = al.actor_member_id
            LEFT JOIN users u ON u.id = wm.user_id
            WHERE al.workspace_id = ?
            ORDER BY al.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
