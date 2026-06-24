<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;
use App\Helpers\GiphyUrl;
use PDO;

class WorkspaceSearch extends Model
{
    /** @return array{people: array, channels: array, messages: array, files: array} */
    public static function search(string $query, int $limit = 6, ?int $conversationId = null): array
    {
        $query = trim($query);
        $limit = max(1, min(10, $limit));

        if (mb_strlen($query) < 2) {
            return [
                'people' => [],
                'channels' => [],
                'messages' => [],
                'files' => [],
            ];
        }

        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($memberId === 0 || $workspaceId === 0) {
            return [
                'people' => [],
                'channels' => [],
                'messages' => [],
                'files' => [],
            ];
        }

        if ($conversationId !== null && $conversationId > 0) {
            return [
                'people' => [],
                'channels' => [],
                'messages' => self::searchMessages($workspaceId, $memberId, $query, $limit, $conversationId),
                'files' => self::searchFiles($workspaceId, $memberId, $query, $limit, $conversationId),
            ];
        }

        return [
            'people' => self::searchPeople($workspaceId, $memberId, $query, $limit),
            'channels' => self::searchChannels($workspaceId, $memberId, $query, $limit),
            'messages' => self::searchMessages($workspaceId, $memberId, $query, $limit),
            'files' => self::searchFiles($workspaceId, $memberId, $query, $limit),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function searchPeople(int $workspaceId, int $memberId, string $query, int $limit): array
    {
        $like = '%' . self::escapeLike($query) . '%';
        $stmt = self::db()->prepare("
            SELECT username, display_name, avatar_path, job_title, presence_status
            FROM v_people_directory
            WHERE workspace_id = ?
              AND workspace_member_id != ?
              AND (
                  display_name LIKE ? ESCAPE '\\\\'
                  OR username LIKE ? ESCAPE '\\\\'
                  OR email LIKE ? ESCAPE '\\\\'
                  OR job_title LIKE ? ESCAPE '\\\\'
              )
            ORDER BY display_name ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $workspaceId, PDO::PARAM_INT);
        $stmt->bindValue(2, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(3, $like);
        $stmt->bindValue(4, $like);
        $stmt->bindValue(5, $like);
        $stmt->bindValue(6, $like);
        $stmt->bindValue(7, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = [
                'type' => 'person',
                'id' => $row['username'],
                'title' => $row['display_name'],
                'subtitle' => '@' . $row['username'],
                'avatar' => $row['avatar_path'] ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150',
                'url' => 'dms/' . $row['username'],
                'online' => ($row['presence_status'] ?? '') === 'online',
            ];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private static function searchChannels(int $workspaceId, int $memberId, string $query, int $limit): array
    {
        $like = '%' . self::escapeLike($query) . '%';
        $stmt = self::db()->prepare("
            SELECT c.slug, c.name, c.description,
                   EXISTS (
                       SELECT 1 FROM channel_members cm
                       WHERE cm.channel_id = c.id
                         AND cm.workspace_member_id = ?
                         AND cm.left_at IS NULL
                   ) AS joined
            FROM channels c
            WHERE c.workspace_id = ?
              AND c.status = 'active'
              AND (c.name LIKE ? ESCAPE '\\\\' OR c.slug LIKE ? ESCAPE '\\\\' OR c.description LIKE ? ESCAPE '\\\\')
            ORDER BY joined DESC, c.name ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(2, $workspaceId, PDO::PARAM_INT);
        $stmt->bindValue(3, $like);
        $stmt->bindValue(4, $like);
        $stmt->bindValue(5, $like);
        $stmt->bindValue(6, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = [
                'type' => 'channel',
                'id' => $row['slug'],
                'title' => $row['name'],
                'subtitle' => !empty($row['joined']) ? 'Joined channel' : 'Public channel',
                'url' => !empty($row['joined']) ? 'channels/' . $row['slug'] : 'browse-channels',
            ];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private static function searchMessages(int $workspaceId, int $memberId, string $query, int $limit, ?int $conversationId = null): array
    {
        $like = '%' . self::escapeLike($query) . '%';
        $sql = "
            SELECT
                m.id,
                m.body,
                m.message_type,
                m.created_at,
                c.type AS conversation_type,
                c.id AS conversation_id,
                ch.slug AS channel_slug,
                ch.name AS channel_name,
                u.username AS sender_username,
                CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
                other_u.username AS dm_username,
                CONCAT(other_u.first_name, ' ', other_u.last_name) AS dm_name
            FROM messages m
            JOIN conversations c ON c.id = m.conversation_id
            JOIN workspace_members wm ON wm.id = m.sender_id
            JOIN users u ON u.id = wm.user_id
            LEFT JOIN channels ch ON ch.id = c.channel_id
            LEFT JOIN conversation_participants cp_self
                ON cp_self.conversation_id = c.id
               AND cp_self.workspace_member_id = :member_id
               AND cp_self.left_at IS NULL
            LEFT JOIN conversation_participants cp_other
                ON cp_other.conversation_id = c.id
               AND cp_other.workspace_member_id != :member_id
               AND cp_other.left_at IS NULL
               AND c.type IN ('dm', 'group_dm')
            LEFT JOIN workspace_members other_wm ON other_wm.id = cp_other.workspace_member_id
            LEFT JOIN users other_u ON other_u.id = other_wm.user_id
            LEFT JOIN channel_members cm
                ON cm.channel_id = c.channel_id
               AND cm.workspace_member_id = :member_id
               AND cm.left_at IS NULL
            WHERE m.workspace_id = :workspace_id
              AND m.deleted_for_everyone_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM message_user_deletions mud
                  WHERE mud.message_id = m.id
                    AND mud.workspace_member_id = :member_id
              )
              AND m.body LIKE :like ESCAPE '\\\\'
              AND (
                  (c.type IN ('dm', 'group_dm') AND cp_self.id IS NOT NULL)
                  OR (c.type = 'channel' AND cm.id IS NOT NULL)
              )
        ";
        if ($conversationId !== null) {
            $sql .= " AND m.conversation_id = :conversation_id ";
        }
        $sql .= " ORDER BY m.id DESC LIMIT :limit ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':workspace_id', $workspaceId, PDO::PARAM_INT);
        $stmt->bindValue(':like', $like);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        if ($conversationId !== null) {
            $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $msgType = GiphyUrl::resolveMessageType(
                (string)($row['message_type'] ?? 'text'),
                (string)($row['body'] ?? '')
            );
            $snippet = self::messageSnippet($row['body'] ?? '', $msgType);

            if ($row['conversation_type'] === 'channel') {
                $title = '#' . ($row['channel_name'] ?? 'Channel');
                $url = 'channels/' . ($row['channel_slug'] ?? '');
            } else {
                $title = $row['dm_name'] ?: ($row['sender_name'] ?? 'Direct message');
                $url = 'dms/' . ($row['dm_username'] ?: $row['sender_username']);
            }

            $items[] = [
                'type' => 'message',
                'id' => (int)$row['id'],
                'message_id' => (int)$row['id'],
                'conversation_id' => (int)$row['conversation_id'],
                'conversation_type' => (string)$row['conversation_type'],
                'title' => $title,
                'subtitle' => ($row['sender_name'] ?? 'Someone') . ': ' . $snippet,
                'url' => $url,
                'time' => date('M j, h:i A', strtotime($row['created_at'])),
            ];
        }

        return $items;
    }

    private static function searchFiles(int $workspaceId, int $memberId, string $query, int $limit, ?int $conversationId = null): array
    {
        $like = '%' . self::escapeLike($query) . '%';
        $sql = "
            SELECT DISTINCT f.id, f.original_name, f.mime_type, f.category, f.created_at
            FROM files f
            LEFT JOIN message_attachments ma ON ma.file_id = f.id
            LEFT JOIN messages m ON m.id = ma.message_id
            LEFT JOIN conversations c ON c.id = m.conversation_id
            LEFT JOIN conversation_participants cp_self 
                ON cp_self.conversation_id = c.id 
                AND cp_self.workspace_member_id = :member_id 
                AND cp_self.left_at IS NULL
            LEFT JOIN channel_members cm 
                ON cm.channel_id = c.channel_id 
                AND cm.workspace_member_id = :member_id 
                AND cm.left_at IS NULL
            WHERE f.workspace_id = :workspace_id
              AND f.deleted_at IS NULL
              AND (
                  f.original_name LIKE :like ESCAPE '\\\\'
                  OR f.extension LIKE :like ESCAPE '\\\\'
              )
              AND (
                  f.uploaded_by = :member_id
                  OR (c.type IN ('dm', 'group_dm') AND cp_self.id IS NOT NULL)
                  OR (c.type = 'channel' AND cm.id IS NOT NULL)
              )
              AND (m.id IS NULL OR (
                  m.deleted_for_everyone_at IS NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM message_user_deletions mud
                      WHERE mud.message_id = m.id
                        AND mud.workspace_member_id = :member_id
                  )
              ))
        ";
        if ($conversationId !== null) {
            $sql .= " AND m.conversation_id = :conversation_id ";
        }
        $sql .= " ORDER BY f.created_at DESC LIMIT :limit ";

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':workspace_id', $workspaceId, PDO::PARAM_INT);
        $stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':like', $like);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        if ($conversationId !== null) {
            $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = [
                'type' => 'file',
                'id' => (int)$row['id'],
                'title' => $row['original_name'],
                'subtitle' => strtoupper($row['category'] ?? 'file') . ' · ' . date('M j, Y', strtotime($row['created_at'])),
                'url' => 'files/download/' . $row['id'],
            ];
        }

        return $items;
    }

    private static function messageSnippet(string $body, string $messageType): string
    {
        if ($messageType === 'voice') {
            return 'Voice message';
        }
        if ($messageType === 'gif') {
            return 'Photo';
        }
        if ($messageType === 'file') {
            return 'Attachment';
        }

        $text = DmsConversation::bodyToPlainText($body, true);
        if (mb_strlen($text) > 80) {
            return mb_substr($text, 0, 79) . '…';
        }

        return $text ?: 'Message';
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
