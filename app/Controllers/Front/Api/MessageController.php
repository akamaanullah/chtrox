<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Model;
use App\Models\ForwardTarget;
use PDO;

class MessageController extends Controller
{
    public function send(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $conversationId = $input['conversation_id'] ?? 0;
        $body = trim($input['body'] ?? '');
        $replyToId = !empty($input['reply_to_id']) ? (int)$input['reply_to_id'] : null;
        $fileIds = $input['file_ids'] ?? []; // Array of file IDs

        if (empty($body) && empty($fileIds)) {
            $this->jsonResponse(['error' => 'Message content or files are required'], 400);
        }

        $db = Model::db();

        // Verify participant in conversation
        $stmt = $db->prepare("
            SELECT * FROM conversations 
            WHERE id = ? AND workspace_id = ?
        ");
        $stmt->execute([$conversationId, $workspaceId]);
        $conversation = $stmt->fetch();

        if (!$conversation) {
            $this->jsonResponse(['error' => 'Conversation not found'], 404);
        }

        // Verify member is participant in the channel or conversation
        if ($conversation['type'] === 'channel') {
            $stmt = $db->prepare("
                SELECT id FROM channel_members 
                WHERE channel_id = ? AND workspace_member_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$conversation['channel_id'], $memberId]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['error' => 'You are not a member of this channel'], 403);
            }
        } else {
            $stmt = $db->prepare("
                SELECT id FROM conversation_participants 
                WHERE conversation_id = ? AND workspace_member_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$conversationId, $memberId]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['error' => 'You are not a participant in this conversation'], 403);
            }
        }

        // Determine message type
        $msgType = 'text';
        if (!empty($fileIds)) {
            $msgType = 'file';
        }
        // If body looks like an absolute Giphy link, we could classify as 'gif'
        if (preg_is_gif_url($body)) {
            $msgType = 'gif';
        }

        $db->beginTransaction();
        try {
            // Insert message
            $stmt = $db->prepare("
                INSERT INTO messages (workspace_id, conversation_id, sender_id, reply_to_id, body, message_type)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $workspaceId,
                $conversationId,
                $memberId,
                $replyToId,
                $body,
                $msgType
            ]);
            $messageId = $db->lastInsertId();

            // Link attachments if any
            if (!empty($fileIds) && is_array($fileIds)) {
                $stmtAttach = $db->prepare("
                    INSERT INTO message_attachments (message_id, file_id)
                    VALUES (?, ?)
                ");
                foreach ($fileIds as $fId) {
                    $stmtAttach->execute([$messageId, $fId]);
                }
            }

            // Auto-update read cursor for sender
            $stmtCursor = $db->prepare("
                INSERT INTO conversation_read_cursors (workspace_member_id, conversation_id, last_read_message_id)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    last_read_message_id = VALUES(last_read_message_id),
                    last_read_at = CURRENT_TIMESTAMP(3)
            ");
            $stmtCursor->execute([$memberId, $conversationId, $messageId]);

            // If it is a DM, build default delivery states for the recipient
            if ($conversation['type'] === 'dm') {
                // Find target recipient
                $stmtTarget = $db->prepare("
                    SELECT workspace_member_id 
                    FROM conversation_participants 
                    WHERE conversation_id = ? AND workspace_member_id != ? AND left_at IS NULL
                    LIMIT 1
                ");
                $stmtTarget->execute([$conversationId, $memberId]);
                $recipient = $stmtTarget->fetch();

                if ($recipient) {
                    $recipientId = $recipient['workspace_member_id'];
                    // Query if target is online
                    $stmtPresence = $db->prepare("
                        SELECT status FROM user_presence WHERE user_id = (
                            SELECT user_id FROM workspace_members WHERE id = ?
                        )
                    ");
                    $stmtPresence->execute([$recipientId]);
                    $presence = $stmtPresence->fetch();
                    $state = ($presence && $presence['status'] === 'online') ? 'delivered' : 'sent';

                    $stmtDelivery = $db->prepare("
                        INSERT INTO message_delivery_states (message_id, recipient_member_id, state)
                        VALUES (?, ?, ?)
                    ");
                    $stmtDelivery->execute([$messageId, $recipientId, $state]);
                }
            }

            $db->commit();

            // Prepare JSON payload for return and websockets
            $stmtMsg = $db->prepare("
                SELECT m.*, 
                       u.first_name, u.last_name, u.avatar_path, u.username AS sender_username,
                       CONCAT(u.first_name, ' ', u.last_name) AS sender_name
                FROM messages m
                JOIN workspace_members wm ON wm.id = m.sender_id
                JOIN users u ON u.id = wm.user_id
                WHERE m.id = ?
            ");
            $stmtMsg->execute([$messageId]);
            $msgDetails = $stmtMsg->fetch(PDO::FETCH_ASSOC);

            // Fetch attachments
            $attachments = [];
            if (!empty($fileIds)) {
                $stmtFiles = $db->prepare("
                    SELECT f.id, f.original_name, f.mime_type, f.extension, f.size_bytes, f.category
                    FROM message_attachments ma
                    JOIN files f ON f.id = ma.file_id
                    WHERE ma.message_id = ?
                ");
                $stmtFiles->execute([$messageId]);
                $attachments = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);
                foreach ($attachments as &$att) {
                    $att['url'] = BASE_URL . '/files/download/' . $att['id'];
                }
            }

            // Fetch the other participant's username for DMs (used by sidebar updater on recipient side)
            $recipientUsername = null;
            if ($conversation['type'] === 'dm') {
                $stmtRecipUser = $db->prepare("
                    SELECT u.username
                    FROM conversation_participants cp
                    JOIN workspace_members wm ON wm.id = cp.workspace_member_id
                    JOIN users u ON u.id = wm.user_id
                    WHERE cp.conversation_id = ? AND cp.workspace_member_id != ? AND cp.left_at IS NULL
                    LIMIT 1
                ");
                $stmtRecipUser->execute([$conversationId, $memberId]);
                $recipUser = $stmtRecipUser->fetch(PDO::FETCH_ASSOC);
                $recipientUsername = $recipUser ? $recipUser['username'] : null;
            }

            $response = [
                'success' => true,
                'message' => [
                    'id' => $msgDetails['id'],
                    'conversation_id' => $msgDetails['conversation_id'],
                    'conversation_type' => $conversation['type'],
                    'channel_id' => $conversation['channel_id'] ?? null,
                    'sender_id' => $msgDetails['sender_id'],
                    'sender_name' => $msgDetails['sender_name'],
                    'sender_avatar' => $msgDetails['avatar_path'],
                    'sender_username' => $msgDetails['sender_username'],
                    'recipient_username' => $recipientUsername,
                    'body' => $msgDetails['body'],
                    'message_type' => $msgDetails['message_type'],
                    'reply_to_id' => $msgDetails['reply_to_id'],
                    'created_at' => $msgDetails['created_at'],
                    'time_label' => date('h:i A', strtotime($msgDetails['created_at'])),
                    'attachments' => $attachments,
                    'read_status' => $state ?? 'sent'
                ]
            ];

            $this->jsonResponse($response);


        } catch (\Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => 'Message send failed: ' . $e->getMessage()], 500);
        }
    }

    public function react(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $messageId = $input['message_id'] ?? 0;
        $emoji = trim($input['emoji'] ?? '');

        if (empty($emoji)) {
            $this->jsonResponse(['error' => 'Emoji is required'], 400);
        }

        $db = Model::db();

        // Verify message belongs to this workspace
        $stmt = $db->prepare("SELECT id, conversation_id FROM messages WHERE id = ? AND workspace_id = ?");
        $stmt->execute([$messageId, $workspaceId]);
        $message = $stmt->fetch();
        if (!$message) {
            $this->jsonResponse(['error' => 'Message not found'], 404);
        }

        // Check if already reacted
        $stmt = $db->prepare("
            SELECT id FROM message_reactions 
            WHERE message_id = ? AND workspace_member_id = ? AND emoji = ?
        ");
        $stmt->execute([$messageId, $memberId, $emoji]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Delete reaction (toggle off)
            $stmt = $db->prepare("DELETE FROM message_reactions WHERE id = ?");
            $stmt->execute([$existing['id']]);
            $action = 'removed';
        } else {
            // Add reaction
            $stmt = $db->prepare("
                INSERT INTO message_reactions (message_id, workspace_member_id, emoji)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$messageId, $memberId, $emoji]);
            $action = 'added';
        }

        // Get total reaction counts for this message/emoji
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM message_reactions 
            WHERE message_id = ? AND emoji = ?
        ");
        $stmt->execute([$messageId, $emoji]);
        $countRow = $stmt->fetch();

        $this->jsonResponse([
            'success' => true,
            'action' => $action,
            'emoji' => $emoji,
            'message_id' => $messageId,
            'conversation_id' => $message['conversation_id'],
            'member_id' => $memberId,
            'count' => (int)($countRow['count'] ?? 0)
        ]);
    }

    public function edit(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $messageId = $input['message_id'] ?? 0;
        $body = trim($input['body'] ?? '');

        if (empty($body)) {
            $this->jsonResponse(['error' => 'Message body cannot be empty'], 400);
        }

        $db = Model::db();

        // Find message
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ? AND workspace_id = ?");
        $stmt->execute([$messageId, $workspaceId]);
        $message = $stmt->fetch();

        if (!$message) {
            $this->jsonResponse(['error' => 'Message not found'], 404);
        }

        // Only the sender can edit their own messages
        if ((int)$message['sender_id'] !== (int)$memberId) {
            $this->jsonResponse(['error' => 'You can only edit your own messages'], 403);
        }

        if ($message['deleted_for_everyone_at'] !== null) {
            $this->jsonResponse(['error' => 'Cannot edit a deleted message'], 400);
        }

        // Update message body
        $stmt = $db->prepare("
            UPDATE messages 
            SET body = ?, edited_at = CURRENT_TIMESTAMP(3) 
            WHERE id = ?
        ");
        $stmt->execute([$body, $messageId]);

        $this->jsonResponse([
            'success' => true,
            'message_id' => $messageId,
            'conversation_id' => $message['conversation_id'],
            'body' => $body
        ]);
    }

    public function delete(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;
        $userRole = $user['role'] ?? 'member';

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $messageId = $input['message_id'] ?? 0;

        $db = Model::db();

        // Find message
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ? AND workspace_id = ?");
        $stmt->execute([$messageId, $workspaceId]);
        $message = $stmt->fetch();

        if (!$message) {
            $this->jsonResponse(['error' => 'Message not found'], 404);
        }

        // If the current user is the sender OR is admin/owner, they can do a delete for everyone
        if ((int)$message['sender_id'] === (int)$memberId || in_array($userRole, ['owner', 'admin'])) {
            $stmt = $db->prepare("
                UPDATE messages 
                SET deleted_for_everyone_at = CURRENT_TIMESTAMP(3) 
                WHERE id = ?
            ");
            $stmt->execute([$messageId]);

            // Audit Log
            $stmtAudit = $db->prepare("
                INSERT INTO audit_logs (workspace_id, actor_member_id, actor_label, status, activity_type, message)
                VALUES (?, ?, ?, 'complete', 'message_delete', ?)
            ");
            $displayName = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
            $stmtAudit->execute([
                $workspaceId,
                $memberId,
                $displayName,
                "Deleted message ID {$messageId}"
            ]);

            $this->jsonResponse([
                'success' => true,
                'action' => 'everyone',
                'message_id' => $messageId,
                'conversation_id' => $message['conversation_id']
            ]);
        } else {
            // Otherwise, it is just deleted locally for this user
            $stmt = $db->prepare("
                INSERT INTO message_user_deletions (message_id, workspace_member_id)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE deleted_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$messageId, $memberId]);

            $this->jsonResponse([
                'success' => true,
                'action' => 'local',
                'message_id' => $messageId,
                'conversation_id' => $message['conversation_id']
            ]);
        }
    }

    public function forward(): void
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $messageId = (int)($input['message_id'] ?? 0);
        $targets = $input['targets'] ?? [];

        if ($messageId <= 0) {
            $this->jsonResponse(['error' => 'Message ID is required'], 400);
        }

        if (!is_array($targets) || empty($targets)) {
            $this->jsonResponse(['error' => 'Select at least one destination'], 400);
        }

        $db = Model::db();

        $stmt = $db->prepare("
            SELECT m.*, c.type AS conversation_type, c.channel_id
            FROM messages m
            JOIN conversations c ON c.id = m.conversation_id
            WHERE m.id = ? AND m.workspace_id = ? AND m.deleted_for_everyone_at IS NULL
        ");
        $stmt->execute([$messageId, $workspaceId]);
        $sourceMessage = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sourceMessage) {
            $this->jsonResponse(['error' => 'Message not found'], 404);
        }

        if (!$this->memberCanAccessConversation($db, $sourceMessage['conversation_id'], $sourceMessage['conversation_type'], (int)($sourceMessage['channel_id'] ?? 0), $memberId)) {
            $this->jsonResponse(['error' => 'You cannot forward this message'], 403);
        }

        $stmt = $db->prepare("SELECT file_id FROM message_attachments WHERE message_id = ?");
        $stmt->execute([$messageId]);
        $sourceFileIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        if ($sourceMessage['body'] === '' && empty($sourceFileIds)) {
            $this->jsonResponse(['error' => 'Nothing to forward'], 400);
        }

        $createdMessages = [];
        $seenTargets = [];

        $db->beginTransaction();
        try {
            foreach ($targets as $target) {
                if (!is_array($target)) {
                    continue;
                }

                $targetType = trim((string)($target['type'] ?? ''));
                $targetId = trim((string)($target['id'] ?? ''));
                if ($targetType === '' || $targetId === '') {
                    continue;
                }

                $targetKey = $targetType . ':' . $targetId;
                if (isset($seenTargets[$targetKey])) {
                    continue;
                }
                $seenTargets[$targetKey] = true;

                $targetConversationId = ForwardTarget::resolveConversationId($targetType, $targetId);
                if ($targetConversationId <= 0) {
                    continue;
                }

                $stmt = $db->prepare("
                    SELECT id, type, channel_id
                    FROM conversations
                    WHERE id = ? AND workspace_id = ?
                ");
                $stmt->execute([$targetConversationId, $workspaceId]);
                $targetConversation = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$targetConversation) {
                    continue;
                }

                if (!$this->memberCanAccessConversation(
                    $db,
                    $targetConversationId,
                    $targetConversation['type'],
                    (int)($targetConversation['channel_id'] ?? 0),
                    $memberId
                )) {
                    continue;
                }

                $stmt = $db->prepare("
                    INSERT INTO messages (workspace_id, conversation_id, sender_id, reply_to_id, forwarded_from_message_id, body, message_type)
                    VALUES (?, ?, ?, NULL, ?, ?, ?)
                ");
                $stmt->execute([
                    $workspaceId,
                    $targetConversationId,
                    $memberId,
                    $messageId,
                    $sourceMessage['body'],
                    $sourceMessage['message_type'],
                ]);
                $newMessageId = (int)$db->lastInsertId();

                if (!empty($sourceFileIds)) {
                    $stmtAttach = $db->prepare("
                        INSERT INTO message_attachments (message_id, file_id)
                        VALUES (?, ?)
                    ");
                    foreach ($sourceFileIds as $fileId) {
                        $stmtAttach->execute([$newMessageId, $fileId]);
                    }
                }

                $stmtForward = $db->prepare("
                    INSERT INTO message_forwards (source_message_id, target_conversation_id, forwarded_by)
                    VALUES (?, ?, ?)
                ");
                $stmtForward->execute([$messageId, $targetConversationId, $memberId]);

                $stmtCursor = $db->prepare("
                    INSERT INTO conversation_read_cursors (workspace_member_id, conversation_id, last_read_message_id)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        last_read_message_id = VALUES(last_read_message_id),
                        last_read_at = CURRENT_TIMESTAMP(3)
                ");
                $stmtCursor->execute([$memberId, $targetConversationId, $newMessageId]);

                $deliveryState = 'sent';
                if ($targetConversation['type'] === 'dm') {
                    $stmtTarget = $db->prepare("
                        SELECT workspace_member_id
                        FROM conversation_participants
                        WHERE conversation_id = ? AND workspace_member_id != ? AND left_at IS NULL
                        LIMIT 1
                    ");
                    $stmtTarget->execute([$targetConversationId, $memberId]);
                    $recipient = $stmtTarget->fetch(PDO::FETCH_ASSOC);

                    if ($recipient) {
                        $recipientId = (int)$recipient['workspace_member_id'];
                        $stmtPresence = $db->prepare("
                            SELECT status FROM user_presence WHERE user_id = (
                                SELECT user_id FROM workspace_members WHERE id = ?
                            )
                        ");
                        $stmtPresence->execute([$recipientId]);
                        $presence = $stmtPresence->fetch(PDO::FETCH_ASSOC);
                        $deliveryState = ($presence && $presence['status'] === 'online') ? 'delivered' : 'sent';

                        $stmtDelivery = $db->prepare("
                            INSERT INTO message_delivery_states (message_id, recipient_member_id, state)
                            VALUES (?, ?, ?)
                        ");
                        $stmtDelivery->execute([$newMessageId, $recipientId, $deliveryState]);
                    }
                }

                $payload = $this->buildMessagePayload(
                    $db,
                    $newMessageId,
                    $targetConversation,
                    $memberId,
                    $deliveryState,
                    $targetType,
                    $targetId
                );
                if ($payload !== null) {
                    $createdMessages[] = $payload;
                }
            }

            if (empty($createdMessages)) {
                $db->rollBack();
                $this->jsonResponse(['error' => 'Could not forward to the selected destinations'], 400);
            }

            $db->commit();
            $this->jsonResponse([
                'success' => true,
                'messages' => $createdMessages,
                'forwarded_count' => count($createdMessages),
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => 'Forward failed: ' . $e->getMessage()], 500);
        }
    }

    public function markRead(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $conversationId = $input['conversation_id'] ?? 0;
        $lastReadMessageId = $input['last_read_message_id'] ?? null;

        if (!$conversationId) {
            $this->jsonResponse(['error' => 'Conversation ID is required'], 400);
        }

        $db = Model::db();

        // If no message ID is passed, find the latest message ID in this conversation
        if ($lastReadMessageId === null) {
            $stmt = $db->prepare("
                SELECT id FROM messages 
                WHERE conversation_id = ? AND deleted_for_everyone_at IS NULL
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$conversationId]);
            $row = $stmt->fetch();
            $lastReadMessageId = $row ? $row['id'] : null;
        }

        if ($lastReadMessageId === null) {
            $this->jsonResponse(['success' => true, 'message' => 'No messages to mark read']);
        }

        // Update read cursor
        $stmt = $db->prepare("
            INSERT INTO conversation_read_cursors (workspace_member_id, conversation_id, last_read_message_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                last_read_message_id = VALUES(last_read_message_id),
                last_read_at = CURRENT_TIMESTAMP(3)
        ");
        $stmt->execute([$memberId, $conversationId, $lastReadMessageId]);

        // Also if DM, update message delivery states for the user to 'read'
        $stmt = $db->prepare("
            UPDATE message_delivery_states mds
            JOIN messages m ON m.id = mds.message_id
            SET mds.state = 'read', mds.read_at = CURRENT_TIMESTAMP(3)
            WHERE m.conversation_id = ?
              AND mds.recipient_member_id = ?
              AND mds.state != 'read'
        ");
        $stmt->execute([$conversationId, $memberId]);

        $this->jsonResponse([
            'success' => true,
            'conversation_id' => $conversationId,
            'last_read_message_id' => $lastReadMessageId
        ]);
    }

    private function memberCanAccessConversation(PDO $db, int $conversationId, string $type, int $channelId, int $memberId): bool
    {
        if ($type === 'channel') {
            $stmt = $db->prepare("
                SELECT id FROM channel_members
                WHERE channel_id = ? AND workspace_member_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$channelId, $memberId]);

            return (bool)$stmt->fetch();
        }

        $stmt = $db->prepare("
            SELECT id FROM conversation_participants
            WHERE conversation_id = ? AND workspace_member_id = ? AND left_at IS NULL
        ");
        $stmt->execute([$conversationId, $memberId]);

        return (bool)$stmt->fetch();
    }

    /**
     * @param array<string, mixed> $conversation
     * @return array<string, mixed>|null
     */
    private function buildMessagePayload(
        PDO $db,
        int $messageId,
        array $conversation,
        int $memberId,
        string $readStatus = 'sent',
        ?string $targetType = null,
        ?string $targetId = null
    ): ?array {
        $stmtMsg = $db->prepare("
            SELECT m.*,
                   u.first_name, u.last_name, u.avatar_path, u.username AS sender_username,
                   CONCAT(u.first_name, ' ', u.last_name) AS sender_name
            FROM messages m
            JOIN workspace_members wm ON wm.id = m.sender_id
            JOIN users u ON u.id = wm.user_id
            WHERE m.id = ?
        ");
        $stmtMsg->execute([$messageId]);
        $msgDetails = $stmtMsg->fetch(PDO::FETCH_ASSOC);
        if (!$msgDetails) {
            return null;
        }

        $stmtFiles = $db->prepare("
            SELECT f.id, f.original_name, f.mime_type, f.extension, f.size_bytes, f.category
            FROM message_attachments ma
            JOIN files f ON f.id = ma.file_id
            WHERE ma.message_id = ?
        ");
        $stmtFiles->execute([$messageId]);
        $attachments = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);
        foreach ($attachments as &$att) {
            $att['url'] = BASE_URL . '/files/download/' . $att['id'];
        }
        unset($att);

        $recipientUsername = null;
        $channelSlug = null;

        if ($conversation['type'] === 'dm') {
            $stmtRecipUser = $db->prepare("
                SELECT u.username
                FROM conversation_participants cp
                JOIN workspace_members wm ON wm.id = cp.workspace_member_id
                JOIN users u ON u.id = wm.user_id
                WHERE cp.conversation_id = ? AND cp.workspace_member_id != ? AND cp.left_at IS NULL
                LIMIT 1
            ");
            $stmtRecipUser->execute([(int)$conversation['id'], $memberId]);
            $recipUser = $stmtRecipUser->fetch(PDO::FETCH_ASSOC);
            $recipientUsername = $recipUser ? $recipUser['username'] : null;
        } elseif ($targetType === 'channel' && $targetId) {
            $channelSlug = $targetId;
        } elseif (!empty($conversation['channel_id'])) {
            $stmtSlug = $db->prepare("SELECT slug FROM channels WHERE id = ? LIMIT 1");
            $stmtSlug->execute([(int)$conversation['channel_id']]);
            $channelSlug = $stmtSlug->fetchColumn() ?: null;
        }

        return [
            'id' => $msgDetails['id'],
            'conversation_id' => $msgDetails['conversation_id'],
            'conversation_type' => $conversation['type'],
            'channel_id' => $conversation['channel_id'] ?? null,
            'channel_slug' => $channelSlug,
            'sender_id' => $msgDetails['sender_id'],
            'sender_name' => $msgDetails['sender_name'],
            'sender_avatar' => $msgDetails['avatar_path'],
            'sender_username' => $msgDetails['sender_username'],
            'recipient_username' => $recipientUsername,
            'body' => $msgDetails['body'],
            'message_type' => $msgDetails['message_type'],
            'reply_to_id' => $msgDetails['reply_to_id'],
            'created_at' => $msgDetails['created_at'],
            'time_label' => date('h:i A', strtotime($msgDetails['created_at'])),
            'attachments' => $attachments,
            'read_status' => $readStatus,
            'is_forwarded' => !empty($msgDetails['forwarded_from_message_id']),
        ];
    }
}

// Simple helper to check if a body string is a direct Giphy GIF URL
if (!function_exists('preg_is_gif_url')) {
    function preg_is_gif_url(string $url): bool {
        return (bool) preg_match('/^https?:\/\/[a-zA-Z0-9.-]+\.giphy\.com\/v1\/gifs\/.*$/i', $url) 
            || (bool) preg_match('/^https?:\/\/media[0-9]*\.giphy\.com\/media\/[a-zA-Z0-9]+\/giphy\.(gif|webp|mp4)$/i', $url);
    }
}
