<?php

namespace App\Helpers;

use App\Core\Model;
use App\Models\DmsConversation;
use PDO;

class MessageEnricher
{
    public static function getMessageReactions(int $messageId, int $currentMemberId): array
    {
        $stmt = Model::db()->prepare(<<<'SQL'
SELECT
    emoji,
    COUNT(*) as count,
    MAX(CASE WHEN workspace_member_id = ? THEN 1 ELSE 0 END) as reacted
FROM message_reactions
WHERE message_id = ?
GROUP BY emoji
SQL
        );
        $stmt->execute([$currentMemberId, $messageId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param int[] $messageIds @return array<int, list<array<string, mixed>>> */
    public static function batchReactions(PDO $db, array $messageIds, int $memberId): array
    {
        $messageIds = array_values(array_filter(array_map('intval', $messageIds)));
        if ($messageIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $stmt = $db->prepare("
            SELECT
                message_id,
                emoji,
                COUNT(*) as count,
                MAX(CASE WHEN workspace_member_id = ? THEN 1 ELSE 0 END) as reacted
            FROM message_reactions
            WHERE message_id IN ($placeholders)
            GROUP BY message_id, emoji
        ");
        $stmt->execute(array_merge([$memberId], $messageIds));

        $grouped = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $messageId = (int)$row['message_id'];
            unset($row['message_id']);
            $grouped[$messageId][] = $row;
        }

        return $grouped;
    }

    /** @param int[] $messageIds @return array<int, list<array<string, mixed>>> */
    public static function batchAttachments(PDO $db, array $messageIds): array
    {
        $messageIds = array_values(array_filter(array_map('intval', $messageIds)));
        if ($messageIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $stmt = $db->prepare("
            SELECT ma.message_id, f.id, f.original_name, f.mime_type, f.extension, f.size_bytes, f.category
            FROM message_attachments ma
            JOIN files f ON f.id = ma.file_id
            WHERE ma.message_id IN ($placeholders)
        ");
        $stmt->execute($messageIds);

        $grouped = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $messageId = (int)$row['message_id'];
            unset($row['message_id']);
            $row['url'] = BASE_URL . '/files/download/' . $row['id'];
            $grouped[$messageId][] = $row;
        }

        return $grouped;
    }

    /** @param int[] $replyIds @return array<int, string> */
    public static function batchReplySnippets(PDO $db, array $replyIds): array
    {
        $replyIds = array_values(array_unique(array_filter(array_map('intval', $replyIds))));
        if ($replyIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($replyIds), '?'));
        $stmt = $db->prepare("
            SELECT id, body, message_type, deleted_for_everyone_at
            FROM messages
            WHERE id IN ($placeholders)
        ");
        $stmt->execute($replyIds);

        $snippets = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $snippets[(int)$row['id']] = self::buildReplySnippet($row);
        }

        return $snippets;
    }

    /** @param array<string, mixed> $row */
    public static function buildReplySnippet(array $row): string
    {
        if ($row['deleted_for_everyone_at'] !== null) {
            return 'This message was deleted';
        }

        $messageType = GiphyUrl::resolveMessageType(
            (string)($row['message_type'] ?? 'text'),
            (string)($row['body'] ?? '')
        );

        if ($messageType === 'file') {
            return 'File';
        }
        if ($messageType === 'gif') {
            return 'Photo';
        }
        if ($messageType === 'voice') {
            return 'Voice message';
        }

        $text = DmsConversation::bodyToPlainText($row['body'] ?? '', false);
        // LOW-10: Use mb_strlen/mb_substr for correct multibyte character counting
        if (mb_strlen($text) > 80) {
            $text = mb_substr($text, 0, 80) . '…';
        }

        return $text ?: 'Message';
    }

    public static function getReplySnippet(int $replyToId): string
    {
        $stmt = Model::db()->prepare("
            SELECT id, body, message_type, deleted_for_everyone_at
            FROM messages WHERE id = ?
        ");
        $stmt->execute([$replyToId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return '';
        }
        return self::buildReplySnippet($row);
    }
}
