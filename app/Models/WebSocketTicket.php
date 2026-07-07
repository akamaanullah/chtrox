<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class WebSocketTicket extends Model
{
    /**
     * Create a short-lived WebSocket authentication ticket.
     * Ticket expires in 60 seconds.
     */
    public static function createTicket(int $userId, int $workspaceMemberId, int $workspaceId, string $sessionToken): string
    {
        $db = self::db();

        // Perform a quick opportunistic cleanup of expired tickets (1% chance)
        if (mt_rand(1, 100) === 1) {
            self::cleanup();
        }

        $ticket = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 60); // 60 seconds lifespan

        $stmt = $db->prepare('
            INSERT INTO websocket_tickets (ticket, session_token, user_id, workspace_member_id, workspace_id, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$ticket, $sessionToken, $userId, $workspaceMemberId, $workspaceId, $expiresAt]);

        return $ticket;
    }

    /**
     * Consume a WebSocket ticket.
     * If valid, returns the associated user membership info and deletes the ticket (single use).
     * If invalid or expired, returns null.
     */
    public static function consumeTicket(string $ticket): ?array
    {
        $db = self::db();

        $stmt = $db->prepare('
            SELECT wt.*, u.username, u.first_name, u.last_name, u.avatar_path, wm.role
            FROM websocket_tickets wt
            JOIN users u ON u.id = wt.user_id
            JOIN workspace_members wm ON wm.id = wt.workspace_member_id AND wm.left_at IS NULL AND wm.status = "active"
            WHERE wt.ticket = ?
        ');
        $stmt->execute([$ticket]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$info) {
            return null;
        }

        // Delete the ticket immediately (single use)
        $stmtDelete = $db->prepare('DELETE FROM websocket_tickets WHERE ticket = ?');
        $stmtDelete->execute([$ticket]);

        // Check expiration in PHP using UNIX timestamp comparison to avoid database timezone/clock out-of-sync errors
        $expiresAt = strtotime($info['expires_at']);
        if ($expiresAt < time()) {
            return null;
        }

        return $info;
    }

    /**
     * Delete all expired tickets.
     */
    public static function cleanup(): void
    {
        $db = self::db();
        $db->exec('DELETE FROM websocket_tickets WHERE expires_at < CURRENT_TIMESTAMP()');
    }
}
