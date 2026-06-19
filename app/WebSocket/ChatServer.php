<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\UserPresence;
use App\Core\Model;
use PDO;

class ChatServer implements MessageComponentInterface
{
    /** @var \SplObjectStorage<ConnectionInterface, array> Maps connections to user session context */
    protected \SplObjectStorage $clients;

    /** @var array<int, array<string, ConnectionInterface>> Maps conversation_id -> [resourceId => connection] */
    protected array $subscriptions = [];

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn, []);

        try {
            // Authenticate client using token in query parameters
            $queryString = $conn->httpRequest->getUri()->getQuery();
            parse_str($queryString, $queryParams);
            $token = $queryParams['token'] ?? '';

            if (empty($token)) {
                $conn->send(json_encode(['error' => 'Missing authentication token']));
                $conn->close();
                return;
            }

            $session = $this->authenticateToken($token);

            if (!$session) {
                $conn->send(json_encode(['error' => 'Invalid or expired session token']));
                $conn->close();
                return;
            }

            // Store session context on connection
            $this->clients[$conn] = [
                'user_id' => (int)$session['user_id'],
                'username' => $session['username'],
                'display_name' => $session['first_name'] . ' ' . $session['last_name'],
                'workspace_member_id' => (int)$session['workspace_member_id'],
                'workspace_id' => (int)$session['workspace_id'],
                'role' => $session['role'],
                'avatar' => $session['avatar_path']
            ];

            // Mark user as online in DB
            $this->updateUserPresence((int)$session['user_id'], 'online');

            // Auto-subscribe to ALL conversations (DMs + channels) so messages arrive in real-time
            // even when the user is not actively viewing that specific conversation.
            $this->subscribeClientToAllConversations($conn, (int)$session['workspace_member_id']);

            // Mark offline-period messages as delivered and notify senders in real-time
            $this->deliverPendingMessages((int)$session['workspace_member_id']);

            // Broadcast presence change to workspace members
            $this->broadcastPresence((int)$session['workspace_id'], (int)$session['workspace_member_id'], 'online');

        } catch (\Exception $e) {
            echo "⚠️ onOpen error: " . $e->getMessage() . "\n";
            $conn->send(json_encode(['error' => 'Server error during connection setup']));
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $clientData = $this->clients[$from] ?? [];
        if (empty($clientData)) {
            return;
        }

        $payload = json_decode($msg, true);
        if (!$payload || !isset($payload['action'])) {
            return;
        }

        $action = $payload['action'];
        $workspaceId = $clientData['workspace_id'];
        $memberId = $clientData['workspace_member_id'];

        switch ($action) {
            case 'subscribe':
                $convId = (int)($payload['conversation_id'] ?? 0);
                if ($convId > 0 && $this->verifyConversationAccess($convId, $memberId)) {
                    $this->subscriptions[$convId][$from->resourceId] = $from;
                }
                break;

            case 'unsubscribe':
                $convId = (int)($payload['conversation_id'] ?? 0);
                if (isset($this->subscriptions[$convId][$from->resourceId])) {
                    unset($this->subscriptions[$convId][$from->resourceId]);
                }
                break;

            case 'broadcast':
                $convId = (int)($payload['conversation_id'] ?? 0);
                $event = $payload['event'] ?? '';
                $eventData = $payload['data'] ?? [];

                if ($convId > 0 && !empty($event) && $this->verifyConversationAccess($convId, $memberId)) {
                    // Ensure sender is subscribed (covers new conversations after initial connect)
                    $this->subscriptions[$convId][$from->resourceId] = $from;

                    // Send to everyone subscribed to this conversation EXCEPT the sender
                    $eventPayload = json_encode([
                        'event' => $event,
                        'conversation_id' => $convId,
                        'data' => $eventData
                    ]);

                    foreach ($this->subscriptions[$convId] as $resourceId => $conn) {
                        if ($resourceId !== $from->resourceId) {
                            $conn->send($eventPayload);
                        }
                    }
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $clientData = $this->clients[$conn] ?? [];
        
        // Remove subscriptions
        foreach ($this->subscriptions as $convId => $subList) {
            if (isset($subList[$conn->resourceId])) {
                unset($this->subscriptions[$convId][$conn->resourceId]);
            }
        }

        // Detach client
        $this->clients->detach($conn);

        if (!empty($clientData)) {
            $userId = $clientData['user_id'];
            $workspaceId = $clientData['workspace_id'];
            $memberId = $clientData['workspace_member_id'];

            // Check if user has any other active websocket connections open
            if (!$this->hasActiveConnections($userId)) {
                $this->updateUserPresence($userId, 'offline');
                $this->broadcastPresence($workspaceId, $memberId, 'offline');
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "❌ Connection Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
        $conn->close();
    }

    /**
     * Authenticate session token and retrieve user workspace membership details.
     */
    protected function authenticateToken(string $token): ?array
    {
        $db = \App\Core\Database::connection();
        $stmt = $db->prepare("
            SELECT us.*, u.id AS user_id, u.username, u.first_name, u.last_name, u.avatar_path,
                   wm.id AS workspace_member_id, wm.workspace_id, wm.role
            FROM user_sessions us
            JOIN users u ON u.id = us.user_id
            JOIN workspace_members wm ON wm.user_id = u.id AND wm.left_at IS NULL AND wm.status = 'active'
            WHERE us.session_token = ? AND us.revoked_at IS NULL
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Verify if workspace member has access to a specific conversation.
     */
    protected function verifyConversationAccess(int $conversationId, int $memberId): bool
    {
        $db = \App\Core\Database::connection();
        $stmt = $db->prepare("SELECT type, channel_id FROM conversations WHERE id = ?");
        $stmt->execute([$conversationId]);
        $conv = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conv) {
            return false;
        }

        if ($conv['type'] === 'channel') {
            $stmt = $db->prepare("
                SELECT id FROM channel_members 
                WHERE channel_id = ? AND workspace_member_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$conv['channel_id'], $memberId]);
            return (bool)$stmt->fetch();
        } else {
            $stmt = $db->prepare("
                SELECT id FROM conversation_participants 
                WHERE conversation_id = ? AND workspace_member_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$conversationId, $memberId]);
            return (bool)$stmt->fetch();
        }
    }

    /**
     * Updates user presence status in database.
     */
    protected function updateUserPresence(int $userId, string $status): void
    {
        $db = \App\Core\Database::connection();
        $stmt = $db->prepare("
            INSERT INTO user_presence (user_id, status, last_seen_at)
            VALUES (?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE status = VALUES(status), last_seen_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$userId, $status]);

        // Also update last_active_at in workspace_members
        $stmt = $db->prepare("
            UPDATE workspace_members 
            SET last_active_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND left_at IS NULL AND status = 'active'
        ");
        $stmt->execute([$userId]);
    }

    /**
     * Checks if user has other active open connections (multi-tab support).
     */
    protected function hasActiveConnections(int $userId): bool
    {
        foreach ($this->clients as $clientConn) {
            $data = $this->clients[$clientConn];
            if (isset($data['user_id']) && $data['user_id'] === $userId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Broadcast presence status changes to other online members of the same workspace.
     */
    protected function broadcastPresence(int $workspaceId, int $memberId, string $status): void
    {
        $payload = json_encode([
            'event' => 'presence_change',
            'data' => [
                'workspace_member_id' => $memberId,
                'status' => $status
            ]
        ]);

        foreach ($this->clients as $clientConn) {
            $data = $this->clients[$clientConn];
            if (isset($data['workspace_id']) && $data['workspace_id'] === $workspaceId) {
                $clientConn->send($payload);
            }
        }
    }

    /**
     * Automatically subscribe a client connection to all their active conversations (DMs & channels).
     */
    protected function subscribeClientToAllConversations(ConnectionInterface $conn, int $memberId): void
    {
        $db = \App\Core\Database::connection();

        // 1. Get all DM conversations where the member is a participant
        $stmtDms = $db->prepare("
            SELECT conversation_id 
            FROM conversation_participants 
            WHERE workspace_member_id = ? AND left_at IS NULL
        ");
        $stmtDms->execute([$memberId]);
        $dmConvIds = $stmtDms->fetchAll(PDO::FETCH_COLUMN) ?: [];

        // 2. Get all channel conversations where the member is a channel member
        $stmtChannels = $db->prepare("
            SELECT conv.id 
            FROM conversations conv
            JOIN channel_members cm ON conv.channel_id = cm.channel_id
            WHERE cm.workspace_member_id = ? AND cm.left_at IS NULL
        ");
        $stmtChannels->execute([$memberId]);
        $channelConvIds = $stmtChannels->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $allConvIds = array_unique(array_merge($dmConvIds, $channelConvIds));

        foreach ($allConvIds as $convId) {
            $this->subscriptions[(int)$convId][$conn->resourceId] = $conn;
        }
    }

    /**
     * Get all workspace member IDs who are participants/members of a conversation.
     * @return array<int>
     */
    protected function getConversationMemberIds(int $conversationId): array
    {
        $db = \App\Core\Database::connection();
        $stmt = $db->prepare("SELECT type, channel_id FROM conversations WHERE id = ?");
        $stmt->execute([$conversationId]);
        $conv = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conv) {
            return [];
        }

        if ($conv['type'] === 'channel') {
            $stmt = $db->prepare("
                SELECT workspace_member_id 
                FROM channel_members 
                WHERE channel_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$conv['channel_id']]);
        } else {
            $stmt = $db->prepare("
                SELECT workspace_member_id 
                FROM conversation_participants 
                WHERE conversation_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$conversationId]);
        }

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * When a user comes online, upgrade pending 'sent' DM messages to 'delivered'
     * and notify the original senders in real-time.
     */
    protected function deliverPendingMessages(int $recipientMemberId): void
    {
        $db = \App\Core\Database::connection();

        $stmt = $db->prepare("
            SELECT mds.message_id, m.conversation_id, m.sender_id
            FROM message_delivery_states mds
            JOIN messages m ON m.id = mds.message_id
            JOIN conversations c ON c.id = m.conversation_id
            WHERE mds.recipient_member_id = ?
              AND mds.state = 'sent'
              AND m.deleted_for_everyone_at IS NULL
              AND c.type = 'dm'
        ");
        $stmt->execute([$recipientMemberId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return;
        }

        $stmtUpdate = $db->prepare("
            UPDATE message_delivery_states mds
            JOIN messages m ON m.id = mds.message_id
            SET mds.state = 'delivered', mds.updated_at = CURRENT_TIMESTAMP(3)
            WHERE mds.recipient_member_id = ?
              AND mds.state = 'sent'
        ");
        $stmtUpdate->execute([$recipientMemberId]);

        $byConversation = [];
        foreach ($rows as $row) {
            $convId = (int)$row['conversation_id'];
            if (!isset($byConversation[$convId])) {
                $byConversation[$convId] = [
                    'sender_id' => (int)$row['sender_id'],
                    'message_ids' => [],
                ];
            }
            $byConversation[$convId]['message_ids'][] = (int)$row['message_id'];
        }

        foreach ($byConversation as $convId => $info) {
            $this->notifyMessageDelivery($convId, $info['sender_id'], $recipientMemberId, $info['message_ids']);
        }
    }

    /**
     * Push delivery receipts to the sender's active WebSocket connections.
     */
    protected function notifyMessageDelivery(int $conversationId, int $senderMemberId, int $recipientMemberId, array $messageIds): void
    {
        if (empty($messageIds) || !isset($this->subscriptions[$conversationId])) {
            return;
        }

        $payload = json_encode([
            'event' => 'messages_delivered',
            'conversation_id' => $conversationId,
            'data' => [
                'conversation_id' => $conversationId,
                'sender_id' => $senderMemberId,
                'recipient_member_id' => $recipientMemberId,
                'message_ids' => $messageIds,
            ]
        ]);

        foreach ($this->subscriptions[$conversationId] as $conn) {
            $data = $this->clients[$conn] ?? [];
            if (isset($data['workspace_member_id']) && (int)$data['workspace_member_id'] === $senderMemberId) {
                $conn->send($payload);
            }
        }
    }
}
