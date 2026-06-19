<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class WorkspaceMember extends Model
{
    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM workspace_members WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByWorkspaceAndUser(int $workspaceId, int $userId): ?array
    {
        $stmt = self::db()->prepare('
            SELECT * FROM workspace_members 
            WHERE workspace_id = ? AND user_id = ?
        ');
        $stmt->execute([$workspaceId, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function findActiveForUser(int $userId): array
    {
        $stmt = self::db()->prepare('
            SELECT wm.*, w.name as workspace_name, w.slug as workspace_slug, w.logo_path as workspace_logo
            FROM workspace_members wm
            JOIN workspaces w ON wm.workspace_id = w.id
            WHERE wm.user_id = ? AND wm.status = "active" AND w.status = "active" AND w.deleted_at IS NULL
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $db = self::db();
        $stmt = $db->prepare('
            INSERT INTO workspace_members (workspace_id, user_id, role, job_title, status)
            VALUES (:workspace_id, :user_id, :role, :job_title, :status)
        ');
        $stmt->execute([
            'workspace_id' => $data['workspace_id'],
            'user_id' => $data['user_id'],
            'role' => $data['role'] ?? 'member',
            'job_title' => $data['job_title'] ?? null,
            'status' => $data['status'] ?? 'active'
        ]);
        $memberId = (int) $db->lastInsertId();

        // Automatically add member to default workspace channels: query c.is_default = 1 from database
        $chStmt = $db->prepare("
            SELECT c.id, conv.id as conversation_id 
            FROM channels c
            JOIN conversations conv ON conv.channel_id = c.id
            WHERE c.workspace_id = ? AND c.is_default = 1 AND c.status = 'active'
        ");
        $chStmt->execute([$data['workspace_id']]);
        $defaultChannels = $chStmt->fetchAll();

        foreach ($defaultChannels as $ch) {
            // Check membership
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM channel_members WHERE channel_id = ? AND workspace_member_id = ?");
            $checkStmt->execute([$ch['id'], $memberId]);
            if ((int) $checkStmt->fetchColumn() === 0) {
                $insMember = $db->prepare("
                    INSERT INTO channel_members (channel_id, workspace_member_id, role, notifications_muted)
                    VALUES (?, ?, 'member', 0)
                ");
                $insMember->execute([$ch['id'], $memberId]);

                // Update member count
                $updCount = $db->prepare("UPDATE channels SET member_count = member_count + 1 WHERE id = ?");
                $updCount->execute([$ch['id']]);
            }

            // Check participant
            $checkConv = $db->prepare("SELECT COUNT(*) FROM conversation_participants WHERE conversation_id = ? AND workspace_member_id = ?");
            $checkConv->execute([$ch['conversation_id'], $memberId]);
            if ((int) $checkConv->fetchColumn() === 0) {
                $insConv = $db->prepare("
                    INSERT INTO conversation_participants (conversation_id, workspace_member_id)
                    VALUES (?, ?)
                ");
                $insConv->execute([$ch['conversation_id'], $memberId]);
            }
        }

        return $memberId;
    }
}
