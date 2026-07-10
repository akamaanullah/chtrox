<?php

namespace App\Services;

use App\Helpers\GiphyUrl;
use App\Helpers\HtmlSanitizer;
use App\Helpers\MessageEnricher;
use App\Helpers\FileAccess;
use App\Core\Model;
use App\Core\ErrorHandler;
use Exception;
use PDO;

class MessageService
{
    /**
     * Send a new message in a conversation.
     *
     * @param int $workspaceId
     * @param int $memberId
     * @param int $conversationId
     * @param string $body
     * @param int|null $replyToId
     * @param array $fileIds
     * @param string $clientMessageType
     * @param int $voiceDurationSeconds
     * @return array The enriched message payload.
     * @throws Exception
     */
    public function send(
        int $workspaceId,
        int $memberId,
        int $conversationId,
        string $body,
        ?int $replyToId = null,
        array $fileIds = [],
        string $clientMessageType = '',
        int $voiceDurationSeconds = 0
    ): array {
        if (empty($body) && empty($fileIds)) {
            throw new Exception('Message content or files are required', 400);
        }

        $db = Model::db();

        // Verify conversation exists in the workspace
        $stmt = $db->prepare("
            SELECT * FROM conversations 
            WHERE id = ? AND workspace_id = ?
        ");
        $stmt->execute([$conversationId, $workspaceId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            throw new Exception('Conversation not found', 404);
        }

        // Verify member is participant in the channel or conversation
        if ($conversation['type'] === 'channel') {
            $stmt = $db->prepare("
                SELECT id FROM channel_members 
                WHERE channel_id = ? AND workspace_member_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$conversation['channel_id'], $memberId]);
            if (!$stmt->fetch()) {
                throw new Exception('You are not a member of this channel', 403);
            }
        } else {
            $stmt = $db->prepare("
                SELECT id FROM conversation_participants 
                WHERE conversation_id = ? AND workspace_member_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$conversationId, $memberId]);
            if (!$stmt->fetch()) {
                throw new Exception('You are not a participant in this conversation', 403);
            }
        }

        // Determine message type
        $msgType = 'text';
        $clientType = strtolower(trim($clientMessageType));
        if ($clientType === 'voice' && !empty($fileIds)) {
            $msgType = 'voice';
            $body = $voiceDurationSeconds > 0 ? (string)$voiceDurationSeconds : '';
        } elseif ($clientType === 'gif') {
            if (!GiphyUrl::isGifUrl($body)) {
                throw new Exception('Invalid GIF URL', 400);
            }
            $msgType = 'gif';
        } elseif (!empty($fileIds)) {
            $msgType = $this->resolveAttachmentMessageType($db, $fileIds, $workspaceId);
        }
        if ($msgType === 'text' && GiphyUrl::isGifUrl($body)) {
            $msgType = 'gif';
        }

        // Sanitize HTML body server-side (text messages only).
        if ($msgType === 'text' && $body !== '') {
            $body = HtmlSanitizer::clean($body);
        }

        if (!empty($fileIds)) {
            $fileError = FileAccess::validateFileIdsForSend($db, $fileIds, $workspaceId, $memberId);
            if ($fileError !== null) {
                throw new Exception($fileError, 403);
            }
        }

        $mentionedIds = [];
        $channelName = null;
        $channelSlug = null;
        $state = 'sent';

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
            $messageId = (int)$db->lastInsertId();

            // Link attachments if any
            if (!empty($fileIds)) {
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
                $stmtTarget = $db->prepare("
                    SELECT workspace_member_id 
                    FROM conversation_participants 
                    WHERE conversation_id = ? AND workspace_member_id != ? AND left_at IS NULL
                    LIMIT 1
                ");
                $stmtTarget->execute([$conversationId, $memberId]);
                $recipient = $stmtTarget->fetch(PDO::FETCH_ASSOC);

                if ($recipient) {
                    $recipientId = (int)$recipient['workspace_member_id'];
                    // Query if target is online
                    $stmtPresence = $db->prepare("
                        SELECT status FROM user_presence WHERE user_id = (
                            SELECT user_id FROM workspace_members WHERE id = ?
                        )
                    ");
                    $stmtPresence->execute([$recipientId]);
                    $presence = $stmtPresence->fetch(PDO::FETCH_ASSOC);
                    $state = ($presence && $presence['status'] === 'online') ? 'delivered' : 'sent';

                    $stmtDelivery = $db->prepare("
                        INSERT INTO message_delivery_states (message_id, recipient_member_id, state)
                        VALUES (?, ?, ?)
                    ");
                    $stmtDelivery->execute([$messageId, $recipientId, $state]);
                } else {
                    // Self-conversation: recipient is the sender, so message is read instantly
                    $state = 'read';
                }
            }

            // Parse mentions (e.g. data-member-id="X")
            if (preg_match_all('/data-member-id="(\d+)"/', $body, $matches)) {
                $mentionedIds = array_unique(array_map('intval', $matches[1]));
            }
            // Filter out sender
            $mentionedIds = array_filter($mentionedIds, function ($id) use ($memberId) {
                return $id !== $memberId;
            });

            if ($conversation['type'] === 'channel') {
                $stmtChan = $db->prepare("SELECT name, slug FROM channels WHERE id = ?");
                $stmtChan->execute([$conversation['channel_id']]);
                $chanRow = $stmtChan->fetch(PDO::FETCH_ASSOC);
                if ($chanRow) {
                    $channelName = $chanRow['name'];
                    $channelSlug = $chanRow['slug'];
                }
            }

            if (!empty($mentionedIds)) {
                $plainBodyText = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $plainBodyText = preg_replace('/\s+/', ' ', $plainBodyText);
                if (mb_strlen($plainBodyText) > 200) {
                    $plainBodyText = mb_substr($plainBodyText, 0, 200) . '...';
                }

                if ($conversation['type'] === 'channel') {
                    $notifBody = 'mentioned you in #' . ($channelName ?? 'channel') . ': "' . $plainBodyText . '"';
                    $notifBodyHtml = 'mentioned you in <span class="text-primary">#' . htmlspecialchars($channelName ?? 'channel') . '</span>: "' . htmlspecialchars($plainBodyText) . '"';
                } else {
                    $notifBody = 'mentioned you: "' . $plainBodyText . '"';
                    $notifBodyHtml = 'mentioned you: "' . htmlspecialchars($plainBodyText) . '"';
                }

                $stmtNotif = $db->prepare("
                    INSERT INTO notifications (workspace_id, recipient_id, type, actor_id, title, body, body_html, reference_type, reference_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                foreach ($mentionedIds as $recipientId) {
                    $stmtNotif->execute([
                        $workspaceId,
                        $recipientId,
                        'mention',
                        $memberId,
                        null,
                        $notifBody,
                        $notifBodyHtml,
                        'message',
                        $messageId
                    ]);
                }
            }

            $db->commit();
            \App\Helpers\Cache::invalidateConversationDashboardCache($conversationId, $workspaceId);

            // Fetch the fully populated details of the inserted message
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

            // Fetch attachments details
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

            // Fetch the other participant's username for DMs
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

            $replySnippet = null;
            if ($msgDetails['reply_to_id']) {
                $replySnippet = MessageEnricher::getReplySnippet((int)$msgDetails['reply_to_id']);
            }

            return [
                'id' => $msgDetails['id'],
                'conversation_id' => $msgDetails['conversation_id'],
                'conversation_type' => $conversation['type'],
                'channel_id' => $conversation['channel_id'] ?? null,
                'channel_name' => $channelName,
                'channel_slug' => $channelSlug,
                'sender_id' => $msgDetails['sender_id'],
                'sender_name' => $msgDetails['sender_name'],
                'sender_avatar' => $msgDetails['avatar_path'],
                'sender_username' => $msgDetails['sender_username'],
                'recipient_username' => $recipientUsername,
                'mentioned_ids' => array_values($mentionedIds),
                'body' => $msgDetails['message_type'] === 'voice' ? '' : $msgDetails['body'],
                'voice_duration_seconds' => $msgDetails['message_type'] === 'voice'
                    ? max(0, (int)trim((string)$msgDetails['body']))
                    : 0,
                'message_type' => GiphyUrl::resolveMessageType(
                    (string)($msgDetails['message_type'] ?? 'text'),
                    (string)($msgDetails['body'] ?? '')
                ),
                'reply_to_id' => $msgDetails['reply_to_id'],
                'reply_snippet' => $replySnippet,
                'created_at' => $msgDetails['created_at'],
                'time_label' => date('h:i A', strtotime($msgDetails['created_at'])),
                'attachments' => $attachments,
                'read_status' => $state
            ];

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Resolves message type based on attachments.
     */
    protected function resolveAttachmentMessageType(PDO $db, array $fileIds, int $workspaceId): string
    {
        if (empty($fileIds)) {
            return 'text';
        }
        $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
        $stmt = $db->prepare("
            SELECT category FROM files 
            WHERE id IN ($placeholders) AND workspace_id = ?
        ");
        $stmt->execute(array_merge($fileIds, [$workspaceId]));
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        if (in_array('image', $categories, true)) {
            return 'image';
        }
        if (in_array('audio', $categories, true)) {
            return 'audio';
        }
        if (in_array('video', $categories, true)) {
            return 'video';
        }
        return 'document';
    }
}
