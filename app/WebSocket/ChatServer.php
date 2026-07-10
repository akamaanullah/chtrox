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

    /** @var array<int, array<string, ConnectionInterface>> Maps workspace_member_id -> [resourceId => connection] */
    protected array $memberConnections = [];

    /** @var array<int, array<string, ConnectionInterface>> Maps user_id -> [resourceId => connection] */
    protected array $userConnections = [];

    /** @var array<int, array<string, ConnectionInterface>> Maps workspace_id -> [resourceId => connection] */
    protected array $workspaceConnections = [];

    /** @var array<int, array<string, mixed>> Cache of conversation_id -> ['expires' => int, 'members' => array<int>] */
    protected array $conversationMembersCache = [];

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

            $workspaceId = (int)($queryParams['workspace_id'] ?? 0);
            $session = $this->authenticateToken($token, $workspaceId);

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
                'avatar' => $session['avatar_path'],
                'session_token' => $session['session_token'],
                'last_activity' => time()
            ];

            $userId = (int)$session['user_id'];
            $memberId = (int)$session['workspace_member_id'];
            $workspaceId = (int)$session['workspace_id'];

            $this->memberConnections[$memberId][$conn->resourceId] = $conn;
            $this->userConnections[$userId][$conn->resourceId] = $conn;
            $this->workspaceConnections[$workspaceId][$conn->resourceId] = $conn;

            // Get user's preferred status from user_presence table to preserve away/dnd choices
            $db = \App\Core\Database::connection();
            $stmtStatus = $db->prepare("SELECT preferred_status FROM user_presence WHERE user_id = ?");
            $stmtStatus->execute([$userId]);
            $preferredStatus = $stmtStatus->fetchColumn();
            
            $statusToSet = 'online';
            if ($preferredStatus === 'away' || $preferredStatus === 'dnd') {
                $statusToSet = $preferredStatus;
            }

            // Mark user status in DB
            $this->updateUserPresence($userId, $statusToSet);

            // Auto-subscribe to ALL conversations (DMs + channels) so messages arrive in real-time
            // even when the user is not actively viewing that specific conversation.
            $this->subscribeClientToAllConversations($conn, $memberId);

            // Mark offline-period messages as delivered and notify senders in real-time
            $this->deliverPendingMessages($memberId);

            // Broadcast presence change to workspace members
            $this->broadcastPresence($workspaceId, $memberId, $statusToSet);

        } catch (\Exception $e) {
            echo "⚠️ onOpen error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
            $conn->send(json_encode(['error' => 'Connection failed. Please try again.']));
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        try {
            $clientData = $this->clients[$from] ?? [];
            if (empty($clientData)) {
                return;
            }

            $payload = json_decode($msg, true);
            if (!$payload || !isset($payload['action'])) {
                return;
            }

            // Update last activity timestamp for keepalive
            if ($this->clients->contains($from)) {
                $clientData['last_activity'] = time();
                $this->clients[$from] = $clientData;
            }

            $action = $payload['action'];
            $workspaceId = $clientData['workspace_id'];
            $memberId = $clientData['workspace_member_id'];
            $userId = $clientData['user_id'];

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

                    $allowedBroadcastEvents = [
                        'new_message', 'message_edited', 'message_deleted',
                        'typing_start', 'typing_stop', 'message_delivered', 'messages_read',
                        'conversation_read', 'reaction_added', 'reaction_removed', 'message_reaction'
                    ];
                    if (!in_array($event, $allowedBroadcastEvents, true)) {
                        break;
                    }

                    $hasAccess = false;
                    if ($convId > 0 && !empty($event)) {
                        if ($event === 'new_message' && isset($eventData['sender_id']) && (int)$eventData['sender_id'] === $memberId) {
                            $hasAccess = true;
                        } else {
                            $hasAccess = $this->verifyConversationAccess($convId, $memberId);
                        }
                    }

                    if ($hasAccess) {
                        // Send to everyone who has access to this conversation EXCEPT the sender connection
                        $eventPayload = json_encode([
                            'event' => $event,
                            'conversation_id' => $convId,
                            'data' => $eventData
                        ]);

                        $memberIds = $this->getConversationMemberIds($convId);
                        foreach ($memberIds as $mId) {
                            $mId = (int)$mId;
                            if (isset($this->memberConnections[$mId])) {
                                foreach ($this->memberConnections[$mId] as $conn) {
                                    if ($conn->resourceId !== $from->resourceId) {
                                        $conn->send($eventPayload);
                                    }
                                }
                            }
                        }
                    }
                    break;

                case 'notify_members':
                    $targetMemberIds = array_map('intval', $payload['member_ids'] ?? []);
                    $event = $payload['event'] ?? '';
                    $eventData = $payload['data'] ?? [];

                    $allowedEvents = [
                        'typing', 'stop_typing', 'message_delivered', 'messages_read',
                        'reaction_added', 'reaction_removed', 'new_notification',
                        'home_dashboard_update', 'nav_badge_update',
                        'channel_join_request', 'channel_join_request_approved', 'channel_join_request_rejected'
                    ];
                    if (!in_array($event, $allowedEvents, true)) {
                        break;
                    }

                    if (!empty($targetMemberIds) && !empty($event)) {
                        $targetMemberIds = array_filter($targetMemberIds, function(int $tId) use ($workspaceId) {
                            if (!isset($this->memberConnections[$tId])) {
                                return false;
                            }
                            foreach ($this->memberConnections[$tId] as $conn) {
                                $data = $this->clients[$conn] ?? [];
                                if (($data['workspace_id'] ?? 0) === $workspaceId) {
                                    return true;
                                }
                            }
                            return false;
                        });
                    }

                    if (!empty($targetMemberIds) && !empty($event)) {
                        $eventPayload = json_encode([
                            'event' => $event,
                            'data' => $eventData
                        ]);

                        foreach ($targetMemberIds as $tId) {
                            if (isset($this->memberConnections[$tId])) {
                                foreach ($this->memberConnections[$tId] as $conn) {
                                    // Send to all connections of this member
                                    $conn->send($eventPayload);
                                }
                            }
                        }
                    }
                    break;

                case 'presence_change':
                    $status = trim((string)($payload['status'] ?? ''));
                    if (in_array($status, ['online', 'away', 'dnd', 'offline'], true)) {
                        $this->updateUserPresence($userId, $status);
                        $this->broadcastPresence($workspaceId, $memberId, $status);
                    }
                    break;

                case 'pong':
                    // Keepalive pong received, last_activity updated above
                    break;
            }
        } catch (\Exception $e) {
            echo "⚠️ onMessage error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        try {
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
                $memberId = (int)$clientData['workspace_member_id'];

                if (isset($this->memberConnections[$memberId][$conn->resourceId])) {
                    unset($this->memberConnections[$memberId][$conn->resourceId]);
                    if (empty($this->memberConnections[$memberId])) {
                        unset($this->memberConnections[$memberId]);
                    }
                }

                if (isset($this->userConnections[$userId][$conn->resourceId])) {
                    unset($this->userConnections[$userId][$conn->resourceId]);
                    if (empty($this->userConnections[$userId])) {
                        unset($this->userConnections[$userId]);
                    }
                }

                if (isset($this->workspaceConnections[$workspaceId][$conn->resourceId])) {
                    unset($this->workspaceConnections[$workspaceId][$conn->resourceId]);
                    if (empty($this->workspaceConnections[$workspaceId])) {
                        unset($this->workspaceConnections[$workspaceId]);
                    }
                }

                // Check if user has any other active websocket connections open
                if (!$this->hasActiveConnections($userId)) {
                    $this->updateUserPresence($userId, 'offline');
                    $this->broadcastPresence($workspaceId, $memberId, 'offline');
                }
            }
        } catch (\Exception $e) {
            echo "⚠️ onClose error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
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
    protected function authenticateToken(string $token, int $workspaceId = 0): ?array
    {
        $info = \App\Models\WebSocketTicket::consumeTicket($token);
        if (!$info) {
            return null;
        }
        if ($workspaceId > 0 && (int)$info['workspace_id'] !== $workspaceId) {
            return null;
        }
        return $info;
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
        if ($status === 'offline') {
            $stmt = $db->prepare("
                INSERT INTO user_presence (user_id, status, last_seen_at)
                VALUES (?, 'offline', CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE status = 'offline', last_seen_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$userId]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO user_presence (user_id, status, preferred_status, last_seen_at)
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE status = VALUES(status), preferred_status = VALUES(preferred_status), last_seen_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$userId, $status, $status]);
        }

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
        return !empty($this->userConnections[$userId]);
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

        if (isset($this->workspaceConnections[$workspaceId])) {
            foreach ($this->workspaceConnections[$workspaceId] as $clientConn) {
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
        $now = time();
        if (isset($this->conversationMembersCache[$conversationId])) {
            $cached = $this->conversationMembersCache[$conversationId];
            if ($now < $cached['expires']) {
                return $cached['members'];
            }
        }

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
            $ttl = 10; // Cache channel members for 10 seconds
        } else {
            $stmt = $db->prepare("
                SELECT workspace_member_id 
                FROM conversation_participants 
                WHERE conversation_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$conversationId]);
            $ttl = 86400; // Cache DM participants for 24 hours (effectively static)
        }

        $members = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $memberIds = array_map('intval', $members);

        $this->conversationMembersCache[$conversationId] = [
            'expires' => $now + $ttl,
            'members' => $memberIds
        ];

        return $memberIds;
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
        if (empty($messageIds)) {
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

        if (isset($this->memberConnections[$senderMemberId])) {
            foreach ($this->memberConnections[$senderMemberId] as $conn) {
                $conn->send($payload);
            }
        }
    }

    /**
     * Periodically revalidate connected clients against active sessions in database.
     */
    public function revalidateActiveSessions(): void
    {
        $db = \App\Core\Database::connection();
        $invalidConns = [];

        $tokensToConnections = [];
        foreach ($this->clients as $conn) {
            $data = $this->clients[$conn] ?? [];
            if (empty($data['session_token'])) {
                $invalidConns[] = $conn;
                continue;
            }
            $tokensToConnections[$data['session_token']][] = $conn;
        }

        if (!empty($tokensToConnections)) {
            $tokens = array_keys($tokensToConnections);
            $placeholders = implode(',', array_fill(0, count($tokens), '?'));
            
            $stmt = $db->prepare("
                SELECT session_token 
                FROM user_sessions 
                WHERE session_token IN ($placeholders) 
                  AND revoked_at IS NULL
            ");
            $stmt->execute($tokens);
            $validTokens = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            
            $validTokensSet = array_flip($validTokens);
            foreach ($tokensToConnections as $token => $conns) {
                if (!isset($validTokensSet[$token])) {
                    foreach ($conns as $conn) {
                        $invalidConns[] = $conn;
                    }
                }
            }
        }

        // Close all invalid connections
        foreach ($invalidConns as $conn) {
            echo "🔌 Closing connection for resource #{$conn->resourceId} due to revoked/invalid session token.\n";
            $conn->send(json_encode(['error' => 'Session expired or revoked']));
            $conn->close();
        }
    }

    /**
     * Prune expired conversation members cache entries.
     */
    public function pruneExpiredCache(): void
    {
        $now = time();
        $pruned = 0;
        foreach ($this->conversationMembersCache as $convId => $cached) {
            if ($now >= $cached['expires']) {
                unset($this->conversationMembersCache[$convId]);
                $pruned++;
            }
        }
        if ($pruned > 0) {
            echo "🧹 Pruned {$pruned} expired conversation members cache entries.\n";
        }
    }

    /**
     * Periodically ping all active client connections and disconnect idle ones.
     */
    public function pingConnections(): void
    {
        $now = time();
        $deadConns = [];

        foreach ($this->clients as $conn) {
            $data = $this->clients[$conn] ?? [];
            $lastActivity = $data['last_activity'] ?? $now;

            // If client has been silent for more than 65 seconds (2 ping cycles), disconnect
            if ($now - $lastActivity > 65) {
                $deadConns[] = $conn;
                continue;
            }

            // Send application-level ping packet
            $conn->send(json_encode([
                'event' => 'ping',
                'data' => []
            ]));
        }

        foreach ($deadConns as $conn) {
            echo "🔌 Closing inactive connection for resource #{$conn->resourceId} (no heartbeat).\n";
            $conn->close();
        }
    }
}
