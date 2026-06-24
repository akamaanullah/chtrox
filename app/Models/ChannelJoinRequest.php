<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;
use PDO;

class ChannelJoinRequest extends Model
{
    public static function getPendingByChannel(int $channelId, int $workspaceId): array
    {
        $db = self::db();
        $stmt = $db->prepare("
            SELECT 
                cjr.id,
                cjr.workspace_member_id,
                cjr.created_at,
                u.first_name,
                u.last_name,
                u.avatar_path,
                wm.status
            FROM channel_join_requests cjr
            JOIN workspace_members wm ON cjr.workspace_member_id = wm.id
            JOIN users u ON wm.user_id = u.id
            WHERE cjr.channel_id = ? 
              AND cjr.workspace_id = ?
              AND cjr.status = 'pending'
            ORDER BY cjr.created_at ASC
        ");
        $stmt->execute([$channelId, $workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getRequest(int $requestId, int $workspaceId): ?array
    {
        $db = self::db();
        $stmt = $db->prepare("
            SELECT *
            FROM channel_join_requests
            WHERE id = ? AND workspace_id = ?
        ");
        $stmt->execute([$requestId, $workspaceId]);
        return $stmt->fetch() ?: null;
    }

    public static function countPendingByChannel(int $channelId): int
    {
        $db = self::db();
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM channel_join_requests 
            WHERE channel_id = ? AND status = 'pending'
        ");
        $stmt->execute([$channelId]);
        return (int)$stmt->fetchColumn();
    }
}
