<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;
use App\Helpers\GiphyUrl;
use App\Helpers\MessageEnricher;
use App\Helpers\SystemMessage;
use PDO;

class ChannelConversation extends Model
{
    public const INITIAL_VISIBLE = 20;
    public const INITIAL_LOAD = 30;

    public static function resolveChannel(string $slug): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        
        $stmt = self::db()->prepare("
            SELECT c.*, conv.id as conversation_id
            FROM channels c
            LEFT JOIN conversations conv ON conv.channel_id = c.id
            WHERE c.workspace_id = ? AND c.slug = ? AND c.status = 'active'
        ");
        $stmt->execute([$workspaceId, $slug]);
        $channel = $stmt->fetch();

        if (!$channel) {
            // Try to resolve by former slug
            $stmt = self::db()->prepare("
                SELECT c.*, conv.id as conversation_id
                FROM channels c
                LEFT JOIN conversations conv ON conv.channel_id = c.id
                WHERE c.workspace_id = ? AND FIND_IN_SET(?, c.former_slugs) AND c.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$workspaceId, $slug]);
            $channel = $stmt->fetch();
        }

        if (!$channel) {
            // Fallback to the first default channel (e.g. #general)
            $stmt = self::db()->prepare("
                SELECT c.*, conv.id as conversation_id
                FROM channels c
                LEFT JOIN conversations conv ON conv.channel_id = c.id
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
                    'conversation_id' => 0,
                    'name' => '#general',
                    'raw_name' => 'general',
                    'slug' => 'general',
                    'avatar' => 'G',
                    'stat' => '0 TEAM MEMBERS ACTIVE',
                    'description' => 'Default general channel',
                    'created_by' => 0,
                    'is_default' => 1
                ]
            ];
        }

        if ($channel && empty($channel['conversation_id'])) {
            $stmt = self::db()->prepare("
                INSERT INTO conversations (workspace_id, type, channel_id)
                VALUES (?, 'channel', ?)
            ");
            $stmt->execute([$workspaceId, $channel['id']]);
            $channel['conversation_id'] = (int)self::db()->lastInsertId();
        }

        $stmt = self::db()->prepare("SELECT COUNT(*) FROM channel_members cm JOIN workspace_members wm ON cm.workspace_member_id = wm.id WHERE cm.channel_id = ? AND cm.left_at IS NULL AND wm.status = 'active'");
        $stmt->execute([$channel['id']]);
        $activeMemberCount = (int)$stmt->fetchColumn();

        return [
            'channel_id' => $channel['slug'],
            'active_channel' => [
                'id' => $channel['id'],
                'conversation_id' => $channel['conversation_id'],
                'name' => '#' . $channel['name'],
                'raw_name' => $channel['name'],
                'slug' => $channel['slug'],
                'avatar' => strtoupper(substr($channel['name'], 0, 2)),
                'stat' => $activeMemberCount . ' TEAM MEMBERS ACTIVE',
                'description' => $channel['description'],
                'created_by' => (int)$channel['created_by'],
                'is_default' => (int)$channel['is_default']
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
                    WHERE m.conversation_id = conv.id
                      AND m.sender_id != :member_id
                      AND m.deleted_for_everyone_at IS NULL
                      AND (crc.last_read_message_id IS NULL OR m.id > crc.last_read_message_id)
                ) as unread_count
            FROM channels c
            JOIN channel_members cm ON c.id = cm.channel_id AND cm.workspace_member_id = :member_id AND cm.left_at IS NULL
            JOIN conversations conv ON conv.channel_id = c.id
            LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = conv.id AND crc.workspace_member_id = :member_id
            WHERE c.workspace_id = :workspace_id AND c.status = 'active'
            ORDER BY COALESCE(conv.last_message_at, conv.created_at) DESC
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
                'conversation_id' => (int)$row['conversation_id'],
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
                (m.sender_id = :member_id) as is_me,
                (mp.id IS NOT NULL) as is_pinned
            FROM messages m
            JOIN workspace_members wm ON m.sender_id = wm.id
            JOIN users u ON wm.user_id = u.id
            LEFT JOIN message_pins mp ON mp.message_id = m.id AND mp.conversation_id = m.conversation_id AND mp.pinned_by = :member_id
            WHERE m.conversation_id = :conversation_id
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id
                    AND mud.workspace_member_id = :member_id
              )
            ORDER BY m.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', self::INITIAL_LOAD, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows === []) {
            return [];
        }

        $messageIds = array_map(static fn(array $row): int => (int)$row['id'], $rows);
        $replyIds = [];
        foreach ($rows as $row) {
            if (!empty($row['reply_to_id'])) {
                $replyIds[] = (int)$row['reply_to_id'];
            }
        }

        $reactionsByMessage = MessageEnricher::batchReactions($db, $messageIds, $memberId);
        $attachmentsByMessage = MessageEnricher::batchAttachments($db, $messageIds);
        $replySnippets = MessageEnricher::batchReplySnippets($db, $replyIds);

        $messages = [];
        foreach ($rows as $row) {
            $deletedForEveryone = $row['deleted_for_everyone_at'] !== null;
            $messageId = (int)$row['id'];
            $messageType = GiphyUrl::resolveMessageType(
                (string)($row['message_type'] ?? 'text'),
                (string)($row['body'] ?? '')
            );
            $messages[] = [
                'id' => $messageId,
                'side' => $row['is_me'] ? 'me' : 'them',
                'sender' => $row['first_name'] . ' ' . $row['last_name'],
                'text' => $deletedForEveryone ? '' : $row['body'],
                'time' => self::formatMessageTime($row['created_at']),
                'created_at' => $row['created_at'],
                'edited' => !$deletedForEveryone && $row['edited_at'] !== null,
                'avatar' => \App\Core\View::avatar($row['avatar_path']),
                'reactions' => $deletedForEveryone ? [] : ($reactionsByMessage[$messageId] ?? []),
                'attachments' => $deletedForEveryone ? [] : ($attachmentsByMessage[$messageId] ?? []),
                'reply_to_id' => $deletedForEveryone ? null : $row['reply_to_id'],
                'reply_snippet' => (!$deletedForEveryone && !empty($row['reply_to_id']))
                    ? ($replySnippets[(int)$row['reply_to_id']] ?? 'Message')
                    : null,
                'message_type' => $messageType,
                'is_forwarded' => !$deletedForEveryone && !empty($row['forwarded_from_message_id']),
                'deleted_for_everyone' => $deletedForEveryone,
                'is_pinned' => !$deletedForEveryone && !empty($row['is_pinned']),
            ];
            self::applySystemMessagePresentation($messages[count($messages) - 1], $row);
        }

        return self::applyChannelReadReceipts($messages, $conversationId);
    }

    /** @return array{messages: array<int, array<string, mixed>>, has_more_before: bool, has_more_after: bool, oldest_message_id: int, newest_message_id: int, target_message_id: int} */
    public static function contextPage(int $conversationId, int $memberId, int $messageId, int $limit = 30): array
    {
        $empty = [
            'messages' => [],
            'has_more_before' => false,
            'has_more_after' => false,
            'oldest_message_id' => 0,
            'newest_message_id' => 0,
            'target_message_id' => $messageId,
        ];

        $limit = max(10, min(50, $limit));
        if ($conversationId <= 0 || $memberId <= 0 || $messageId <= 0) {
            return $empty;
        }

        $db = self::db();
        $stmt = $db->prepare('SELECT id FROM messages WHERE id = ? AND conversation_id = ? LIMIT 1');
        $stmt->execute([$messageId, $conversationId]);
        if (!$stmt->fetchColumn()) {
            return $empty;
        }

        $beforeLimit = max(1, (int)ceil($limit / 2));
        $afterLimit = max(0, (int)floor($limit / 2));

        $beforeRows = self::fetchContextRows($db, $conversationId, $memberId, $messageId, 'before', $beforeLimit);
        $afterRows = $afterLimit > 0
            ? self::fetchContextRows($db, $conversationId, $memberId, $messageId, 'after', $afterLimit)
            : [];

        $rows = array_merge(array_reverse($beforeRows), $afterRows);
        $messages = self::formatChannelMessageRows($rows, $memberId, $conversationId);

        $oldestId = !empty($messages) ? (int)$messages[0]['id'] : $messageId;
        $newestId = !empty($messages) ? (int)$messages[count($messages) - 1]['id'] : $messageId;

        return [
            'messages' => $messages,
            'has_more_before' => \App\Models\DmsConversation::hasOlderMessages($conversationId, $memberId, $oldestId),
            'has_more_after' => \App\Models\DmsConversation::hasNewerMessages($conversationId, $memberId, $newestId),
            'oldest_message_id' => $oldestId,
            'newest_message_id' => $newestId,
            'target_message_id' => $messageId,
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function fetchContextRows(
        \PDO $db,
        int $conversationId,
        int $memberId,
        int $pivotMessageId,
        string $direction,
        int $limit
    ): array {
        $sql = "
            SELECT
                m.*,
                u.first_name,
                u.last_name,
                u.avatar_path,
                (m.sender_id = :member_id) as is_me,
                (mp.id IS NOT NULL) as is_pinned
            FROM messages m
            JOIN workspace_members wm ON m.sender_id = wm.id
            JOIN users u ON wm.user_id = u.id
            LEFT JOIN message_pins mp ON mp.message_id = m.id AND mp.conversation_id = m.conversation_id AND mp.pinned_by = :member_id
            WHERE m.conversation_id = :conversation_id
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id AND mud.workspace_member_id = :member_id
              )
        ";

        if ($direction === 'before') {
            $sql .= ' AND m.id <= :pivot_id ORDER BY m.id DESC LIMIT :limit';
        } else {
            $sql .= ' AND m.id > :pivot_id ORDER BY m.id ASC LIMIT :limit';
        }

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':conversation_id', $conversationId, \PDO::PARAM_INT);
        $stmt->bindValue(':member_id', $memberId, \PDO::PARAM_INT);
        $stmt->bindValue(':pivot_id', $pivotMessageId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @param list<array<string, mixed>> $rows @return list<array<string, mixed>> */
    private static function formatChannelMessageRows(array $rows, int $memberId, int $conversationId): array
    {
        if ($rows === []) {
            return [];
        }

        $db = self::db();
        $messageIds = [];
        $replyIds = [];
        foreach ($rows as $row) {
            $messageIds[] = (int)$row['id'];
            if (!empty($row['reply_to_id'])) {
                $replyIds[] = (int)$row['reply_to_id'];
            }
        }

        $reactionsByMessage = MessageEnricher::batchReactions($db, $messageIds, $memberId);
        $attachmentsByMessage = MessageEnricher::batchAttachments($db, $messageIds);
        $replySnippets = MessageEnricher::batchReplySnippets($db, $replyIds);

        $messages = [];
        foreach ($rows as $row) {
            $deletedForEveryone = $row['deleted_for_everyone_at'] !== null;
            $messageId = (int)$row['id'];
            $messageType = GiphyUrl::resolveMessageType(
                (string)($row['message_type'] ?? 'text'),
                (string)($row['body'] ?? '')
            );
            $senderName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $rawBody = ($deletedForEveryone || ($row['message_type'] ?? '') === 'voice') ? '' : ($row['body'] ?? '');
            if ($rawBody !== '') {
                if ($messageType === 'gif') {
                    $body = trim($rawBody);
                } else {
                    $body = \App\Helpers\HtmlSanitizer::clean($rawBody);
                }
            } else {
                $body = '';
            }

            $messages[] = [
                'id' => $messageId,
                'side' => !empty($row['is_me']) ? 'me' : 'them',
                'sender' => $senderName,
                'sender_name' => $senderName,
                'text' => $body,
                'body' => $body,
                'time' => self::formatMessageTime($row['created_at']),
                'created_at' => $row['created_at'],
                'time_label' => self::formatMessageTime($row['created_at']),
                'edited' => !$deletedForEveryone && $row['edited_at'] !== null,
                'avatar' => \App\Core\View::avatar($row['avatar_path']),
                'reactions' => $deletedForEveryone ? [] : ($reactionsByMessage[$messageId] ?? []),
                'attachments' => $deletedForEveryone ? [] : ($attachmentsByMessage[$messageId] ?? []),
                'reply_to_id' => $deletedForEveryone ? null : $row['reply_to_id'],
                'reply_snippet' => (!$deletedForEveryone && !empty($row['reply_to_id']))
                    ? ($replySnippets[(int)$row['reply_to_id']] ?? 'Message')
                    : null,
                'message_type' => $messageType,
                'is_forwarded' => !$deletedForEveryone && !empty($row['forwarded_from_message_id']),
                'deleted_for_everyone' => $deletedForEveryone,
                'is_pinned' => !$deletedForEveryone && !empty($row['is_pinned']),
            ];
            self::applySystemMessagePresentation($messages[count($messages) - 1], $row);
        }

        return self::applyChannelReadReceipts($messages, $conversationId);
    }

    /** @param array<string, mixed> $message @param array<string, mixed> $row */
    private static function applySystemMessagePresentation(array &$message, array $row): void
    {
        if (($row['message_type'] ?? '') !== 'system') {
            return;
        }

        $senderName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $systemText = SystemMessage::formatDisplay(
            (string)($row['body'] ?? ''),
            $senderName,
            (string)($row['created_at'] ?? '')
        );

        $message['side'] = 'system';
        $message['system_text'] = $systemText;
        $message['text'] = '';
        $message['body'] = '';
        $message['reactions'] = [];
        $message['attachments'] = [];
        $message['reply_to_id'] = null;
        $message['reply_snippet'] = null;
        $message['is_pinned'] = false;
        $message['is_forwarded'] = false;
        $message['created_at'] = $row['created_at'] ?? '';
    }

    public static function getReplySnippet(int $replyToId): string
    {
        return \App\Helpers\MessageEnricher::getReplySnippet($replyToId);
    }

    public static function getMessageReactions(int $messageId, int $currentMemberId): array
    {
        return \App\Helpers\MessageEnricher::getMessageReactions($messageId, $currentMemberId);
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
            SELECT wm.id as member_id, u.first_name, u.last_name, u.avatar_path, u.username, cm.role as channel_role
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
                'member_id' => (int)$row['member_id'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'avatar' => \App\Core\View::avatar($row['avatar_path']),
                'username' => $row['username'],
                'channel_role' => $row['channel_role'] ?? 'member',
            ];
        }

        return $members;
    }

    public static function getWorkspaceMembers(): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        if ($workspaceId === 0) {
            return [];
        }

        $stmt = self::db()->prepare("
            SELECT wm.id as member_id, u.first_name, u.last_name, u.avatar_path, wm.role, u.username
            FROM workspace_members wm
            JOIN users u ON wm.user_id = u.id
            WHERE wm.workspace_id = ? AND wm.status = 'active'
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function applyChannelReadReceipts(array $messages, int $conversationId): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;
        
        if ($workspaceId === 0 || $memberId === 0) {
            return $messages;
        }

        // HIGH-08: Check if there are any messages sent by the current user
        $hasMyMessages = false;
        foreach ($messages as $message) {
            if (($message['side'] ?? '') === 'me') {
                $hasMyMessages = true;
                break;
            }
        }

        // If the user has no messages in this batch, skip the heavy queries and loop entirely
        if (!$hasMyMessages) {
            return $messages;
        }

        $db = self::db();

        $stmt = $db->prepare("
            SELECT 
                wm.id AS workspace_member_id,
                wm.role,
                u.first_name,
                u.last_name,
                u.avatar_path,
                MAX(crc.last_read_message_id) AS last_read_message_id,
                MAX(crc.last_read_at) AS last_read_at
            FROM channel_members cm
            JOIN channels c ON cm.channel_id = c.id
            JOIN conversations conv ON conv.channel_id = c.id
            JOIN workspace_members wm ON cm.workspace_member_id = wm.id
            JOIN users u ON wm.user_id = u.id
            LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = conv.id AND crc.workspace_member_id = wm.id
            WHERE conv.id = :conversation_id 
              AND wm.id != :member_id
              AND cm.left_at IS NULL
            GROUP BY wm.id, wm.role, u.first_name, u.last_name, u.avatar_path
        ");
        $stmt->execute([
            'conversation_id' => $conversationId,
            'member_id' => $memberId
        ]);
        $channelMembers = $stmt->fetchAll();
        $otherMembersCount = count($channelMembers);

        foreach ($messages as $i => $message) {
            if (($message['side'] ?? '') !== 'me') {
                continue;
            }

            $messageId = $message['id'] ?? 0;
            $readBy = [];
            $notRead = [];

            foreach ($channelMembers as $m) {
                $name = $m['first_name'] . ' ' . $m['last_name'];
                $avatar = \App\Core\View::avatar($m['avatar_path']);
                
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
                'member_count' => $otherMembersCount,
                'read_count' => count($readBy),
                'read_by' => $readBy,
                'not_read' => $notRead,
            ];
        }

        return $messages;
    }

    /** @return array{messages: array<int, array<string, mixed>>, has_more: bool, oldest_message_id: int} */
    public static function historyPage(int $conversationId, int $memberId, int $beforeMessageId, int $limit = 30): array
    {
        $limit = max(1, min(50, $limit));
        if ($conversationId <= 0 || $memberId <= 0 || $beforeMessageId <= 0) {
            return ['messages' => [], 'has_more' => false, 'oldest_message_id' => 0];
        }

        $db = self::db();
        $stmt = $db->prepare("
            SELECT 
                m.*,
                u.first_name,
                u.last_name,
                u.avatar_path,
                (m.sender_id = :member_id) as is_me,
                (mp.id IS NOT NULL) as is_pinned
            FROM messages m
            JOIN workspace_members wm ON m.sender_id = wm.id
            JOIN users u ON wm.user_id = u.id
            LEFT JOIN message_pins mp ON mp.message_id = m.id AND mp.conversation_id = m.conversation_id AND mp.pinned_by = :member_id
            WHERE m.conversation_id = :conversation_id
              AND m.id < :before_id
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id
                    AND mud.workspace_member_id = :member_id
              )
            ORDER BY m.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':before_id', $beforeMessageId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $messages = self::formatChannelMessageRows($rows, $memberId, $conversationId);
        $oldestId = !empty($messages) ? (int)end($messages)['id'] : $beforeMessageId;

        return [
            'messages' => $messages,
            'has_more' => \App\Models\DmsConversation::hasOlderMessages($conversationId, $memberId, $oldestId),
            'oldest_message_id' => $oldestId,
        ];
    }

    /** @return array{messages: array<int, array<string, mixed>>, has_more: bool, newest_message_id: int} */
    public static function historyPageAfter(int $conversationId, int $memberId, int $afterMessageId, int $limit = 30): array
    {
        $limit = max(1, min(50, $limit));
        if ($conversationId <= 0 || $memberId <= 0 || $afterMessageId <= 0) {
            return ['messages' => [], 'has_more' => false, 'newest_message_id' => 0];
        }

        $db = self::db();
        $stmt = $db->prepare("
            SELECT 
                m.*,
                u.first_name,
                u.last_name,
                u.avatar_path,
                (m.sender_id = :member_id) as is_me,
                (mp.id IS NOT NULL) as is_pinned
            FROM messages m
            JOIN workspace_members wm ON m.sender_id = wm.id
            JOIN users u ON wm.user_id = u.id
            LEFT JOIN message_pins mp ON mp.message_id = m.id AND mp.conversation_id = m.conversation_id AND mp.pinned_by = :member_id
            WHERE m.conversation_id = :conversation_id
              AND m.id > :after_id
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id
                    AND mud.workspace_member_id = :member_id
              )
            ORDER BY m.id ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':after_id', $afterMessageId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $messages = self::formatChannelMessageRows($rows, $memberId, $conversationId);
        $newestId = !empty($messages) ? (int)end($messages)['id'] : $afterMessageId;

        return [
            'messages' => $messages,
            'has_more' => \App\Models\DmsConversation::hasNewerMessages($conversationId, $memberId, $newestId),
            'newest_message_id' => $newestId,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function conversationMedia(int $conversationId, int $memberId): array
    {
        return \App\Models\DmsConversation::conversationMedia($conversationId, $memberId);
    }

    /** @return array<int, array<string, mixed>> */
    public static function conversationFiles(int $conversationId, int $memberId): array
    {
        return \App\Models\DmsConversation::conversationFiles($conversationId, $memberId);
    }

    private static function formatMessageTime(string $timestamp): string
    {
        return \App\Helpers\TimeFormatter::formatMessageTime($timestamp);
    }
}
