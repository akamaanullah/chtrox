<?php

namespace App\Helpers;

use App\Core\Model;
use PDO;

class Cache
{
    /**
     * Get a cached value by key. Returns null if not found or expired.
     */
    public static function get(string $key): mixed
    {
        $db = Model::db();
        $stmt = $db->prepare("SELECT value, expires_at FROM system_cache WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if (strtotime($row['expires_at']) < time()) {
            self::delete($key);
            return null;
        }

        return json_decode($row['value'], true);
    }

    /**
     * Store a value in the cache with a given TTL in seconds.
     */
    public static function set(string $key, mixed $value, int $ttlSeconds): void
    {
        $db = Model::db();
        
        // Opportunistic cache pruning (1% chance)
        if (mt_rand(1, 100) === 1) {
            $db->exec("DELETE FROM system_cache WHERE expires_at < CURRENT_TIMESTAMP()");
        }

        $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);
        $encoded = json_encode($value);

        $stmt = $db->prepare("
            INSERT INTO system_cache (`key`, `value`, `expires_at`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value), expires_at = VALUES(expires_at)
        ");
        $stmt->execute([$key, $encoded, $expiresAt]);
    }

    /**
     * Delete a cached value by key.
     */
    public static function delete(string $key): void
    {
        $db = Model::db();
        $stmt = $db->prepare("DELETE FROM system_cache WHERE `key` = ?");
        $stmt->execute([$key]);
    }

    /**
     * Invalidate home dashboard cache for all active participants in a conversation.
     */
    public static function invalidateConversationDashboardCache(int $conversationId, int $workspaceId): void
    {
        $db = Model::db();
        
        $stmt = $db->prepare("SELECT type, channel_id FROM conversations WHERE id = ? AND workspace_id = ?");
        $stmt->execute([$conversationId, $workspaceId]);
        $conv = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$conv) {
            return;
        }

        $memberIds = [];
        if ($conv['type'] === 'channel') {
            $stmt = $db->prepare("SELECT workspace_member_id FROM channel_members WHERE channel_id = ? AND left_at IS NULL");
            $stmt->execute([(int)$conv['channel_id']]);
            $memberIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } else {
            $stmt = $db->prepare("SELECT workspace_member_id FROM conversation_participants WHERE conversation_id = ? AND left_at IS NULL");
            $stmt->execute([$conversationId]);
            $memberIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        if (empty($memberIds)) {
            return;
        }

        $keys = [];
        foreach ($memberIds as $mId) {
            $keys[] = "home_summary_{$mId}_{$workspaceId}";
            $keys[] = "nav_badges_{$mId}_{$workspaceId}";
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmtDel = $db->prepare("DELETE FROM system_cache WHERE `key` IN ($placeholders)");
        $stmtDel->execute($keys);
    }
}
