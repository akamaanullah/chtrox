<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;

class Navigation extends Model
{
    public static function sidebarTabs(): array
    {
        $user = Session::user();
        $memberId = $user['workspace_member_id'] ?? 0;
        $workspaceId = $user['workspace_id'] ?? 0;

        $badgeCounts = ($memberId > 0 && $workspaceId > 0)
            ? self::navBadgeCounts()
            : ['dms' => 0, 'channels' => 0, 'activity' => 0];
        $dmUnread = $badgeCounts['dms'];
        $channelUnread = $badgeCounts['channels'];
        $activityUnread = $badgeCounts['activity'];

        $tabs = [
            ['id' => 'home', 'icon' => 'home', 'label' => 'Home'],
            ['id' => 'dms', 'icon' => 'message-square-text', 'label' => 'Dms'],
            ['id' => 'channels', 'icon' => 'hash', 'label' => 'Channels'],
            ['id' => 'people', 'icon' => 'users-round', 'label' => 'People'],
            ['id' => 'activity', 'icon' => 'bell-ring', 'label' => 'Activity'],
            ['id' => 'more', 'icon' => 'more-horizontal', 'label' => 'More', 'is_more' => true],
        ];

        // Add badges if they have unread messages/notifications
        if ($dmUnread > 0) {
            $tabs[1]['badge'] = $dmUnread;
        }
        if ($channelUnread > 0) {
            $tabs[2]['badge'] = $channelUnread;
        }
        if ($activityUnread > 0) {
            $tabs[4]['badge'] = $activityUnread;
        }

        return $tabs;
    }

    /** @return array{dms: int, channels: int, activity: int} */
    public static function navBadgeCounts(): array
    {
        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        $counts = ['dms' => 0, 'channels' => 0, 'activity' => 0];

        if ($memberId === 0 || $workspaceId === 0) {
            return $counts;
        }

        $cacheKey = "nav_badges_{$memberId}_{$workspaceId}";
        $cached = \App\Helpers\Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $db = self::db();

        // Combine DM and Channel unread counts into a single query using joins
        $unreadStmt = $db->prepare("
            SELECT 
                c.type,
                COUNT(m.id) as unread_count
            FROM conversations c
            LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = c.id AND crc.workspace_member_id = :member_id
            LEFT JOIN conversation_participants cp ON cp.conversation_id = c.id AND cp.workspace_member_id = :member_id AND cp.left_at IS NULL
            LEFT JOIN channel_members cm ON cm.channel_id = c.channel_id AND cm.workspace_member_id = :member_id AND cm.left_at IS NULL
            JOIN messages m ON m.conversation_id = c.id
              AND m.sender_id != :member_id
              AND m.deleted_for_everyone_at IS NULL
              AND (crc.last_read_message_id IS NULL OR m.id > crc.last_read_message_id)
            WHERE c.workspace_id = :workspace_id
              AND (
                  (c.type IN ('dm', 'group_dm') AND cp.id IS NOT NULL)
                  OR
                  (c.type = 'channel' AND cm.id IS NOT NULL)
              )
            GROUP BY c.type
        ");
        $unreadStmt->execute([
            'member_id' => $memberId,
            'workspace_id' => $workspaceId,
        ]);
        $unreadResults = $unreadStmt->fetchAll();

        foreach ($unreadResults as $row) {
            $type = $row['type'];
            $count = (int)$row['unread_count'];
            if ($type === 'dm' || $type === 'group_dm') {
                $counts['dms'] += $count;
            } elseif ($type === 'channel') {
                $counts['channels'] += $count;
            }
        }

        $activityStmt = $db->prepare("
            SELECT COUNT(*)
            FROM notifications
            WHERE recipient_id = ? AND read_at IS NULL AND workspace_id = ?
        ");
        $activityStmt->execute([$memberId, $workspaceId]);
        $counts['activity'] = (int)$activityStmt->fetchColumn();

        \App\Helpers\Cache::set($cacheKey, $counts, 30);

        return $counts;
    }
}
