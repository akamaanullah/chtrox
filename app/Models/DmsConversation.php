<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;
use PDO;

class DmsConversation extends Model
{
    public const INITIAL_VISIBLE = 20;

    public static function users(): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            return [];
        }

        $stmt = self::db()->prepare("
            SELECT wm.id as member_id, u.username, u.first_name, u.last_name, u.avatar_path
            FROM workspace_members wm
            JOIN users u ON wm.user_id = u.id
            WHERE wm.workspace_id = ? AND wm.status = 'active' AND wm.id != ?
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute([$workspaceId, $memberId]);
        $rows = $stmt->fetchAll();

        $users = [];
        foreach ($rows as $row) {
            $users[$row['username']] = [
                'id' => $row['member_id'],
                'username' => $row['username'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'avatar' => $row['avatar_path'] ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150'
            ];
        }

        return $users;
    }

    public static function resolveUser(string $withId): array
    {
        $users = self::users();
        $id = isset($users[$withId]) ? $withId : (empty($users) ? '' : array_key_first($users));

        return [
            'with_id' => $id,
            'with_user' => $id !== '' ? $users[$id] : [
                'id' => 0,
                'username' => '',
                'name' => 'No User Available',
                'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150'
            ],
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

        // Fetch DM conversations for this member
        $stmt = $db->prepare("
            SELECT 
                c.id as conversation_id,
                c.last_message_at,
                other_u.username as other_username,
                other_u.first_name,
                other_u.last_name,
                other_u.avatar_path,
                (
                    SELECT body FROM messages m2 
                    WHERE m2.conversation_id = c.id 
                    ORDER BY m2.id DESC LIMIT 1
                ) as last_message_body,
                (
                    SELECT created_at FROM messages m2 
                    WHERE m2.conversation_id = c.id 
                    ORDER BY m2.id DESC LIMIT 1
                ) as last_message_time,
                (
                    SELECT COUNT(*) 
                    FROM messages m3
                    LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = c.id AND crc.workspace_member_id = :member_id
                    WHERE m3.conversation_id = c.id
                      AND m3.sender_id != :member_id
                      AND m3.deleted_for_everyone_at IS NULL
                      AND (crc.last_read_message_id IS NULL OR m3.id > crc.last_read_message_id)
                ) as unread_count
            FROM conversations c
            JOIN conversation_participants cp_me ON c.id = cp_me.conversation_id AND cp_me.workspace_member_id = :member_id AND cp_me.left_at IS NULL
            JOIN conversation_participants cp_other ON c.id = cp_other.conversation_id AND cp_other.workspace_member_id != :member_id AND cp_other.left_at IS NULL
            JOIN workspace_members other_wm ON cp_other.workspace_member_id = other_wm.id
            JOIN users other_u ON other_wm.user_id = other_u.id
            WHERE c.workspace_id = :workspace_id AND c.type = 'dm'
            ORDER BY COALESCE(c.last_message_at, c.created_at) DESC
        ");
        $stmt->execute([
            'member_id' => $memberId,
            'workspace_id' => $workspaceId
        ]);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $formattedTime = '';
            if ($row['last_message_time']) {
                $formattedTime = self::formatMessageTime($row['last_message_time']);
            }

            $items[] = [
                'id' => $row['other_username'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'avatar' => $row['avatar_path'] ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150',
                'preview' => self::bodyToSidebarPreview($row['last_message_body'] ?? '') ?: 'No messages yet',
                'time' => $formattedTime,
                'unread' => (int) $row['unread_count'],
            ];
        }

        return $items;
    }

    public static function welcomeCards(): array
    {
        return array_values(self::users());
    }

    public static function commonMedia(): array
    {
        return MediaAssets::commonMedia();
    }

    public static function messages(string $otherUsername): array
    {
        $user = Session::user();
        $memberId = $user['workspace_member_id'] ?? 0;
        $workspaceId = $user['workspace_id'] ?? 0;
        $db = self::db();

        // Find the conversation ID between current user and other user
        $stmt = $db->prepare("
            SELECT c.id 
            FROM conversations c
            JOIN conversation_participants cp1 ON c.id = cp1.conversation_id AND cp1.workspace_member_id = :member_id
            JOIN conversation_participants cp2 ON c.id = cp2.conversation_id AND cp2.workspace_member_id = (
                SELECT wm.id FROM workspace_members wm 
                JOIN users u ON wm.user_id = u.id 
                WHERE wm.workspace_id = :workspace_id AND u.username = :other_username AND wm.status = 'active'
            )
            WHERE c.workspace_id = :workspace_id AND c.type = 'dm'
            LIMIT 1
        ");
        $stmt->execute([
            'member_id' => $memberId,
            'workspace_id' => $workspaceId,
            'other_username' => $otherUsername
        ]);
        $conversationId = (int) $stmt->fetchColumn();

        if ($conversationId === 0) {
            return []; // No conversation history yet
        }

        // Fetch messages for this conversation
        $stmt = $db->prepare("
            SELECT 
                m.*,
                (m.sender_id = :member_id) as is_me
            FROM messages m
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
                'text' => $row['body'],
                'time' => self::formatMessageTime($row['created_at']),
                'edited' => $row['edited_at'] !== null,
                'reactions' => self::getMessageReactions($row['id'], $memberId),
                'attachments' => self::getMessageAttachments($row['id']),
                'reply_to_id' => $row['reply_to_id'],
                'reply_snippet' => $row['reply_to_id'] ? self::getReplySnippet($row['reply_to_id']) : null,
                'message_type' => $row['message_type'],
                'is_forwarded' => !empty($row['forwarded_from_message_id']),
            ];
        }

        return self::applyReadReceipts($messages, $conversationId);
    }

    public static function getReplySnippet(int $replyToId): string
    {
        $stmt = self::db()->prepare("SELECT body, message_type FROM messages WHERE id = ?");
        $stmt->execute([$replyToId]);
        $row = $stmt->fetch();
        if (!$row) return 'Message';
        if ($row['message_type'] === 'file') return 'File';
        if ($row['message_type'] === 'gif') return 'Photo';
        $text = self::bodyToPlainText($row['body'] ?? '', false);
        if (strlen($text) > 80) {
            $text = substr($text, 0, 80) . '…';
        }
        return $text ?: 'Message';
    }

    /**
     * Convert stored message HTML to readable plain text.
     */
    public static function bodyToPlainText(string $body, bool $singleLine = false): string
    {
        if (trim($body) === '') {
            return '';
        }

        $normalized = preg_replace('/<br\s*\/?>/i', "\n", $body);
        $normalized = preg_replace('/<\/(p|div|li|tr)>/i', "\n", $normalized);
        $text = html_entity_decode(strip_tags($normalized), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = trim($text);

        if ($singleLine) {
            $text = preg_replace('/\s+/', ' ', str_replace("\n", ' ', $text));
            $text = trim($text);
        }

        return $text;
    }

    public static function bodyToSidebarPreview(?string $body, int $maxLen = 30): string
    {
        $text = self::bodyToPlainText($body ?? '', true);
        if ($text === '') {
            return '';
        }
        if ($maxLen > 0 && strlen($text) > $maxLen) {
            return substr($text, 0, $maxLen) . '...';
        }
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

    private static function applyReadReceipts(array $messages, int $conversationId): array
    {
        $user = Session::user();
        $memberId = $user['workspace_member_id'] ?? 0;
        $db = self::db();

        // Get the other participant's last read cursor and their presence
        $stmt = $db->prepare("
            SELECT crc.last_read_message_id, up.status as presence_status
            FROM conversation_participants cp
            JOIN workspace_members wm ON cp.workspace_member_id = wm.id
            JOIN user_presence up ON wm.user_id = up.user_id
            LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = cp.conversation_id AND crc.workspace_member_id = wm.id
            WHERE cp.conversation_id = ? AND cp.workspace_member_id != ? AND cp.left_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$conversationId, $memberId]);
        $otherPart = $stmt->fetch();

        $lastReadMsgId = $otherPart ? (int) $otherPart['last_read_message_id'] : 0;
        $otherOnline = $otherPart ? ($otherPart['presence_status'] === 'online') : false;

        foreach ($messages as $i => $message) {
            if (($message['side'] ?? '') !== 'me') {
                continue;
            }

            $messageId = $message['id'] ?? 0;
            if ($messageId <= $lastReadMsgId) {
                $status = 'read';
            } elseif ($otherOnline) {
                $status = 'delivered';
            } else {
                $status = 'sent';
            }

            $messages[$i]['read_status'] = $status;
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

    public static function getOrCreateConversationId(string $otherUsername): int
    {
        $user = Session::user();
        $memberId = $user['workspace_member_id'] ?? 0;
        $workspaceId = $user['workspace_id'] ?? 0;
        $db = self::db();

        // Find the other user member ID
        $stmt = $db->prepare("
            SELECT wm.id FROM workspace_members wm 
            JOIN users u ON wm.user_id = u.id 
            WHERE wm.workspace_id = :workspace_id AND u.username = :other_username AND wm.status = 'active'
        ");
        $stmt->execute([
            'workspace_id' => $workspaceId,
            'other_username' => $otherUsername
        ]);
        $otherMemberId = (int)$stmt->fetchColumn();

        if ($otherMemberId === 0) {
            return 0;
        }

        // Generate dynamic DM hash
        $memberIds = [$memberId, $otherMemberId];
        sort($memberIds);
        $dmHash = hash('sha256', implode(':', $memberIds));

        // Find conversation
        $stmt = $db->prepare("
            SELECT id FROM conversations 
            WHERE workspace_id = ? AND type = 'dm' AND dm_hash = ?
            LIMIT 1
        ");
        $stmt->execute([$workspaceId, $dmHash]);
        $conversationId = (int)$stmt->fetchColumn();

        if ($conversationId > 0) {
            return $conversationId;
        }

        // Create new conversation
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO conversations (workspace_id, type, dm_hash)
                VALUES (?, 'dm', ?)
            ");
            $stmt->execute([$workspaceId, $dmHash]);
            $conversationId = $db->lastInsertId();

            // Register participants
            $stmt = $db->prepare("
                INSERT INTO conversation_participants (conversation_id, workspace_member_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$conversationId, $memberId]);
            $stmt->execute([$conversationId, $otherMemberId]);

            $db->commit();
            return (int)$conversationId;
        } catch (\Exception $e) {
            $db->rollBack();
            return 0;
        }
    }
}
