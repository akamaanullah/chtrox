<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;
use App\Helpers\GiphyUrl;
use App\Helpers\MessageEnricher;
use PDO;

class DmsConversation extends Model
{
    public const INITIAL_VISIBLE = 20;
    public const INITIAL_LOAD = 30;

    public static function users(): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            return [];
        }

        $stmt = self::db()->prepare("
            SELECT 
                wm.id as member_id, 
                u.username, 
                u.first_name, 
                u.last_name, 
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
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute([$workspaceId]);
        $rows = $stmt->fetchAll();

        $users = [];
        foreach ($rows as $row) {
            $isMe = ((int)$row['member_id'] === (int)$memberId);
            $name = $isMe ? 'Me' : ($row['first_name'] . ' ' . $row['last_name']);

            $users[$row['username']] = [
                'id' => $row['member_id'],
                'username' => $row['username'],
                'name' => $name,
                'avatar' => \App\Core\View::avatar($row['avatar_path']),
                'presence_status' => $row['presence_status']
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
                'avatar' => DEFAULT_AVATAR_URL
            ],
        ];
    }

    public static function sidebarDisplayItems(?string $activeWith = null): array
    {
        $user = Session::user();
        $memberId = $user['workspace_member_id'] ?? 0;
        $workspaceId = $user['workspace_id'] ?? 0;
        $db = self::db();

        if ($memberId === 0 || $workspaceId === 0) {
            return [];
        }

        $activeWith = $activeWith ?? '';

        $stmt = $db->prepare("
            SELECT 
                c.id as conversation_id,
                c.last_message_at,
                other_wm.id as other_member_id,
                other_u.username as other_username,
                other_u.first_name,
                other_u.last_name,
                other_u.avatar_path,
                m.id as last_message_id,
                m.sender_id as last_message_sender_id,
                m.body as last_message_body,
                m.message_type as last_message_type,
                m.created_at as last_message_time,
                m.deleted_for_everyone_at as last_message_deleted_at,
                crc_other.last_read_message_id as other_last_read_message_id,
                (
                    SELECT status 
                    FROM user_presence 
                    WHERE user_id = other_wm.user_id 
                    ORDER BY last_seen_at DESC, updated_at DESC 
                    LIMIT 1
                ) as other_presence_status,
                (
                    SELECT COUNT(*) 
                    FROM messages m3
                    WHERE m3.conversation_id = c.id
                      AND m3.sender_id != :member_id2
                      AND m3.deleted_for_everyone_at IS NULL
                      AND (crc.last_read_message_id IS NULL OR m3.id > crc.last_read_message_id)
                ) as unread_count
            FROM conversations c
            JOIN conversation_participants cp_me ON c.id = cp_me.conversation_id AND cp_me.workspace_member_id = :member_id3 AND cp_me.left_at IS NULL
            LEFT JOIN conversation_participants cp_other ON c.id = cp_other.conversation_id AND cp_other.workspace_member_id != :member_id4 AND cp_other.left_at IS NULL
            LEFT JOIN workspace_members other_wm ON COALESCE(cp_other.workspace_member_id, cp_me.workspace_member_id) = other_wm.id
            LEFT JOIN users other_u ON other_wm.user_id = other_u.id
            LEFT JOIN messages m ON m.id = c.last_message_id
            LEFT JOIN conversation_read_cursors crc_other ON crc_other.conversation_id = c.id AND crc_other.workspace_member_id = COALESCE(cp_other.workspace_member_id, cp_me.workspace_member_id)
            LEFT JOIN conversation_read_cursors crc ON crc.conversation_id = c.id AND crc.workspace_member_id = :member_id1
            WHERE c.workspace_id = :workspace_id AND c.type = 'dm'
              AND (c.last_message_id IS NOT NULL OR other_u.username = :active_with)
            ORDER BY COALESCE(c.last_message_at, c.created_at) DESC
            LIMIT 100
        ");
        $stmt->execute([
            'member_id1' => $memberId,
            'member_id2' => $memberId,
            'member_id3' => $memberId,
            'member_id4' => $memberId,
            'workspace_id' => $workspaceId,
            'active_with' => $activeWith
        ]);
        $rows = $stmt->fetchAll();

        $items = [];
        $seenUsernames = [];
        foreach ($rows as $row) {
            $otherUsername = $row['other_username'];
            if (isset($seenUsernames[$otherUsername])) {
                continue;
            }
            $seenUsernames[$otherUsername] = true;

            $formattedTime = '';
            if ($row['last_message_time']) {
                $formattedTime = self::formatMessageTime($row['last_message_time']);
            }

            $preview = 'No messages yet';
            if (!empty($row['last_message_deleted_at'])) {
                $preview = 'This message was deleted';
            } else {
                $lastType = GiphyUrl::resolveMessageType(
                    (string)($row['last_message_type'] ?? 'text'),
                    (string)($row['last_message_body'] ?? '')
                );
                if ($lastType === 'voice') {
                    $preview = 'Voice message';
                } elseif ($lastType === 'gif') {
                    $preview = 'Photo';
                } elseif (($row['last_message_type'] ?? '') === 'file') {
                    $preview = 'Attachment';
                } else {
                    $preview = self::bodyToSidebarPreview($row['last_message_body'] ?? '') ?: 'No messages yet';
                }
            }

            $lastMessageId = (int)($row['last_message_id'] ?? 0);
            $lastIsMine = $lastMessageId > 0 && (int)($row['last_message_sender_id'] ?? 0) === $memberId;
            $readStatus = null;
            if ($lastIsMine) {
                $otherReadId = (int)($row['other_last_read_message_id'] ?? 0);
                if ($otherReadId >= $lastMessageId) {
                    $readStatus = 'read';
                } elseif (($row['other_presence_status'] ?? '') === 'online') {
                    $readStatus = 'delivered';
                } else {
                    $readStatus = 'sent';
                }
            }

            $isMe = ((int)$row['other_member_id'] === $memberId);
            $name = $isMe ? 'Me' : ($row['first_name'] . ' ' . $row['last_name']);

            $items[] = [
                'id' => $row['other_username'],
                'conversation_id' => (int)$row['conversation_id'],
                'name' => $name,
                'avatar' => \App\Core\View::avatar($row['avatar_path']),
                'preview' => $preview,
                'time' => $formattedTime,
                'unread' => (int) $row['unread_count'],
                'last_is_mine' => $lastIsMine,
                'read_status' => $readStatus,
                'presence_status' => $row['other_presence_status'] ?? 'offline',
                'member_id' => (int)$row['other_member_id'],
            ];
        }

        return $items;
    }

    public static function welcomeCards(): array
    {
        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $db = self::db();

        if ($memberId === 0 || $workspaceId === 0) {
            return [];
        }

        // Fetch recently active DM contacts
        $stmt = $db->prepare("
            SELECT 
                other_wm.id as member_id, 
                other_u.username, 
                other_u.first_name, 
                other_u.last_name, 
                other_u.avatar_path,
                COALESCE(up.status, 'offline') as presence_status
            FROM conversations c
            JOIN conversation_participants cp_me ON c.id = cp_me.conversation_id AND cp_me.workspace_member_id = :member_id AND cp_me.left_at IS NULL
            LEFT JOIN conversation_participants cp_other ON c.id = cp_other.conversation_id AND cp_other.workspace_member_id != :member_id AND cp_other.left_at IS NULL
            LEFT JOIN workspace_members other_wm ON COALESCE(cp_other.workspace_member_id, cp_me.workspace_member_id) = other_wm.id
            LEFT JOIN users other_u ON other_wm.user_id = other_u.id
            LEFT JOIN user_presence up ON other_u.id = up.user_id
            WHERE c.workspace_id = :workspace_id AND c.type = 'dm' AND c.last_message_id IS NOT NULL
            ORDER BY c.last_message_at DESC, c.id DESC
            LIMIT 4
        ");
        $stmt->execute([
            'member_id' => $memberId,
            'workspace_id' => $workspaceId
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $frequent = [];
        $seenMemberIds = [];
        foreach ($rows as $row) {
            $otherMemberId = (int)$row['member_id'];
            if (in_array($otherMemberId, $seenMemberIds, true)) {
                continue;
            }
            $seenMemberIds[] = $otherMemberId;
            $isMe = ($otherMemberId === $memberId);
            $name = $isMe ? 'Me' : ($row['first_name'] . ' ' . $row['last_name']);

            $frequent[] = [
                'id' => $otherMemberId,
                'username' => $row['username'],
                'name' => $name,
                'avatar' => \App\Core\View::avatar($row['avatar_path']),
                'presence_status' => $row['presence_status']
            ];
        }

        // If we have fewer than 4, pad with other active workspace members
        if (count($frequent) < 4) {
            $allUsers = self::users();
            foreach ($allUsers as $u) {
                if (count($frequent) >= 4) {
                    break;
                }
                if (in_array((int)$u['id'], $seenMemberIds, true)) {
                    continue;
                }
                $frequent[] = $u;
                $seenMemberIds[] = (int)$u['id'];
            }
        }

        return $frequent;
    }

    /** @return array<string, mixed> */
    public static function contactProfile(string $otherUsername): array
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($workspaceId === 0 || $otherUsername === '') {
            return self::emptyContactProfile();
        }

        $stmt = self::db()->prepare("
            SELECT
                u.username,
                u.first_name,
                u.last_name,
                u.bio,
                u.avatar_path,
                wm.job_title,
                up.status AS presence_status,
                up.last_seen_at
            FROM users u
            JOIN workspace_members wm ON wm.user_id = u.id AND wm.workspace_id = ?
            LEFT JOIN user_presence up ON up.user_id = u.id
            WHERE u.username = ? AND wm.status = 'active' AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$workspaceId, $otherUsername]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return self::emptyContactProfile();
        }

        $name = trim($row['first_name'] . ' ' . $row['last_name']);
        $presence = self::formatPresence($row['presence_status'] ?? 'offline', $row['last_seen_at'] ?? null);
        $bio = trim($row['bio'] ?? '');
        if ($bio === '' && !empty($row['job_title'])) {
            $bio = $row['job_title'];
        }

        return [
            'username' => $row['username'],
            'name' => $name,
            'avatar' => \App\Core\View::avatar($row['avatar_path']),
            'handle' => '@' . $row['username'],
            'bio' => $bio !== '' ? $bio : 'No bio added yet.',
            'job_title' => trim($row['job_title'] ?? ''),
            'is_online' => $presence['online'],
            'presence_label' => $presence['label'],
            'presence_status' => $row['presence_status'] ?? 'offline',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function conversationMedia(int $conversationId, int $memberId): array
    {
        if ($conversationId <= 0 || $memberId <= 0) {
            return [];
        }

        $db = self::db();
        $media = [];

        $stmt = $db->prepare("
            SELECT f.id, f.original_name, m.id AS message_id, m.created_at
            FROM messages m
            JOIN message_attachments ma ON ma.message_id = m.id
            JOIN files f ON f.id = ma.file_id AND f.category = 'image'
            WHERE m.conversation_id = ?
              AND m.deleted_for_everyone_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id AND mud.workspace_member_id = ?
              )
            ORDER BY m.id DESC
        ");
        $stmt->execute([$conversationId, $memberId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $media[] = [
                'url' => BASE_URL . '/files/download/' . $row['id'],
                'message_id' => (int)$row['message_id'],
                'label' => $row['original_name'],
                'created_at' => $row['created_at'],
            ];
        }

        $stmt = $db->prepare("
            SELECT m.id AS message_id, m.body AS url, m.created_at
            FROM messages m
            WHERE m.conversation_id = ?
              AND m.message_type = 'gif'
              AND m.deleted_for_everyone_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id AND mud.workspace_member_id = ?
              )
            ORDER BY m.id DESC
        ");
        $stmt->execute([$conversationId, $memberId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $media[] = [
                'url' => $row['url'],
                'message_id' => (int)$row['message_id'],
                'label' => 'GIF',
                'created_at' => $row['created_at'],
            ];
        }

        usort($media, static function (array $a, array $b): int {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        return $media;
    }

    /** @return array<int, array<string, mixed>> */
    public static function conversationFiles(int $conversationId, int $memberId): array
    {
        if ($conversationId <= 0 || $memberId <= 0) {
            return [];
        }

        $stmt = self::db()->prepare("
            SELECT f.id, f.original_name, f.mime_type, f.extension, f.size_bytes, f.category, m.id AS message_id
            FROM messages m
            JOIN message_attachments ma ON ma.message_id = m.id
            JOIN files f ON f.id = ma.file_id AND f.category != 'image'
            WHERE m.conversation_id = ?
              AND m.deleted_for_everyone_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id AND mud.workspace_member_id = ?
              )
            ORDER BY m.id DESC
        ");
        $stmt->execute([$conversationId, $memberId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $files = [];
        foreach ($rows as $row) {
            $files[] = [
                'id' => (int)$row['id'],
                'message_id' => (int)$row['message_id'],
                'name' => $row['original_name'],
                'mime_type' => $row['mime_type'],
                'extension' => $row['extension'],
                'size_bytes' => (int)$row['size_bytes'],
                'size_label' => self::formatFileSize((int)$row['size_bytes']),
                'url' => BASE_URL . '/files/download/' . $row['id'],
            ];
        }

        return $files;
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

        $conversationId = self::resolveConversationId($otherUsername, $workspaceId, $memberId);
        if ($conversationId === 0) {
            return [];
        }

        return self::fetchMessagesForConversation($conversationId, $memberId, null, self::INITIAL_LOAD);
    }

    public static function resolveConversationId(string $otherUsername, int $workspaceId, int $memberId): int
    {
        if ($workspaceId === 0 || $memberId === 0 || $otherUsername === '') {
            return 0;
        }

        $stmt = self::db()->prepare("
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
            'other_username' => $otherUsername,
        ]);

        return (int)$stmt->fetchColumn();
    }

    public static function hasOlderMessages(int $conversationId, int $memberId, int $beforeMessageId): bool
    {
        if ($conversationId <= 0 || $memberId <= 0 || $beforeMessageId <= 0) {
            return false;
        }

        $stmt = self::db()->prepare("
            SELECT 1
            FROM messages m
            WHERE m.conversation_id = ?
              AND m.id < ?
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id AND mud.workspace_member_id = ?
              )
            LIMIT 1
        ");
        $stmt->execute([$conversationId, $beforeMessageId, $memberId]);

        return (bool)$stmt->fetchColumn();
    }

    public static function hasNewerMessages(int $conversationId, int $memberId, int $afterMessageId): bool
    {
        if ($conversationId <= 0 || $memberId <= 0 || $afterMessageId <= 0) {
            return false;
        }

        $stmt = self::db()->prepare("
            SELECT 1
            FROM messages m
            WHERE m.conversation_id = ?
              AND m.id > ?
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id AND mud.workspace_member_id = ?
              )
            LIMIT 1
        ");
        $stmt->execute([$conversationId, $afterMessageId, $memberId]);

        return (bool)$stmt->fetchColumn();
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

        $stmt = self::db()->prepare('SELECT id FROM messages WHERE id = ? AND conversation_id = ? LIMIT 1');
        $stmt->execute([$messageId, $conversationId]);
        if (!$stmt->fetchColumn()) {
            return $empty;
        }

        $beforeLimit = max(1, (int)ceil($limit / 2));
        $afterLimit = max(0, (int)floor($limit / 2));

        $beforeRows = self::fetchMessagesUpTo($conversationId, $memberId, $messageId, $beforeLimit);
        $afterRows = $afterLimit > 0
            ? self::fetchMessagesAfter($conversationId, $memberId, $messageId, $afterLimit)
            : [];

        $rows = array_merge(array_reverse($beforeRows), $afterRows);
        $messages = self::applyReadReceipts(self::enrichMessageRows($rows, $memberId), $conversationId);

        $oldestId = !empty($messages) ? (int)$messages[0]['id'] : $messageId;
        $newestId = !empty($messages) ? (int)$messages[count($messages) - 1]['id'] : $messageId;

        return [
            'messages' => $messages,
            'has_more_before' => self::hasOlderMessages($conversationId, $memberId, $oldestId),
            'has_more_after' => self::hasNewerMessages($conversationId, $memberId, $newestId),
            'oldest_message_id' => $oldestId,
            'newest_message_id' => $newestId,
            'target_message_id' => $messageId,
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function fetchMessagesUpTo(int $conversationId, int $memberId, int $upToMessageId, int $limit): array
    {
        $db = self::db();
        $stmt = $db->prepare("
            SELECT
                m.*,
                (m.sender_id = :member_id) as is_me,
                (mp.id IS NOT NULL) as is_pinned
            FROM messages m
            LEFT JOIN message_pins mp ON mp.message_id = m.id AND mp.conversation_id = m.conversation_id AND mp.pinned_by = :member_id
            WHERE m.conversation_id = :conversation_id
              AND m.id <= :up_to_id
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
        $stmt->bindValue(':up_to_id', $upToMessageId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, mixed>> */
    private static function fetchMessagesAfter(int $conversationId, int $memberId, int $afterMessageId, int $limit): array
    {
        $db = self::db();
        $stmt = $db->prepare("
            SELECT
                m.*,
                (m.sender_id = :member_id) as is_me,
                (mp.id IS NOT NULL) as is_pinned
            FROM messages m
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
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{messages: array<int, array<string, mixed>>, has_more: bool, oldest_message_id: int} */
    public static function historyPage(int $conversationId, int $memberId, int $beforeMessageId, int $limit = 30): array
    {
        $limit = max(1, min(50, $limit));
        if ($conversationId <= 0 || $memberId <= 0 || $beforeMessageId <= 0) {
            return ['messages' => [], 'has_more' => false, 'oldest_message_id' => 0];
        }

        $messages = self::fetchMessagesForConversation($conversationId, $memberId, $beforeMessageId, $limit);
        $oldestId = !empty($messages) ? (int)end($messages)['id'] : $beforeMessageId;

        return [
            'messages' => $messages,
            'has_more' => self::hasOlderMessages($conversationId, $memberId, $oldestId),
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

        $rows = self::fetchMessagesAfter($conversationId, $memberId, $afterMessageId, $limit);
        $messages = self::applyReadReceipts(self::enrichMessageRows($rows, $memberId), $conversationId);
        $newestId = !empty($messages) ? (int)end($messages)['id'] : $afterMessageId;

        return [
            'messages' => $messages,
            'has_more' => self::hasNewerMessages($conversationId, $memberId, $newestId),
            'newest_message_id' => $newestId,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function fetchMessagesForConversation(
        int $conversationId,
        int $memberId,
        ?int $beforeMessageId,
        int $limit
    ): array {
        $db = self::db();
        $sql = "
            SELECT 
                m.*,
                (m.sender_id = :member_id) as is_me,
                (mp.id IS NOT NULL) as is_pinned
            FROM messages m
            LEFT JOIN message_pins mp ON mp.message_id = m.id AND mp.conversation_id = m.conversation_id AND mp.pinned_by = :member_id
            WHERE m.conversation_id = :conversation_id
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id
                    AND mud.workspace_member_id = :member_id
              )
        ";

        if ($beforeMessageId !== null && $beforeMessageId > 0) {
            $sql .= ' AND m.id < :before_id';
        }

        $sql .= ' ORDER BY m.id DESC LIMIT :limit';

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        if ($beforeMessageId !== null && $beforeMessageId > 0) {
            $stmt->bindValue(':before_id', $beforeMessageId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return self::applyReadReceipts(self::enrichMessageRows($rows, $memberId), $conversationId);
    }

    /** @param list<array<string, mixed>> $rows @return list<array<string, mixed>> */
    private static function enrichMessageRows(array $rows, int $memberId): array
    {
        if ($rows === []) {
            return [];
        }

        $messageIds = [];
        $replyIds = [];
        foreach ($rows as $row) {
            $messageIds[] = (int)$row['id'];
            if (!empty($row['reply_to_id'])) {
                $replyIds[] = (int)$row['reply_to_id'];
            }
        }

        $db = self::db();
        $reactionsByMessage = MessageEnricher::batchReactions($db, $messageIds, $memberId);
        $attachmentsByMessage = MessageEnricher::batchAttachments($db, $messageIds);
        $replySnippets = MessageEnricher::batchReplySnippets($db, $replyIds);

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = self::formatMessageRow(
                $row,
                $memberId,
                $reactionsByMessage,
                $attachmentsByMessage,
                $replySnippets
            );
        }

        return $messages;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, list<array<string, mixed>>> $reactionsByMessage
     * @param array<int, list<array<string, mixed>>> $attachmentsByMessage
     * @param array<int, string> $replySnippets
     */
    private static function formatMessageRow(
        array $row,
        int $memberId,
        array $reactionsByMessage = [],
        array $attachmentsByMessage = [],
        array $replySnippets = []
    ): array {
        $deletedForEveryone = $row['deleted_for_everyone_at'] !== null;
        $isVoice = ($row['message_type'] ?? '') === 'voice';
        $messageId = (int)$row['id'];
        $messageType = GiphyUrl::resolveMessageType(
            (string)($row['message_type'] ?? 'text'),
            (string)($row['body'] ?? '')
        );

        $reactions = [];
        $attachments = [];
        $replySnippet = null;

        if (!$deletedForEveryone) {
            if ($reactionsByMessage !== [] || $attachmentsByMessage !== [] || $replySnippets !== []) {
                $reactions = $reactionsByMessage[$messageId] ?? [];
                $attachments = $attachmentsByMessage[$messageId] ?? [];
                if (!empty($row['reply_to_id'])) {
                    $replySnippet = $replySnippets[(int)$row['reply_to_id']] ?? 'Message';
                }
            } else {
                $reactions = self::getMessageReactions($messageId, $memberId);
                $attachments = self::getMessageAttachments($messageId);
                if (!empty($row['reply_to_id'])) {
                    $replySnippet = self::getReplySnippet((int)$row['reply_to_id']);
                }
            }
        }

        $cleanBody = '';
        if (!$deletedForEveryone) {
            if ($isVoice) {
                $cleanBody = '';
            } elseif ($messageType === 'gif') {
                $cleanBody = trim($row['body'] ?? '');
            } else {
                $cleanBody = \App\Helpers\HtmlSanitizer::clean($row['body'] ?? '');
            }
        }

        return [
            'id' => $messageId,
            'side' => !empty($row['is_me']) ? 'me' : 'them',
            'text' => $cleanBody,
            'body' => $cleanBody,
            'voice_duration_seconds' => (!$deletedForEveryone && $isVoice)
                ? max(0, (int)trim((string)($row['body'] ?? '')))
                : 0,
            'time' => self::formatMessageTime($row['created_at']),
            'time_label' => self::formatMessageTime($row['created_at']),
            'created_at' => $row['created_at'],
            'edited' => !$deletedForEveryone && $row['edited_at'] !== null,
            'reactions' => $reactions,
            'attachments' => $attachments,
            'reply_to_id' => $deletedForEveryone ? null : $row['reply_to_id'],
            'reply_snippet' => $replySnippet,
            'message_type' => $messageType,
            'is_forwarded' => !$deletedForEveryone && !empty($row['forwarded_from_message_id']),
            'deleted_for_everyone' => $deletedForEveryone,
            'is_pinned' => !$deletedForEveryone && !empty($row['is_pinned']),
        ];
    }

    public static function getReplySnippet(int $replyToId): string
    {
        return \App\Helpers\MessageEnricher::getReplySnippet($replyToId);
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
        // LOW-10: Use mb_strlen/mb_substr for correct multibyte (Unicode) character counting
        if ($maxLen > 0 && mb_strlen($text) > $maxLen) {
            return mb_substr($text, 0, $maxLen) . '...';
        }
        return $text;
    }

    /** @return array{label: string, online: bool} */
    private static function formatPresence(string $status, ?string $lastSeenAt): array
    {
        return \App\Helpers\TimeFormatter::formatPresence($status, $lastSeenAt);
    }

    private static function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / 1048576, 2) . ' MB';
    }

    /** @return array<string, mixed> */
    private static function emptyContactProfile(): array
    {
        return [
            'username' => '',
            'name' => 'User',
            'avatar' => DEFAULT_AVATAR_URL,
            'handle' => '@user',
            'bio' => 'No bio added yet.',
            'job_title' => '',
            'is_online' => false,
            'presence_label' => 'Offline',
        ];
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
        $isSelfChat = !$otherPart;

        foreach ($messages as $i => $message) {
            if (($message['side'] ?? '') !== 'me') {
                continue;
            }

            $messageId = $message['id'] ?? 0;
            if ($isSelfChat) {
                $status = 'read';
            } elseif ($messageId <= $lastReadMsgId) {
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
        return \App\Helpers\TimeFormatter::formatMessageTime($timestamp);
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
            if ($memberId !== $otherMemberId) {
                $stmt->execute([$conversationId, $otherMemberId]);
            }

            $db->commit();
            return (int)$conversationId;
        } catch (\Exception $e) {
            $db->rollBack();

            // In case of a concurrent insert race condition, re-query to see if the conversation was created
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

            return 0;
        }
    }
}
