<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Helpers\GiphyUrl;
use App\Helpers\MessageEnricher;
use App\Core\Session;
use App\Core\Model;
use App\Helpers\FileAccess;
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

        $input = $this->getRequestInput();
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
        $clientType = strtolower(trim((string)($input['message_type'] ?? '')));
        if ($clientType === 'voice' && !empty($fileIds)) {
            $msgType = 'voice';
            $voiceDuration = max(0, (int)($input['voice_duration_seconds'] ?? 0));
            $body = $voiceDuration > 0 ? (string)$voiceDuration : '';
        } elseif ($clientType === 'gif') {
            if (!GiphyUrl::isGifUrl($body)) {
                $this->jsonResponse(['error' => 'invalid_gif_url', 'message' => 'Invalid GIF URL'], 400);
            }
            $msgType = 'gif';
        } elseif (!empty($fileIds)) {
            $msgType = self::resolveAttachmentMessageType($db, $fileIds, $workspaceId);
        }
        if ($msgType === 'text' && GiphyUrl::isGifUrl($body)) {
            $msgType = 'gif';
        }

        if (!empty($fileIds) && is_array($fileIds)) {
            $fileError = FileAccess::validateFileIdsForSend($db, $fileIds, $workspaceId, $memberId);
            if ($fileError !== null) {
                $this->jsonResponse(['error' => 'invalid_files', 'message' => $fileError], 403);
            }
        }

        $mentionedIds = [];
        $channelName = null;
        $channelSlug = null;

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

            // Initialize default message delivery state
            $state = 'sent';

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

            // Parse mentions (e.g. data-member-id="X")
            if (preg_match_all('/data-member-id="(\d+)"/', $body, $matches)) {
                $mentionedIds = array_unique(array_map('intval', $matches[1]));
            }
            // Filter out sender
            $mentionedIds = array_filter($mentionedIds, function ($id) use ($memberId) {
                return $id !== (int)$memberId;
            });

            if ($conversation['type'] === 'channel') {
                $stmtChan = $db->prepare("SELECT name, slug FROM channels WHERE id = ?");
                $stmtChan->execute([$conversation['channel_id']]);
                $chanRow = $stmtChan->fetch();
                if ($chanRow) {
                    $channelName = $chanRow['name'];
                    $channelSlug = $chanRow['slug'];
                }
            }

            if (!empty($mentionedIds)) {
                $plainBodyText = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                // Replace non-breaking spaces or multiple spaces
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
            \App\Helpers\Cache::invalidateConversationDashboardCache((int)$conversationId, $workspaceId);

            // Check if ChatRox AI should respond
            $aiResponsePayload = null;
            
            // 1. Fetch AI User & Member ID
            $stmtAi = $db->prepare("
                SELECT wm.id AS member_id, u.id AS user_id
                FROM workspace_members wm
                JOIN users u ON u.id = wm.user_id
                WHERE wm.workspace_id = ? AND u.username = 'ai' AND wm.status = 'active'
                LIMIT 1
            ");
            $stmtAi->execute([$workspaceId]);
            $aiData = $stmtAi->fetch(PDO::FETCH_ASSOC);

            if ($aiData) {
                $aiMemberId = (int)$aiData['member_id'];
                
                // Determine if this is a DM with AI or a mention of AI in a channel
                $shouldAiRespond = false;
                if ($conversation['type'] === 'dm') {
                    // Check if AI is a participant in this conversation
                    $stmtParticipant = $db->prepare("
                        SELECT id FROM conversation_participants 
                        WHERE conversation_id = ? AND workspace_member_id = ? AND left_at IS NULL
                        LIMIT 1
                    ");
                    $stmtParticipant->execute([$conversationId, $aiMemberId]);
                    if ($stmtParticipant->fetch()) {
                        $shouldAiRespond = true;
                    }
                } else {
                    // Check if the message body mentions @ai or @chatrox-ai
                    if (
                        strpos($body, 'data-member-id="' . $aiMemberId . '"') !== false ||
                        preg_match('/\b@ai\b/i', $body) ||
                        preg_match('/\b@chatrox-ai\b/i', $body)
                    ) {
                        $shouldAiRespond = true;
                    }
                }

                if ($shouldAiRespond) {
                    // Generate AI response
                    $aiTextHtml = \App\Services\AiService::generateResponse($workspaceId, (int)$conversationId, $memberId, $body, $aiMemberId);
                    
                    // Insert the AI's response message in DB
                    $db->beginTransaction();
                    try {
                        $stmtAiMsg = $db->prepare("
                            INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
                            VALUES (?, ?, ?, ?, 'text')
                        ");
                        $stmtAiMsg->execute([
                            $workspaceId,
                            $conversationId,
                            $aiMemberId,
                            $aiTextHtml
                        ]);
                        $aiMessageId = $db->lastInsertId();

                        // Update cursor for AI
                        $stmtAiCursor = $db->prepare("
                            INSERT INTO conversation_read_cursors (workspace_member_id, conversation_id, last_read_message_id)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                                last_read_message_id = VALUES(last_read_message_id),
                                last_read_at = CURRENT_TIMESTAMP(3)
                        ");
                        $stmtAiCursor->execute([$aiMemberId, $conversationId, $aiMessageId]);

                        // DM delivery state
                        $aiState = 'sent';
                        if ($conversation['type'] === 'dm') {
                            $stmtUserPresence = $db->prepare("
                                SELECT status FROM user_presence WHERE user_id = ?
                            ");
                            $stmtUserPresence->execute([$user['id'] ?? 0]);
                            $userPres = $stmtUserPresence->fetch(PDO::FETCH_ASSOC);
                            $aiState = ($userPres && $userPres['status'] === 'online') ? 'delivered' : 'sent';

                            $stmtAiDelivery = $db->prepare("
                                INSERT INTO message_delivery_states (message_id, recipient_member_id, state)
                                VALUES (?, ?, ?)
                            ");
                            $stmtAiDelivery->execute([$aiMessageId, $memberId, $aiState]);
                        }

                        $db->commit();

                        // Fetch detailed AI message payload to return to client
                        $stmtAiDetails = $db->prepare("
                            SELECT m.*, 
                                   u.first_name, u.last_name, u.avatar_path, u.username AS sender_username,
                                   CONCAT(u.first_name, ' ', u.last_name) AS sender_name
                            FROM messages m
                            JOIN workspace_members wm ON wm.id = m.sender_id
                            JOIN users u ON u.id = wm.user_id
                            WHERE m.id = ?
                        ");
                        $stmtAiDetails->execute([$aiMessageId]);
                        $aiMsgDetails = $stmtAiDetails->fetch(PDO::FETCH_ASSOC);

                        if ($aiMsgDetails) {
                            $aiResponsePayload = [
                                'id' => (int)$aiMsgDetails['id'],
                                'conversation_id' => (int)$aiMsgDetails['conversation_id'],
                                'conversation_type' => $conversation['type'],
                                'channel_id' => $conversation['channel_id'] ?? null,
                                'channel_name' => $channelName ?? null,
                                'channel_slug' => $channelSlug ?? null,
                                'sender_id' => (int)$aiMsgDetails['sender_id'],
                                'sender_name' => $aiMsgDetails['sender_name'],
                                'sender_avatar' => $aiMsgDetails['avatar_path'],
                                'sender_username' => $aiMsgDetails['sender_username'],
                                'recipient_username' => $user['username'] ?? null,
                                'mentioned_ids' => [],
                                'body' => $aiMsgDetails['body'],
                                'message_type' => 'text',
                                'reply_to_id' => null,
                                'created_at' => $aiMsgDetails['created_at'],
                                'time_label' => date('h:i A', strtotime($aiMsgDetails['created_at'])),
                                'attachments' => [],
                                'read_status' => $aiState
                            ];
                        }
                    } catch (\Throwable $aiEx) {
                        $db->rollBack();
                        \App\Core\ErrorHandler::logError($aiEx);
                    }
                }
            }

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
                    'created_at' => $msgDetails['created_at'],
                    'time_label' => date('h:i A', strtotime($msgDetails['created_at'])),
                    'attachments' => $attachments,
                    'read_status' => $state ?? 'sent'
                ]
            ];

            if ($aiResponsePayload !== null) {
                $response['ai_response'] = $aiResponsePayload;
            }

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

        $input = $this->getRequestInput();
        $messageId = $input['message_id'] ?? 0;
        $emoji = trim($input['emoji'] ?? '');

        if (empty($emoji)) {
            $this->jsonResponse(['error' => 'Emoji is required'], 400);
        }

        $db = Model::db();

        // Verify message belongs to this workspace
        $stmt = $db->prepare("SELECT id, conversation_id, sender_id FROM messages WHERE id = ? AND workspace_id = ?");
        $stmt->execute([$messageId, $workspaceId]);
        $message = $stmt->fetch();
        if (!$message) {
            $this->jsonResponse(['error' => 'Message not found'], 404);
        }

        // Check if the member already reacted to this message.
        $stmt = $db->prepare(
            "SELECT id, emoji FROM message_reactions WHERE message_id = ? AND workspace_member_id = ?"
        );
        $stmt->execute([$messageId, $memberId]);
        $existingReactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $prevEmoji = null;
        $action = 'added';

        $prevCount = null;
        if (!empty($existingReactions)) {
            foreach ($existingReactions as $existing) {
                if ($existing['emoji'] === $emoji) {
                    $prevEmoji = $emoji;
                    $stmt = $db->prepare("DELETE FROM message_reactions WHERE id = ?");
                    $stmt->execute([$existing['id']]);
                    $action = 'removed';
                    break;
                }
            }

            if ($action !== 'removed') {
                $prevEmoji = $existingReactions[0]['emoji'];
                $stmt = $db->prepare("DELETE FROM message_reactions WHERE message_id = ? AND workspace_member_id = ?");
                $stmt->execute([$messageId, $memberId]);

                $stmt = $db->prepare(
                    "INSERT INTO message_reactions (message_id, workspace_member_id, emoji) VALUES (?, ?, ?)"
                );
                $stmt->execute([$messageId, $memberId, $emoji]);
                $action = 'replaced';

                $stmt = $db->prepare(
                    "SELECT COUNT(*) as count FROM message_reactions WHERE message_id = ? AND emoji = ?"
                );
                $stmt->execute([$messageId, $prevEmoji]);
                $prevCountRow = $stmt->fetch();
                $prevCount = (int)($prevCountRow['count'] ?? 0);
            }
        } else {
            $stmt = $db->prepare(
                "INSERT INTO message_reactions (message_id, workspace_member_id, emoji) VALUES (?, ?, ?)"
            );
            $stmt->execute([$messageId, $memberId, $emoji]);
            $action = 'added';
        }

        if ($action === 'added' && (int)$message['sender_id'] !== (int)$memberId) {
            $stmtNotif = $db->prepare(
                "INSERT INTO notifications (workspace_id, recipient_id, type, actor_id, title, body, body_html, reference_type, reference_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $notificationTitle = 'Reacted to your message';
            $notificationBody = 'reacted with ' . $emoji . ' to your message';
            $notificationBodyHtml = 'reacted with <strong>' . htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8') . '</strong> to your message';

            $stmtNotif->execute([
                $workspaceId,
                (int)$message['sender_id'],
                'reaction',
                $memberId,
                $notificationTitle,
                $notificationBody,
                $notificationBodyHtml,
                'message',
                $messageId
            ]);
        }

        // Get total reaction counts for this message/emoji
        $stmt = $db->prepare(
            "SELECT COUNT(*) as count FROM message_reactions WHERE message_id = ? AND emoji = ?"
        );
        $stmt->execute([$messageId, $emoji]);
        $countRow = $stmt->fetch();

        \App\Helpers\Cache::invalidateConversationDashboardCache((int)$message['conversation_id'], $workspaceId);

        $reactions = 
            MessageEnricher::getMessageReactions($messageId, $memberId);

        $response = [
            'success' => true,
            'action' => $action,
            'emoji' => $emoji,
            'prev_emoji' => $prevEmoji,
            'message_id' => $messageId,
            'conversation_id' => $message['conversation_id'],
            'member_id' => $memberId,
            'recipient_member_id' => (int)$message['sender_id'],
            'count' => (int)($countRow['count'] ?? 0),
            'reactions' => $reactions
        ];
        if ($prevCount !== null) {
            $response['prev_count'] = $prevCount;
        }

        $this->jsonResponse($response);
    }

    public function reactionDetails(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $messageId = (int)($_GET['message_id'] ?? 0);
        $emoji = trim((string)($_GET['emoji'] ?? ''));

        if ($messageId <= 0) {
            $this->jsonResponse(['error' => 'message_id is required'], 400);
        }

        $db = Model::db();
        $stmt = $db->prepare("SELECT m.conversation_id, c.type AS conversation_type, c.channel_id FROM messages m JOIN conversations c ON c.id = m.conversation_id WHERE m.id = ? AND m.workspace_id = ?");
        $stmt->execute([$messageId, $workspaceId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$message) {
            $this->jsonResponse(['error' => 'Message not found'], 404);
        }

        if (!$this->memberCanAccessConversation(
            $db,
            (int)$message['conversation_id'],
            $message['conversation_type'],
            (int)($message['channel_id'] ?? 0),
            $memberId
        )) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        $query = "SELECT mr.emoji, COUNT(*) as count, MAX(CASE WHEN mr.workspace_member_id = ? THEN 1 ELSE 0 END) as reacted FROM message_reactions mr WHERE mr.message_id = ?";
        $params = [$memberId, $messageId];
        if ($emoji !== '') {
            $query .= " AND mr.emoji = ?";
            $params[] = $emoji;
        }
        $query .= " GROUP BY mr.emoji";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $reactions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reactions[$row['emoji']] = [
                'emoji' => $row['emoji'],
                'count' => (int)$row['count'],
                'reacted' => (bool)$row['reacted'],
                'reactors' => []
            ];
        }

        $query = "SELECT mr.emoji, wm.id as member_id, u.username, CONCAT(u.first_name, ' ', u.last_name) as name, COALESCE(u.avatar_path, '') as avatar FROM message_reactions mr JOIN workspace_members wm ON wm.id = mr.workspace_member_id JOIN users u ON u.id = wm.user_id WHERE mr.message_id = ?";
        $params = [$messageId];
        if ($emoji !== '') {
            $query .= " AND mr.emoji = ?";
            $params[] = $emoji;
        }
        $query .= " ORDER BY mr.created_at ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $emojiKey = $row['emoji'];
            if (!isset($reactions[$emojiKey])) {
                $reactions[$emojiKey] = [
                    'emoji' => $emojiKey,
                    'count' => 0,
                    'reacted' => false,
                    'reactors' => []
                ];
            }
            $reactions[$emojiKey]['reactors'][] = [
                'member_id' => (int)$row['member_id'],
                'username' => $row['username'],
                'name' => trim($row['name']),
                'avatar' => \App\Core\View::avatar($row['avatar']),
                'is_you' => ((int)$row['member_id'] === (int)$memberId)
            ];
        }

        $this->jsonResponse([
            'success' => true,
            'message_id' => $messageId,
            'reactions' => array_values($reactions)
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

        $input = $this->getRequestInput();
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

        \App\Helpers\Cache::invalidateConversationDashboardCache((int)$message['conversation_id'], $workspaceId);

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

        $input = $this->getRequestInput();
        $messageId = (int)($input['message_id'] ?? 0);
        $scope = strtolower(trim((string)($input['scope'] ?? 'me')));

        if ($messageId <= 0) {
            $this->jsonResponse(['error' => 'Message ID is required'], 400);
        }

        if (!in_array($scope, ['me', 'everyone'], true)) {
            $this->jsonResponse(['error' => 'Invalid delete scope'], 400);
        }

        $db = Model::db();

        // Find message
        $stmt = $db->prepare(
            "SELECT m.*, c.type AS conversation_type, c.channel_id
            FROM messages m
            JOIN conversations c ON c.id = m.conversation_id
            WHERE m.id = ? AND m.workspace_id = ?"
        );
        $stmt->execute([$messageId, $workspaceId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$message) {
            $this->jsonResponse(['error' => 'Message not found'], 404);
        }

        $isAdmin = in_array($userRole, ['owner', 'admin'], true);
        $canAccessConversation = $this->memberCanAccessConversation(
            $db,
            (int)$message['conversation_id'],
            (string)$message['conversation_type'],
            (int)($message['channel_id'] ?? 0),
            $memberId
        );

        if (!$canAccessConversation && !$isAdmin) {
            $this->jsonResponse(['error' => 'You are not a participant in this conversation'], 403);
        }

        if ($message['deleted_for_everyone_at'] !== null && $scope === 'everyone') {
            $this->jsonResponse(['error' => 'Message is already deleted for everyone'], 400);
        }

        $isSender = (int)$message['sender_id'] === (int)$memberId;

        if ($scope === 'everyone') {
            if (!$isSender && !$isAdmin) {
                $this->jsonResponse(['error' => 'You can only delete your own messages for everyone'], 403);
            }

            $stmt = $db->prepare("
                UPDATE messages 
                SET deleted_for_everyone_at = CURRENT_TIMESTAMP(3) 
                WHERE id = ?
            ");
            $stmt->execute([$messageId]);

            $stmt = $db->prepare("DELETE FROM message_pins WHERE message_id = ?");
            $stmt->execute([$messageId]);

            $stmtAudit = $db->prepare("
                INSERT INTO audit_logs (workspace_id, actor_member_id, actor_label, status, activity_type, message)
                VALUES (?, ?, ?, 'complete', 'message_delete', ?)
            ");
            $displayName = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
            $stmtAudit->execute([
                $workspaceId,
                $memberId,
                $displayName,
                "Deleted message ID {$messageId} for everyone"
            ]);

            \App\Helpers\Cache::invalidateConversationDashboardCache((int)$message['conversation_id'], $workspaceId);

            $this->jsonResponse([
                'success' => true,
                'action' => 'everyone',
                'message_id' => $messageId,
                'conversation_id' => $message['conversation_id']
            ]);
        }

        // Delete for me — hide only for the current user
        $stmt = $db->prepare("
            INSERT INTO message_user_deletions (message_id, workspace_member_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE deleted_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$messageId, $memberId]);

        \App\Helpers\Cache::invalidateConversationDashboardCache((int)$message['conversation_id'], $workspaceId);

        $this->jsonResponse([
            'success' => true,
            'action' => 'local',
            'message_id' => $messageId,
            'conversation_id' => $message['conversation_id']
        ]);
    }

    public function pin(): void
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $messageId = (int)($input['message_id'] ?? 0);
        $action = strtolower(trim((string)($input['action'] ?? 'pin')));

        if ($messageId <= 0) {
            $this->jsonResponse(['error' => 'Message ID is required'], 400);
        }

        if (!in_array($action, ['pin', 'unpin'], true)) {
            $this->jsonResponse(['error' => 'Invalid action'], 400);
        }

        $db = Model::db();

        $stmt = $db->prepare("
            SELECT m.*, c.type AS conversation_type, c.channel_id
            FROM messages m
            JOIN conversations c ON c.id = m.conversation_id
            WHERE m.id = ? AND m.workspace_id = ? AND m.deleted_for_everyone_at IS NULL
        ");
        $stmt->execute([$messageId, $workspaceId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$message) {
            $this->jsonResponse(['error' => 'Message not found'], 404);
        }

        if (!$this->memberCanAccessConversation(
            $db,
            (int)$message['conversation_id'],
            $message['conversation_type'],
            (int)($message['channel_id'] ?? 0),
            $memberId
        )) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        $conversationId = (int)$message['conversation_id'];

        if ($action === 'pin') {
            $stmt = $db->prepare("
                INSERT IGNORE INTO message_pins (conversation_id, message_id, pinned_by)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$conversationId, $messageId, $memberId]);
        } else {
            $stmt = $db->prepare("DELETE FROM message_pins WHERE conversation_id = ? AND message_id = ? AND pinned_by = ?");
            $stmt->execute([$conversationId, $messageId, $memberId]);
        }

        \App\Helpers\Cache::invalidateConversationDashboardCache((int)$conversationId, $workspaceId);

        $this->jsonResponse([
            'success' => true,
            'action' => $action,
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'is_pinned' => $action === 'pin',
        ]);
    }

    public function history(): void
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $conversationId = (int)($_GET['conversation_id'] ?? 0);
        $beforeId = (int)($_GET['before_id'] ?? 0);
        $afterId = (int)($_GET['after_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 30);

        if ($conversationId <= 0) {
            $this->jsonResponse(['error' => 'conversation_id is required'], 400);
        }
        if ($beforeId <= 0 && $afterId <= 0) {
            $this->jsonResponse(['error' => 'Either before_id or after_id is required'], 400);
        }

        $db = Model::db();
        $stmt = $db->prepare("SELECT * FROM conversations WHERE id = ? AND workspace_id = ?");
        $stmt->execute([$conversationId, $workspaceId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            $this->jsonResponse(['error' => 'Conversation not found'], 404);
        }

        if (!$this->memberCanAccessConversation(
            $db,
            $conversationId,
            $conversation['type'],
            (int)($conversation['channel_id'] ?? 0),
            $memberId
        )) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        if ($conversation['type'] !== 'dm' && $conversation['type'] !== 'channel') {
            $this->jsonResponse(['error' => 'History pagination is only available for DMs and Channels'], 400);
        }

        if ($conversation['type'] === 'channel') {
            if ($afterId > 0) {
                $page = \App\Models\ChannelConversation::historyPageAfter($conversationId, $memberId, $afterId, $limit);
                $this->jsonResponse([
                    'success' => true,
                    'messages' => $page['messages'],
                    'has_more' => $page['has_more'],
                    'newest_message_id' => $page['newest_message_id'],
                ]);
            } else {
                $page = \App\Models\ChannelConversation::historyPage($conversationId, $memberId, $beforeId, $limit);
                $this->jsonResponse([
                    'success' => true,
                    'messages' => $page['messages'],
                    'has_more' => $page['has_more'],
                    'oldest_message_id' => $page['oldest_message_id'],
                ]);
            }
        } else {
            if ($afterId > 0) {
                $page = \App\Models\DmsConversation::historyPageAfter($conversationId, $memberId, $afterId, $limit);
                $this->jsonResponse([
                    'success' => true,
                    'messages' => $page['messages'],
                    'has_more' => $page['has_more'],
                    'newest_message_id' => $page['newest_message_id'],
                ]);
            } else {
                $page = \App\Models\DmsConversation::historyPage($conversationId, $memberId, $beforeId, $limit);
                $this->jsonResponse([
                    'success' => true,
                    'messages' => $page['messages'],
                    'has_more' => $page['has_more'],
                    'oldest_message_id' => $page['oldest_message_id'],
                ]);
            }
        }
    }

    public function context(): void
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $conversationId = (int)($_GET['conversation_id'] ?? 0);
        $messageId = (int)($_GET['message_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 30);

        if ($conversationId <= 0 || $messageId <= 0) {
            $this->jsonResponse(['error' => 'conversation_id and message_id are required'], 400);
        }

        $db = Model::db();
        $stmt = $db->prepare('SELECT * FROM conversations WHERE id = ? AND workspace_id = ?');
        $stmt->execute([$conversationId, $workspaceId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            $this->jsonResponse(['error' => 'Conversation not found'], 404);
        }

        if (!$this->memberCanAccessConversation(
            $db,
            $conversationId,
            $conversation['type'],
            (int)($conversation['channel_id'] ?? 0),
            $memberId
        )) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
        }

        if (($conversation['type'] ?? '') === 'channel') {
            $page = \App\Models\ChannelConversation::contextPage($conversationId, $memberId, $messageId, $limit);
        } else {
            $page = \App\Models\DmsConversation::contextPage($conversationId, $memberId, $messageId, $limit);
        }

        $this->jsonResponse(array_merge(['success' => true], $page));
    }

    public function forward(): void
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
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
            foreach ($createdMessages as $createdMsg) {
                \App\Helpers\Cache::invalidateConversationDashboardCache((int)$createdMsg['conversation_id'], $workspaceId);
            }
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

        $input = $this->getRequestInput();
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

        \App\Helpers\Cache::invalidateConversationDashboardCache((int)$conversationId, $workspaceId);

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
     * @param array<int, int|string> $fileIds
     */
    private function resolveAttachmentMessageType(PDO $db, array $fileIds, int $workspaceId): string
    {
        $fileIds = array_values(array_filter(array_map('intval', $fileIds)));
        if (empty($fileIds)) {
            return 'file';
        }

        $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
        $stmt = $db->prepare("SELECT category FROM files WHERE workspace_id = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([$workspaceId], $fileIds));
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($categories)) {
            return 'file';
        }

        $allAudio = true;
        foreach ($categories as $category) {
            if ($category !== 'audio') {
                $allAudio = false;
                break;
            }
        }

        return ($allAudio && count($categories) === 1) ? 'voice' : 'file';
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
            'body' => $msgDetails['message_type'] === 'voice' ? '' : $msgDetails['body'],
            'voice_duration_seconds' => $msgDetails['message_type'] === 'voice'
                ? max(0, (int)trim((string)$msgDetails['body']))
                : 0,
            'message_type' => GiphyUrl::resolveMessageType(
                (string)($msgDetails['message_type'] ?? 'text'),
                (string)($msgDetails['body'] ?? '')
            ),
            'reply_to_id' => $msgDetails['reply_to_id'],
            'created_at' => $msgDetails['created_at'],
            'time_label' => date('h:i A', strtotime($msgDetails['created_at'])),
            'attachments' => $attachments,
            'read_status' => $readStatus,
            'is_forwarded' => !empty($msgDetails['forwarded_from_message_id']),
        ];
    }
}
