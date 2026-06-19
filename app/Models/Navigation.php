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
        $db = self::db();

        $dmUnread = 0;
        $channelUnread = 0;
        $activityUnread = 0;

        if ($memberId > 0 && $workspaceId > 0) {
            // Count DM unread messages
            $dmStmt = $db->prepare("
                SELECT COUNT(*) 
                FROM messages m
                JOIN conversations c ON m.conversation_id = c.id
                LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = c.id AND crc.workspace_member_id = :member_id
                WHERE m.workspace_id = :workspace_id
                  AND m.sender_id != :member_id
                  AND m.deleted_for_everyone_at IS NULL
                  AND c.type IN ('dm', 'group_dm')
                  AND EXISTS (
                      SELECT 1 FROM conversation_participants cp 
                      WHERE cp.conversation_id = c.id AND cp.workspace_member_id = :member_id AND cp.left_at IS NULL
                  )
                  AND (crc.last_read_message_id IS NULL OR m.id > crc.last_read_message_id)
            ");
            $dmStmt->execute([
                'member_id' => $memberId,
                'workspace_id' => $workspaceId
            ]);
            $dmUnread = (int) $dmStmt->fetchColumn();

            // Count Channel unread messages
            $channelStmt = $db->prepare("
                SELECT COUNT(*) 
                FROM messages m
                JOIN conversations c ON m.conversation_id = c.id
                LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = c.id AND crc.workspace_member_id = :member_id
                WHERE m.workspace_id = :workspace_id
                  AND m.sender_id != :member_id
                  AND m.deleted_for_everyone_at IS NULL
                  AND c.type = 'channel'
                  AND EXISTS (
                      SELECT 1 FROM channel_members cm 
                      WHERE cm.channel_id = c.channel_id AND cm.workspace_member_id = :member_id AND cm.left_at IS NULL
                  )
                  AND (crc.last_read_message_id IS NULL OR m.id > crc.last_read_message_id)
            ");
            $channelStmt->execute([
                'member_id' => $memberId,
                'workspace_id' => $workspaceId
            ]);
            $channelUnread = (int) $channelStmt->fetchColumn();

            // Count unread notifications for Activity tab
            $activityStmt = $db->prepare("
                SELECT COUNT(*) 
                FROM notifications 
                WHERE recipient_id = ? AND read_at IS NULL AND workspace_id = ?
            ");
            $activityStmt->execute([$memberId, $workspaceId]);
            $activityUnread = (int) $activityStmt->fetchColumn();
        }

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
}
