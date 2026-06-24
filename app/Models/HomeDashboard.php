<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;
use PDO;

class HomeDashboard extends Model
{
    public static function greeting(): array
    {
        $user = Session::user();
        $firstName = $user['first_name'] ?? 'User';
        $lastName = $user['last_name'] ?? '';
        $dateLabel = date('l, F j');

        return [
            'user_name' => trim($firstName . ' ' . $lastName),
            'date_label' => $dateLabel,
        ];
    }

    /** @return array{online: int, total: int} */
    public static function onlineMemberCounts(): array
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            return ['online' => 0, 'total' => 0];
        }

        $stmt = self::db()->prepare("
            SELECT
                SUM(CASE WHEN up.status = 'online' THEN 1 ELSE 0 END) as online_count,
                COUNT(*) as total_count
            FROM workspace_members wm
            JOIN user_presence up ON wm.user_id = up.user_id
            WHERE wm.workspace_id = ?
              AND wm.status = 'active'
        ");
        $stmt->execute([$workspaceId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'online' => (int)($counts['online_count'] ?? 0),
            'total' => (int)($counts['total_count'] ?? 0),
        ];
    }

    public static function unreadMessageCount(): int
    {
        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($memberId === 0 || $workspaceId === 0) {
            return 0;
        }

        $stmt = self::db()->prepare("
            SELECT COUNT(*)
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = c.id AND crc.workspace_member_id = :member_id
            WHERE m.workspace_id = :workspace_id
              AND m.sender_id != :member_id
              AND m.deleted_for_everyone_at IS NULL
              AND (
                  (c.type IN ('dm', 'group_dm') AND EXISTS (
                      SELECT 1 FROM conversation_participants cp
                      WHERE cp.conversation_id = c.id AND cp.workspace_member_id = :member_id AND cp.left_at IS NULL
                  ))
                  OR
                  (c.type = 'channel' AND EXISTS (
                      SELECT 1 FROM channel_members cm
                      WHERE cm.channel_id = c.channel_id AND cm.workspace_member_id = :member_id AND cm.left_at IS NULL
                  ))
              )
              AND (crc.last_read_message_id IS NULL OR m.id > crc.last_read_message_id)
        ");
        $stmt->execute([
            'member_id' => $memberId,
            'workspace_id' => $workspaceId,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /** @return array<string, mixed> */
    public static function liveSummary(): array
    {
        $online = self::onlineMemberCounts();

        return [
            'unread_count' => self::unreadMessageCount(),
            'online_count' => $online['online'],
            'total_members' => $online['total'],
            'inbox' => self::chatInboxCard(),
            'sidebar_dms' => self::sidebarDmPreview(),
            'sidebar_channels' => self::sidebarChannelPreview(),
            'sidebar_activity' => self::sidebarRecentActivity(),
            'announcements' => self::announcements(),
            'date_label' => self::greeting()['date_label'],
            'nav_badges' => Navigation::navBadgeCounts(),
        ];
    }

    public static function stats(): array
    {
        $unreadCount = self::unreadMessageCount();
        $onlineCounts = self::onlineMemberCounts();
        $onlineCount = $onlineCounts['online'];
        $totalMembersCount = $onlineCounts['total'];

        return [
            [
                'label' => 'Unread',
                'value' => (string) $unreadCount,
                'value_id' => 'homeUnreadCount',
                'footer' => 'Messages',
                'variant' => 'green',
                'overlay_icon' => 'message-square'
            ],
            [
                'label' => 'Online',
                'value' => (string) $onlineCount,
                'value_id' => 'homeOnlineCount',
                'footer' => 'of ' . $totalMembersCount . ' members',
                'footer_id' => 'homeOnlineFooter',
                'variant' => 'default',
                'footer_class' => 'text-primary',
                'overlay_icon' => 'users'
            ],
            [
                'label' => 'Focus Time',
                'value' => '0:00',
                'variant' => 'dark',
                'is_timer' => true,
                'overlay_icon' => 'timer'
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function searchTags(): array
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);

        $tags = [];
        if ($workspaceId > 0) {
            $stmt = self::db()->prepare("
                SELECT c.name, c.slug,
                       EXISTS (
                           SELECT 1 FROM channel_members cm
                           WHERE cm.channel_id = c.id
                             AND cm.workspace_member_id = ?
                             AND cm.left_at IS NULL
                       ) AS joined
                FROM channels c
                WHERE c.workspace_id = ? AND c.visibility = 'public' AND c.status = 'active'
                ORDER BY c.name ASC LIMIT 4
            ");
            $stmt->execute([$memberId, $workspaceId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $tags[] = [
                    'label' => '#' . $row['name'],
                    'type' => 'channel',
                    'query' => $row['name'],
                    'slug' => $row['slug'],
                    'joined' => !empty($row['joined']),
                ];
            }
        }

        if (count($tags) < 4 && $workspaceId > 0) {
            $stmt = self::db()->prepare("
                SELECT u.username, u.first_name, u.last_name
                FROM workspace_members wm
                JOIN users u ON u.id = wm.user_id
                WHERE wm.workspace_id = ? AND wm.status = 'active' AND wm.id != ?
                ORDER BY u.first_name ASC
                LIMIT ?
            ");
            $stmt->bindValue(1, $workspaceId, PDO::PARAM_INT);
            $stmt->bindValue(2, $memberId, PDO::PARAM_INT);
            $stmt->bindValue(3, 4 - count($tags), PDO::PARAM_INT);
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $tags[] = [
                    'label' => '@' . $row['username'],
                    'type' => 'person',
                    'query' => $row['username'],
                    'username' => $row['username'],
                ];
            }
        }

        return array_slice($tags, 0, 4);
    }

    public static function chatInboxCard(): array
    {
        $stats = self::inboxStats();

        $badge = $stats['online_count'] > 0
            ? $stats['online_count'] . ' ONLINE'
            : 'LIVE';

        if ($stats['unread_messages'] === 0) {
            $title = "You're all caught up!";
        } else {
            $msgLabel = $stats['unread_messages'] === 1 ? 'message' : 'messages';
            $chatLabel = $stats['unread_chats'] === 1 ? 'chat' : 'chats';
            $title = $stats['unread_messages'] . ' unread ' . $msgLabel . ' in ' . $stats['unread_chats'] . ' ' . $chatLabel;
        }

        return [
            'badge' => $badge,
            'label' => 'Chat Inbox',
            'title' => $title,
            'progress' => $stats['clear_percent'],
            'progress_label' => 'Inbox clear',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function sidebarDmPreview(int $limit = 2): array
    {
        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($memberId === 0 || $workspaceId === 0) {
            return [];
        }

        $items = [];
        $seen = [];

        foreach (DmsConversation::sidebarDisplayItems() as $row) {
            if (count($items) >= $limit) {
                break;
            }
            $presence = self::presenceForUsername($row['id'], $workspaceId);
            $messagePreview = trim((string)($row['preview'] ?? ''));
            $items[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'avatar' => $row['avatar'],
                'preview' => ($messagePreview !== '' && $messagePreview !== 'No messages yet')
                    ? $messagePreview
                    : $presence['label'],
                'presence_label' => $presence['label'],
                'is_online' => $presence['online'],
            ];
            $seen[$row['id']] = true;
        }

        if (count($items) < $limit) {
            foreach (DmsConversation::users() as $username => $person) {
                if (count($items) >= $limit) {
                    break;
                }
                if (isset($seen[$username])) {
                    continue;
                }
                $presence = self::presenceForMemberId((int)$person['id']);
                $items[] = [
                    'id' => $username,
                    'name' => $person['name'],
                    'avatar' => $person['avatar'],
                    'preview' => $presence['label'],
                    'is_online' => $presence['online'],
                ];
            }
        }

        return $items;
    }

    /** @return array<int, array<string, mixed>> */
    public static function sidebarChannelPreview(int $limit = 2): array
    {
        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($memberId === 0 || $workspaceId === 0) {
            return [];
        }

        $stmt = self::db()->prepare("
            SELECT
                c.id,
                c.slug,
                c.name,
                conv.last_message_at
            FROM channels c
            JOIN channel_members cm ON cm.channel_id = c.id
                AND cm.workspace_member_id = ?
                AND cm.left_at IS NULL
            JOIN conversations conv ON conv.channel_id = c.id
            WHERE c.workspace_id = ?
              AND c.status = 'active'
            ORDER BY COALESCE(conv.last_message_at, c.created_at) DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(2, $workspaceId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $onlineCount = self::channelOnlineCount((int)$row['id']);
            $items[] = [
                'slug' => $row['slug'],
                'name' => $row['name'],
                'preview' => $onlineCount . ' active now',
            ];
        }

        return $items;
    }

    /** @return array<int, array<string, mixed>> */
    public static function sidebarRecentActivity(int $limit = 2): array
    {
        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($memberId === 0 || $workspaceId === 0) {
            return [];
        }

        $stmt = self::db()->prepare("
            SELECT n.type, n.title, n.body, n.created_at
            FROM notifications n
            WHERE n.recipient_id = ?
              AND n.workspace_id = ?
              AND n.deleted_at IS NULL
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(2, $workspaceId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $stmt = self::db()->prepare("
                SELECT activity_type, message, created_at
                FROM audit_logs
                WHERE workspace_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $workspaceId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $items = [];
            foreach ($rows as $row) {
                $items[] = [
                    'name' => self::activityTypeLabel($row['activity_type']),
                    'preview' => self::truncateText($row['message'], 60),
                    'symbol' => 'pulse',
                    'time' => self::formatActivityTime($row['created_at']),
                ];
            }
            return $items;
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'name' => $row['title'] ?: self::notificationTypeLabel($row['type']),
                'preview' => self::truncateText($row['body'], 60),
                'symbol' => self::notificationSymbol($row['type']),
                'time' => self::formatActivityTime($row['created_at']),
            ];
        }

        return $items;
    }

    /** @return array{unread_messages: int, unread_chats: int, clear_percent: int, online_count: int} */
    private static function inboxStats(): array
    {
        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($memberId === 0 || $workspaceId === 0) {
            return [
                'unread_messages' => 0,
                'unread_chats' => 0,
                'clear_percent' => 0,
                'online_count' => 0,
            ];
        }

        $db = self::db();
        $stmt = $db->prepare("
            SELECT
                COALESCE(SUM(unread_count), 0) AS total_unread,
                SUM(CASE WHEN unread_count > 0 THEN 1 ELSE 0 END) AS unread_chats,
                SUM(CASE WHEN unread_count = 0 THEN 1 ELSE 0 END) AS cleared,
                COUNT(*) AS total
            FROM (
                SELECT (
                    SELECT COUNT(*)
                    FROM messages m
                    LEFT JOIN conversation_read_cursors crc
                        ON crc.conversation_id = c.id AND crc.workspace_member_id = :member_id
                    WHERE m.conversation_id = c.id
                      AND m.sender_id != :member_id
                      AND m.deleted_for_everyone_at IS NULL
                      AND (crc.last_read_message_id IS NULL OR m.id > crc.last_read_message_id)
                ) AS unread_count
                FROM conversations c
                WHERE c.workspace_id = :workspace_id
                  AND (
                      (c.type IN ('dm', 'group_dm') AND EXISTS (
                          SELECT 1 FROM conversation_participants cp
                          WHERE cp.conversation_id = c.id
                            AND cp.workspace_member_id = :member_id
                            AND cp.left_at IS NULL
                      ))
                      OR
                      (c.type = 'channel' AND EXISTS (
                          SELECT 1 FROM channel_members cm
                          WHERE cm.channel_id = c.channel_id
                            AND cm.workspace_member_id = :member_id
                            AND cm.left_at IS NULL
                      ))
                  )
            ) AS conv_stats
        ");
        $stmt->execute([
            'member_id' => $memberId,
            'workspace_id' => $workspaceId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = (int)($row['total'] ?? 0);
        $cleared = (int)($row['cleared'] ?? 0);
        $clearPercent = $total > 0 ? (int) round(($cleared / $total) * 100) : 100;

        $presenceStmt = $db->prepare("
            SELECT SUM(CASE WHEN up.status = 'online' THEN 1 ELSE 0 END) AS online_count
            FROM workspace_members wm
            JOIN user_presence up ON wm.user_id = up.user_id
            WHERE wm.workspace_id = :workspace_id
              AND wm.status = 'active'
        ");
        $presenceStmt->execute(['workspace_id' => $workspaceId]);
        $onlineCount = (int)($presenceStmt->fetchColumn() ?: 0);

        return [
            'unread_messages' => (int)($row['total_unread'] ?? 0),
            'unread_chats' => (int)($row['unread_chats'] ?? 0),
            'clear_percent' => $clearPercent,
            'online_count' => $onlineCount,
        ];
    }

    private static function channelOnlineCount(int $channelId): int
    {
        $stmt = self::db()->prepare("
            SELECT COUNT(DISTINCT cm.workspace_member_id)
            FROM channel_members cm
            JOIN workspace_members wm ON wm.id = cm.workspace_member_id AND wm.status = 'active'
            JOIN user_presence up ON up.user_id = wm.user_id AND up.status = 'online'
            WHERE cm.channel_id = ? AND cm.left_at IS NULL
        ");
        $stmt->execute([$channelId]);

        return (int)$stmt->fetchColumn();
    }

    /** @return array{label: string, online: bool} */
    private static function presenceForUsername(string $username, int $workspaceId): array
    {
        $stmt = self::db()->prepare("
            SELECT up.status, up.last_seen_at
            FROM users u
            JOIN workspace_members wm ON wm.user_id = u.id AND wm.workspace_id = ?
            JOIN user_presence up ON up.user_id = u.id
            WHERE u.username = ?
            LIMIT 1
        ");
        $stmt->execute([$workspaceId, $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return self::formatPresence($row['status'] ?? 'offline', $row['last_seen_at'] ?? null);
    }

    /** @return array{label: string, online: bool} */
    private static function presenceForMemberId(int $memberId): array
    {
        $stmt = self::db()->prepare("
            SELECT up.status, up.last_seen_at
            FROM workspace_members wm
            JOIN user_presence up ON up.user_id = wm.user_id
            WHERE wm.id = ?
            LIMIT 1
        ");
        $stmt->execute([$memberId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return self::formatPresence($row['status'] ?? 'offline', $row['last_seen_at'] ?? null);
    }

    /** @return array{label: string, online: bool} */
    private static function formatPresence(string $status, ?string $lastSeenAt): array
    {
        if ($status === 'online') {
            return ['label' => 'Active now', 'online' => true];
        }

        if (!$lastSeenAt) {
            return ['label' => 'Offline', 'online' => false];
        }

        $diff = time() - strtotime($lastSeenAt);
        if ($diff < 60) {
            return ['label' => 'Active just now', 'online' => false];
        }
        if ($diff < 3600) {
            return ['label' => 'Active ' . (int) floor($diff / 60) . 'm ago', 'online' => false];
        }
        if ($diff < 86400) {
            return ['label' => 'Active ' . (int) floor($diff / 3600) . 'h ago', 'online' => false];
        }

        return ['label' => 'Active ' . (int) floor($diff / 86400) . 'd ago', 'online' => false];
    }

    private static function notificationSymbol(string $type): string
    {
        return match ($type) {
            'mention', 'reply' => 'bell',
            'system', 'channel_join', 'project' => 'pulse',
            default => 'bell',
        };
    }

    private static function notificationTypeLabel(string $type): string
    {
        return match ($type) {
            'mention' => 'New Mention',
            'file_share', 'file_upload' => 'File Activity',
            'reaction' => 'New Reaction',
            'missed_call' => 'Missed Call',
            'channel_join' => 'Channel Update',
            'reply' => 'New Reply',
            'system' => 'System Update',
            default => 'Activity',
        };
    }

    private static function activityTypeLabel(string $type): string
    {
        return match ($type) {
            'workspace_update' => 'Workspace Update',
            'channel_create' => 'New Channel',
            'member_invite' => 'Member Invite',
            'message_delete' => 'Message Deleted',
            'file_delete' => 'File Deleted',
            default => ucwords(str_replace('_', ' ', strtolower($type))),
        };
    }

    private static function formatActivityTime(string $timestamp): string
    {
        $time = strtotime($timestamp);
        $today = date('Y-m-d');
        $date = date('Y-m-d', $time);

        if ($date === $today) {
            return 'Today at ' . date('h:i A', $time);
        }

        return date('M j, h:i A', $time);
    }

    private static function truncateText(string $text, int $maxLen): string
    {
        $text = trim(strip_tags($text));
        if (strlen($text) <= $maxLen) {
            return $text;
        }

        return substr($text, 0, $maxLen - 1) . '…';
    }

    public static function worldClocks(): array
    {
        // Static timezone configurations as requested by the user
        return [
            ['id' => 'pk', 'label' => 'Pakistan', 'timezone' => 'PK'],
            ['id' => 'hou', 'label' => 'Houston', 'timezone' => 'HOU'],
            ['id' => 'ny', 'label' => 'New York', 'timezone' => 'NY'],
            ['id' => 'phx', 'label' => 'Phoenix', 'timezone' => 'PHX'],
        ];
    }

    public static function announcements(): array
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            return [];
        }

        $stmt = self::db()->prepare("
            SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name
            FROM announcements a
            JOIN workspace_members wm ON wm.id = a.created_by
            JOIN users u ON u.id = wm.user_id
            WHERE a.workspace_id = ?
              AND NOW() BETWEEN a.start_date AND a.end_date
            ORDER BY a.created_at DESC
            LIMIT 3
        ");
        $stmt->execute([$workspaceId]);

        $announcements = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $announcements[] = self::formatAnnouncementRow($row);
        }

        return $announcements;
    }

    /** @param array<string, mixed> $row */
    private static function formatAnnouncementRow(array $row): array
    {
        $tag = (string)($row['tag'] ?? 'UPDATE');
        $iconMap = [
            'IMPORTANT' => '🚨',
            'CELEBRATION' => '🎂',
            'UPDATE' => '📢',
        ];

        return [
            'id' => (int)$row['id'],
            'icon' => $iconMap[$tag] ?? '📢',
            'tag' => $tag,
            'tag_class' => strtolower($tag),
            'title' => $row['title'],
            'body' => $row['message'],
            'date' => date('d/m/Y', strtotime($row['created_at'])),
            'posted_by' => $row['author_name'] ?? 'Workspace Admin',
            'posted_at' => date('M j, Y', strtotime($row['created_at'])),
        ];
    }
}
