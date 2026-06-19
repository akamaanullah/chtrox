<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;
use PDO;

class ChannelConversation extends Model
{
    public const INITIAL_VISIBLE = 20;

    public static function resolveChannel(string $slug): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        
        $stmt = self::db()->prepare("
            SELECT c.*, conv.id as conversation_id
            FROM channels c
            JOIN conversations conv ON conv.channel_id = c.id
            WHERE c.workspace_id = ? AND c.slug = ? AND c.status = 'active'
        ");
        $stmt->execute([$workspaceId, $slug]);
        $channel = $stmt->fetch();

        if (!$channel) {
            // Fallback to the first default channel (e.g. #general)
            $stmt = self::db()->prepare("
                SELECT c.*, conv.id as conversation_id
                FROM channels c
                JOIN conversations conv ON conv.channel_id = c.id
                WHERE c.workspace_id = ? AND c.is_default = 1 AND c.status = 'active'
                ORDER BY c.id ASC
                LIMIT 1
            ");
            $stmt->execute([$workspaceId]);
            $channel = $stmt->fetch();
        }

        if (!$channel) {
            return [
                'channel_id' => 'general',
                'active_channel' => [
                    'id' => 0,
                    'name' => '#general',
                    'slug' => 'general',
                    'avatar' => 'G',
                    'stat' => '0 TEAM MEMBERS ACTIVE',
                    'description' => 'Default general channel'
                ]
            ];
        }

        return [
            'channel_id' => $channel['slug'],
            'active_channel' => [
                'id' => $channel['id'],
                'conversation_id' => $channel['conversation_id'],
                'name' => '#' . $channel['name'],
                'slug' => $channel['slug'],
                'avatar' => strtoupper(substr($channel['name'], 0, 2)),
                'stat' => $channel['member_count'] . ' TEAM MEMBERS ACTIVE',
                'description' => $channel['description']
            ]
        ];
    }

    public static function sidebarDisplayItems(): array
    {
        $user = Session::user();
        $memberId = $user['workspace_member_id'] ?? 0;
        $workspaceId = $user['workspace_id'] ?? 0;
        $db = self::db();

        if ($memberId === 0 || $workspaceId === 0) {
            return [];
        }

        // Fetch all active channels in this workspace that the member has joined
        $stmt = $db->prepare("
            SELECT 
                c.id, 
                c.slug, 
                c.name, 
                c.member_count,
                conv.id as conversation_id,
                conv.last_message_at,
                (
                    SELECT COUNT(*) 
                    FROM messages m
                    LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = conv.id AND crc.workspace_member_id = :member_id
                    WHERE m.conversation_id = conv.id
                      AND m.sender_id != :member_id
                      AND m.deleted_for_everyone_at IS NULL
                      AND (crc.last_read_message_id IS NULL OR m.id > crc.last_read_message_id)
                ) as unread_count
            FROM channels c
            JOIN channel_members cm ON c.id = cm.channel_id AND cm.workspace_member_id = :member_id AND cm.left_at IS NULL
            JOIN conversations conv ON conv.channel_id = c.id
            WHERE c.workspace_id = :workspace_id AND c.status = 'active'
            ORDER BY c.name ASC
        ");
        $stmt->execute([
            'member_id' => $memberId,
            'workspace_id' => $workspaceId
        ]);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $words = preg_split('/[-_\s]+/', $row['name']);
            $initials = '';
            foreach ($words as $w) {
                $initials .= strtoupper(substr($w, 0, 1));
            }
            $initials = substr($initials, 0, 2);
            if (empty($initials)) {
                $initials = '#';
            }

            $lastMsgAt = $row['last_message_at'];
            $timeLabel = '';
            if ($lastMsgAt) {
                $timeLabel = self::formatMessageTime($lastMsgAt);
            }

            $items[] = [
                'id' => $row['slug'],
                'channel_id' => $row['id'],
                'initials' => $initials,
                'title' => '#' . $row['name'],
                'meta' => $row['member_count'] . ' members',
                'time' => $timeLabel,
                'badge' => (int) $row['unread_count'],
            ];
        }

        return $items;
    }

    public static function heroCards(): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        
        $stmt = self::db()->prepare("
            SELECT c.* 
            FROM channels c
            WHERE c.workspace_id = :workspace_id 
              AND c.status = 'active' 
              AND c.visibility = 'public'
            ORDER BY c.is_default DESC, c.id ASC
            LIMIT 2
        ");
        $stmt->execute([
            'workspace_id' => $workspaceId
        ]);
        $rows = $stmt->fetchAll();
        
        $cards = [];
        $iconMap = [
            'general' => 'hash',
            'development-announcements' => 'code',
            'announcements' => 'megaphone',
            'engineering' => 'code',
            'design' => 'palette',
            'design-huddle' => 'palette',
            'marketing' => 'megaphone',
            'random' => 'message-circle'
        ];
        
        foreach ($rows as $row) {
            $cards[] = [
                'id' => $row['slug'],
                'icon' => $iconMap[$row['slug']] ?? 'hash',
                'name' => '#' . $row['name'],
                'stat' => $row['member_count'] . ' TEAM MEMBERS ACTIVE',
            ];
        }
        
        return $cards;
    }

    public static function commonMedia(): array
    {
        return MediaAssets::commonMedia();
    }

    public static function messages(string $channelSlug): array
    {
        $user = Session::user();
        $memberId = $user['workspace_member_id'] ?? 0;
        $workspaceId = $user['workspace_id'] ?? 0;
        $db = self::db();

        $stmt = $db->prepare("
            SELECT conv.id 
            FROM conversations conv
            JOIN channels c ON conv.channel_id = c.id
            WHERE c.workspace_id = ? AND c.slug = ?
        ");
        $stmt->execute([$workspaceId, $channelSlug]);
        $conversationId = (int) $stmt->fetchColumn();

        if ($conversationId === 0) {
            return [];
        }

        $stmt = $db->prepare("
            SELECT 
                m.*,
                u.first_name,
                u.last_name,
                u.avatar_path,
                (m.sender_id = :member_id) as is_me
            FROM messages m
            JOIN workspace_members wm ON m.sender_id = wm.id
            JOIN users u ON wm.user_id = u.id
            WHERE m.conversation_id = :conversation_id AND m.deleted_for_everyone_at IS NULL
            ORDER BY m.id DESC
            LIMIT 100
        ");
        $stmt->execute([
            'conversation_id' => $conversationId,
            'member_id' => $memberId
        ]);
        $rows = $stmt->fetchAll();

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = [
                'id' => $row['id'],
                'side' => $row['is_me'] ? 'me' : 'them',
                'sender' => $row['first_name'] . ' ' . $row['last_name'],
                'text' => $row['body'],
                'time' => self::formatMessageTime($row['created_at']),
                'edited' => $row['edited_at'] !== null,
                'avatar' => $row['avatar_path'] ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150',
                'reactions' => self::getMessageReactions($row['id'], $memberId),
                'attachments' => self::getMessageAttachments($row['id']),
                'reply_to_id' => $row['reply_to_id'],
                'reply_snippet' => $row['reply_to_id'] ? self::getReplySnippet($row['reply_to_id']) : null,
                'message_type' => $row['message_type'],
                'is_forwarded' => !empty($row['forwarded_from_message_id']),
            ];
        }

        return self::applyChannelReadReceipts($messages, $conversationId);
    }

    public static function getReplySnippet(int $replyToId): string
    {
        $stmt = self::db()->prepare("SELECT body, message_type FROM messages WHERE id = ?");
        $stmt->execute([$replyToId]);
        $row = $stmt->fetch();
        if (!$row) return 'Message';
        if ($row['message_type'] === 'file') return 'File';
        if ($row['message_type'] === 'gif') return 'Photo';
        $text = strip_tags($row['body']);
        if (strlen($text) > 80) $text = substr($text, 0, 80) . '…';
        return $text;
    }

    public static function getMessageReactions(int $messageId, int $currentMemberId): array
    {
        $stmt = self::db()->prepare("
            SELECT 
                emoji,
                COUNT(*) as count,
                MAX(CASE WHEN workspace_member_id = ? THEN 1 ELSE 0 END) as reacted
            FROM message_reactions
            WHERE message_id = ?
            GROUP BY emoji
        ");
        $stmt->execute([$currentMemberId, $messageId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getMessageAttachments(int $messageId): array
    {
        $stmt = self::db()->prepare("
            SELECT f.id, f.original_name, f.mime_type, f.extension, f.size_bytes, f.category
            FROM message_attachments ma
            JOIN files f ON f.id = ma.file_id
            WHERE ma.message_id = ?
        ");
        $stmt->execute([$messageId]);
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($attachments as &$att) {
            $att['url'] = BASE_URL . '/files/download/' . $att['id'];
        }
        return $attachments;
    }

    public static function channelMembers(string $channelSlug = 'general'): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        
        $stmt = self::db()->prepare("
            SELECT u.first_name, u.last_name, u.avatar_path
            FROM channel_members cm
            JOIN channels c ON cm.channel_id = c.id
            JOIN workspace_members wm ON cm.workspace_member_id = wm.id
            JOIN users u ON wm.user_id = u.id
            WHERE c.workspace_id = ? AND c.slug = ? AND cm.left_at IS NULL AND wm.status = 'active'
        ");
        $stmt->execute([$workspaceId, $channelSlug]);
        $rows = $stmt->fetchAll();

        $members = [];
        foreach ($rows as $row) {
            $members[] = [
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'avatar' => $row['avatar_path'] ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150',
            ];
        }

        return $members;
    }

    private static function applyChannelReadReceipts(array $messages, int $conversationId): array
    {
        $user = Session::user();
        $memberId = $user['workspace_member_id'] ?? 0;
        $db = self::db();

        $stmt = $db->prepare("
            SELECT 
                wm.id as member_id,
                u.first_name,
                u.last_name,
                u.avatar_path,
                crc.last_read_message_id,
                crc.last_read_at
            FROM channel_members cm
            JOIN channels c ON cm.channel_id = c.id
            JOIN conversations conv ON conv.channel_id = c.id
            JOIN workspace_members wm ON cm.workspace_member_id = wm.id
            JOIN users u ON wm.user_id = u.id
            LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = conv.id AND crc.workspace_member_id = wm.id
            WHERE conv.id = :conversation_id 
              AND wm.id != :member_id
              AND cm.left_at IS NULL
        ");
        $stmt->execute([
            'conversation_id' => $conversationId,
            'member_id' => $memberId
        ]);
        $channelMembers = $stmt->fetchAll();
        $totalMembersCount = count($channelMembers) + 1;

        foreach ($messages as $i => $message) {
            if (($message['side'] ?? '') !== 'me') {
                continue;
            }

            $messageId = $message['id'] ?? 0;
            $readBy = [];
            $notRead = [];

            foreach ($channelMembers as $m) {
                $name = $m['first_name'] . ' ' . $m['last_name'];
                $avatar = $m['avatar_path'] ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150';
                
                if ($m['last_read_message_id'] !== null && $m['last_read_message_id'] >= $messageId) {
                    $readBy[] = [
                        'name' => $name,
                        'avatar' => $avatar,
                        'read_at' => date('h:i A', strtotime($m['last_read_at'])),
                    ];
                } else {
                    $notRead[] = $name;
                }
            }

            $messages[$i]['channel_read'] = [
                'member_count' => $totalMembersCount,
                'read_count' => count($readBy) + 1,
                'read_by' => array_merge([
                    [
                        'name' => 'You',
                        'avatar' => $user['avatar_path'] ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150',
                        'read_at' => 'Just now'
                    ]
                ], $readBy),
                'not_read' => $notRead,
            ];
        }

        return $messages;
    }

    private static function formatMessageTime(string $timestamp): string
    {
        $time = strtotime($timestamp);
        $date = date('Y-m-d', $time);
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('yesterday'));

        if ($date === $today) {
            return date('h:i A', $time);
        } elseif ($date === $yesterday) {
            return 'Yesterday ' . date('h:i A', $time);
        } else {
            return date('M j, h:i A', $time);
        }
    }
}
