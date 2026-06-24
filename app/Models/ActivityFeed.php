<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;

class ActivityFeed extends Model
{
    public static function items(): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;
        $currentRole = $user['role'] ?? 'member';

        if ($workspaceId === 0 || $memberId === 0) {
            return [];
        }

        $db = self::db();
        $stmt = $db->prepare("
            SELECT 
                n.id,
                n.type AS notif_type,
                n.title,
                n.body,
                n.body_html,
                n.reference_type,
                n.reference_id,
                n.created_at,
                u.first_name,
                u.last_name,
                u.avatar_path,
                u.username AS actor_username,
                c.type AS conv_type,
                ch.slug AS channel_slug,
                cm_current.role AS current_channel_role,
                cjr.id AS request_id
            FROM notifications n
            LEFT JOIN workspace_members wm ON wm.id = n.actor_id
            LEFT JOIN users u ON u.id = wm.user_id
            LEFT JOIN messages m ON m.id = n.reference_id AND n.reference_type = 'message'
            LEFT JOIN conversations c ON c.id = m.conversation_id
            LEFT JOIN channels ch ON ch.id = c.channel_id OR (ch.id = n.reference_id AND n.reference_type = 'channel')
            LEFT JOIN channel_members cm_current ON cm_current.channel_id = n.reference_id
                AND cm_current.workspace_member_id = ?
                AND cm_current.left_at IS NULL
            LEFT JOIN channel_join_requests cjr ON cjr.channel_id = n.reference_id
                AND cjr.workspace_member_id = n.actor_id
                AND cjr.status = 'pending'
            WHERE n.recipient_id = ?
              AND n.workspace_id = ?
              AND n.deleted_at IS NULL
              AND NOT (n.type = 'channel_join' AND n.actor_id = n.recipient_id)
            ORDER BY n.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$memberId, $memberId, $workspaceId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            // Determine type of card for view logic (e.g. 'user', 'missed-call', 'system')
            $type = 'system';
            if ($row['notif_type'] === 'mention' || $row['notif_type'] === 'file_share' || $row['notif_type'] === 'reaction') {
                $type = 'user';
            } elseif ($row['notif_type'] === 'missed_call') {
                $type = 'missed-call';
            } elseif ($row['notif_type'] === 'channel_join') {
                $type = 'channel-join';
            }

            if ($row['notif_type'] === 'channel_join' && !empty($row['request_id']) && !in_array($row['current_channel_role'] ?? '', ['owner', 'admin'], true)) {
                continue;
            }

            // Determine display name
            $name = 'Chatrox System';
            if ($row['first_name']) {
                $name = $row['first_name'] . ' ' . $row['last_name'];
            } elseif ($row['title']) {
                $name = $row['title'];
            }

            // Determine avatar
            $avatar = DEFAULT_AVATAR_URL;
            if ($row['avatar_path']) {
                $avatar = $row['avatar_path'];
            }

            // Determine path for click navigation
            $path = '';
            if ($row['conv_type'] === 'channel' && $row['channel_slug']) {
                $path = 'channels/' . $row['channel_slug'];
            } elseif ($row['reference_type'] === 'channel' && $row['channel_slug']) {
                // For channel-based notifications (like join requests)
                $path = 'channels/' . $row['channel_slug'];
            } elseif ($row['conv_type'] === 'dm' && $row['actor_username']) {
                $path = 'dms/' . $row['actor_username'];
            }

            // Format timestamp nicely
            $time = date('M j, h:i A', strtotime($row['created_at']));

            $items[] = [
                'id' => (int)$row['id'],
                'type' => $type,
                'notif_type' => $row['notif_type'],
                'name' => $name,
                'avatar' => $avatar,
                'time' => $time,
                'body' => $row['body'],
                'body_html' => $row['body_html'] ?: null,
                'path' => $path,
                'reference_type' => $row['reference_type'],
                'reference_id' => (int)$row['reference_id'],
                'current_channel_role' => $row['current_channel_role'] ?? null,
                'request_id' => $row['request_id'] ? (int)$row['request_id'] : null
            ];
        }

        return $items;
    }
}
