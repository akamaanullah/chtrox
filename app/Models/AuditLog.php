<?php

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    public static function log(
        int $workspaceId,
        ?int $actorMemberId,
        ?string $actorLabel,
        string $activityType,
        string $message,
        string $status = 'complete',
        ?array $metadata = null
    ): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = self::db()->prepare('
            INSERT INTO audit_logs (workspace_id, actor_member_id, actor_label, status, activity_type, message, ip_address, metadata)
            VALUES (:workspace_id, :actor_member_id, :actor_label, :status, :activity_type, :message, :ip_address, :metadata)
        ');
        return $stmt->execute([
            'workspace_id' => $workspaceId,
            'actor_member_id' => $actorMemberId,
            'actor_label' => $actorLabel,
            'status' => $status,
            'activity_type' => $activityType,
            'message' => $message,
            'ip_address' => $ip,
            'metadata' => $metadata ? json_encode($metadata) : null
        ]);
    }
}
