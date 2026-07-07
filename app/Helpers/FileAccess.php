<?php

namespace App\Helpers;

use PDO;

class FileAccess
{
    /**
     * Member may download when they uploaded it or it was shared in a conversation they belong to.
     */
    public static function canMemberAccessFile(PDO $db, array $file, int $memberId, int $workspaceId): bool
    {
        if ((int)($file['workspace_id'] ?? 0) !== $workspaceId) {
            return false;
        }

        if ((int)($file['uploaded_by'] ?? 0) === $memberId) {
            return true;
        }

        $stmt = $db->prepare("
            SELECT 1
            FROM message_attachments ma
            INNER JOIN messages m ON m.id = ma.message_id
            INNER JOIN conversations c ON c.id = m.conversation_id
            LEFT JOIN conversation_participants cp
                ON cp.conversation_id = c.id
               AND cp.workspace_member_id = ?
               AND cp.left_at IS NULL
            LEFT JOIN channel_members cm
                ON cm.channel_id = c.channel_id
               AND cm.workspace_member_id = ?
               AND cm.left_at IS NULL
            WHERE ma.file_id = ?
              AND m.workspace_id = ?
              AND m.deleted_for_everyone_at IS NULL
              AND (
                    (c.type IN ('dm', 'group_dm') AND cp.id IS NOT NULL)
                 OR (c.type = 'channel' AND cm.id IS NOT NULL)
              )
            LIMIT 1
        ");
        $stmt->execute([$memberId, $memberId, (int)$file['id'], $workspaceId]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * On send, only files uploaded by the current member in this workspace are allowed.
     *
     * @param array<int, int|string> $fileIds
     * @return string|null Error message or null when valid.
     */
    public static function validateFileIdsForSend(PDO $db, array $fileIds, int $workspaceId, int $memberId): ?string
    {
        $fileIds = array_values(array_unique(array_filter(array_map('intval', $fileIds))));
        if ($fileIds === []) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
        $stmt = $db->prepare("
            SELECT id, uploaded_by
            FROM files
            WHERE workspace_id = ?
              AND deleted_at IS NULL
              AND id IN ({$placeholders})
        ");
        $stmt->execute(array_merge([$workspaceId], $fileIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) !== count($fileIds)) {
            return 'One or more files are invalid or no longer available.';
        }

        foreach ($rows as $row) {
            if ((int)$row['uploaded_by'] !== $memberId) {
                return 'You can only send files that you uploaded.';
            }
        }

        return null;
    }

    /**
     * Safe Content-Disposition: inline only for previewable media; everything else downloads.
     * Block SVG files to avoid Stored XSS via inline scripts.
     */
    public static function isInlineDisposition(string $mimeType, string $extension): bool
    {
        $extension = strtolower(trim($extension));
        $mimeType = strtolower(trim($mimeType));

        // HIGH-05: Force attachment disposition for SVGs/XML to prevent Stored XSS
        if ($extension === 'svg' || str_contains($mimeType, 'svg') || str_contains($mimeType, 'xml')) {
            return false;
        }

        if (str_starts_with($mimeType, 'image/')
            || str_starts_with($mimeType, 'audio/')
            || str_starts_with($mimeType, 'video/')) {
            return true;
        }

        $previewExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp3', 'wav', 'ogg', 'webm', 'mp4', 'mov'];
        return in_array($extension, $previewExtensions, true);
    }
}
