<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Model;
use App\Helpers\SystemMessage;
use App\Models\ChannelJoinRequest;
use App\Models\AuditLog;
use App\Services\ChannelService;
use PDO;

class ChannelController extends Controller
{
    public function create(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;
        $displayName = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $name = trim((string)($input['name'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $visibility = trim((string)($input['visibility'] ?? 'public'));
        $addAll = !empty($input['add_all_members']);
        $members = $input['members'] ?? [];

        try {
            $channelService = new ChannelService();
            $channelData = $channelService->create(
                $workspaceId,
                $memberId,
                $displayName,
                $name,
                $description,
                $visibility,
                $addAll,
                $members
            );

            // Log activity audit trail
            AuditLog::log(
                $workspaceId,
                $memberId,
                $displayName,
                'channel_create',
                "Created channel #{$channelData['name']} ({$channelData['visibility']})"
            );

            $this->jsonResponse([
                'success' => true,
                'channel' => $channelData
            ]);
        } catch (\Exception $e) {
            \App\Core\ErrorHandler::logError($e);
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            $msg = APP_DEBUG ? 'Channel creation failed: ' . $e->getMessage() : 'Channel creation failed. Please try again.';
            $this->jsonResponse(['error' => $msg], $statusCode);
        }
    }

    public function join(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $channelId = $input['channel_id'] ?? 0;

        $db = Model::db();

        // Find the channel
        $stmt = $db->prepare("
            SELECT c.*, conv.id as conversation_id
            FROM channels c
            JOIN conversations conv ON conv.channel_id = c.id
            WHERE c.id = ? AND c.workspace_id = ? AND c.status = 'active'
        ");
        $stmt->execute([$channelId, $workspaceId]);
        $channel = $stmt->fetch();

        if (!$channel) {
            $this->jsonResponse(['error' => 'Channel not found'], 404);
        }

        // Check if already joined
        $stmt = $db->prepare("SELECT id FROM channel_members WHERE channel_id = ? AND workspace_member_id = ? AND left_at IS NULL");
        $stmt->execute([$channelId, $memberId]);
        if ($stmt->fetch()) {
            $this->jsonResponse(['success' => true, 'message' => 'Already joined', 'slug' => $channel['slug']]);
        }

        if ($channel['visibility'] === 'private') {
            $stmt = $db->prepare("SELECT status FROM channel_join_requests WHERE channel_id = ? AND workspace_member_id = ? LIMIT 1");
            $stmt->execute([$channelId, $memberId]);
            $requestStatus = $stmt->fetchColumn();

            if ($requestStatus === 'pending') {
                $this->jsonResponse([
                    'success' => true,
                    'requested' => true,
                    'message' => 'Your join request is already pending'
                ]);
            }

            $displayName = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
            $isNewRequest = false;

            if ($requestStatus !== false) {
                $stmt = $db->prepare("UPDATE channel_join_requests SET status = 'pending', updated_at = CURRENT_TIMESTAMP WHERE channel_id = ? AND workspace_member_id = ?");
                $stmt->execute([$channelId, $memberId]);
                $isNewRequest = true;
            } else {
                $stmt = $db->prepare("INSERT INTO channel_join_requests (workspace_id, channel_id, workspace_member_id, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$workspaceId, $channelId, $memberId]);
                $isNewRequest = true;
            }

            // Log to audit trail
            AuditLog::log(
                $workspaceId,
                $memberId,
                $displayName,
                'OTHER',
                "Requested to join channel #{$channel['name']}"
            );

            // Notify channel owner/admins if new request
            if ($isNewRequest) {

                $stmt = $db->prepare("
                    SELECT DISTINCT wm.id
                    FROM workspace_members wm
                    JOIN channel_members cm ON cm.workspace_member_id = wm.id
                    WHERE wm.workspace_id = ?
                      AND wm.id != ?
                      AND cm.channel_id = ?
                      AND cm.role IN ('owner', 'admin')
                ");
                $stmt->execute([$workspaceId, $memberId, $channelId]);
                $admins = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                foreach ($admins as $adminId) {
                    $stmt = $db->prepare("
                        INSERT INTO notifications (workspace_id, recipient_id, type, actor_id, title, body, reference_type, reference_id)
                        VALUES (?, ?, 'channel_join', ?, ?, ?, 'channel', ?)
                    ");
                    $stmt->execute([
                        $workspaceId,
                        $adminId,
                        $memberId,
                        'Join request received',
                        htmlspecialchars($displayName) . ' requested to join #' . htmlspecialchars($channel['name']) . '.',
                        $channelId
                    ]);
                }
            }

            $this->jsonResponse([
                'success' => true,
                'requested' => true,
                'message' => 'Join request sent',
                'admins' => $admins,
                'channel_name' => $channel['name'],
                'channel_id' => $channelId,
                'display_name' => $displayName
            ]);
        }

        $db->beginTransaction();
        try {
            // Join in channel_members (using IGNORE or REPLACE if left_at needs toggle, let's delete old inactive if any first)
            $stmt = $db->prepare("DELETE FROM channel_members WHERE channel_id = ? AND workspace_member_id = ?");
            $stmt->execute([$channelId, $memberId]);

            $stmt = $db->prepare("
                INSERT INTO channel_members (channel_id, workspace_member_id, role)
                VALUES (?, ?, 'member')
            ");
            $stmt->execute([$channelId, $memberId]);

            // Add to conversation_participants
            $stmt = $db->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND workspace_member_id = ?");
            $stmt->execute([$channel['conversation_id'], $memberId]);

            $stmt = $db->prepare("
                INSERT INTO conversation_participants (conversation_id, workspace_member_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$channel['conversation_id'], $memberId]);

            // MED-09: Sync member_count from real count to prevent drift
            $stmt = $db->prepare("
                UPDATE channels
                SET member_count = (SELECT COUNT(*) FROM channel_members WHERE channel_id = ? AND left_at IS NULL)
                WHERE id = ?
            ");
            $stmt->execute([$channelId, $channelId]);

            // System join message
            $stmt = $db->prepare("
                INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
                VALUES (?, ?, ?, ?, 'system')
            ");
            $stmt->execute([$workspaceId, $channel['conversation_id'], $memberId, SystemMessage::bodyMemberJoined()]);

            $db->commit();
            \App\Helpers\Cache::invalidateConversationDashboardCache((int)$channel['conversation_id'], $workspaceId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Joined channel successfully',
                'slug' => $channel['slug']
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            \App\Core\ErrorHandler::logError($e);
            $msg = APP_DEBUG ? 'Failed to join channel: ' . $e->getMessage() : 'Failed to join channel due to an internal server error.';
            $this->jsonResponse(['error' => $msg], 500);
        }
    }

    public function leave(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $channelId = $input['channel_id'] ?? 0;

        $db = Model::db();

        // Find channel
        $stmt = $db->prepare("
            SELECT c.*, conv.id as conversation_id
            FROM channels c
            JOIN conversations conv ON conv.channel_id = c.id
            WHERE c.id = ? AND c.workspace_id = ?
        ");
        $stmt->execute([$channelId, $workspaceId]);
        $channel = $stmt->fetch();

        if (!$channel) {
            $this->jsonResponse(['error' => 'Channel not found'], 404);
        }

        if ($channel['is_default']) {
            $this->jsonResponse(['error' => 'You cannot leave default workspace channels'], 400);
        }

        // Check if user is the channel owner or admin
        $stmt = $db->prepare("
            SELECT role FROM channel_members 
            WHERE channel_id = ? AND workspace_member_id = ? AND left_at IS NULL
        ");
        $stmt->execute([$channelId, $memberId]);
        $memberRole = $stmt->fetchColumn();

        if (!$memberRole) {
            $this->jsonResponse(['error' => 'You are not a member of this channel'], 400);
        }

        $db->beginTransaction();
        try {
            $transferMsg = null;
            $promotedMemberId = null;

            if ($memberRole === 'owner' || $memberRole === 'admin') {
                // Get all other members of this channel
                $otherMembersStmt = $db->prepare("
                    SELECT cm.workspace_member_id, cm.role, u.first_name, u.last_name
                    FROM channel_members cm
                    JOIN workspace_members wm ON cm.workspace_member_id = wm.id
                    JOIN users u ON wm.user_id = u.id
                    WHERE cm.channel_id = ? AND cm.workspace_member_id != ? AND cm.left_at IS NULL
                    ORDER BY cm.role ASC, cm.joined_at ASC
                ");
                $otherMembersStmt->execute([$channelId, $memberId]);
                $otherMembers = $otherMembersStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($otherMembers)) {
                    $hasOtherOwner = false;
                    $hasOtherAdmin = false;
                    $adminCandidate = null;
                    $memberCandidate = null;

                    foreach ($otherMembers as $om) {
                        if ($om['role'] === 'owner') {
                            $hasOtherOwner = true;
                        }
                        if ($om['role'] === 'admin') {
                            $hasOtherAdmin = true;
                            if (!$adminCandidate) {
                                $adminCandidate = $om;
                            }
                        }
                        if ($om['role'] === 'member') {
                            if (!$memberCandidate) {
                                $memberCandidate = $om;
                            }
                        }
                    }

                    if ($memberRole === 'owner' && !$hasOtherOwner) {
                        // Need to transfer ownership (role = 'owner')
                        $candidate = $adminCandidate ?: $memberCandidate;
                        if ($candidate) {
                            $promotedMemberId = $candidate['workspace_member_id'];
                            $candidateName = $candidate['first_name'] . ' ' . $candidate['last_name'];
                            
                            // Update role to owner
                            $updateRoleStmt = $db->prepare("
                                UPDATE channel_members 
                                SET role = 'owner' 
                                WHERE channel_id = ? AND workspace_member_id = ?
                            ");
                            $updateRoleStmt->execute([$channelId, $promotedMemberId]);
                            
                            // If channel was created_by the leaving user, also update channels table
                            if ((int)$channel['created_by'] === (int)$memberId) {
                                $updateChannelCreatedBy = $db->prepare("
                                    UPDATE channels 
                                    SET created_by = ? 
                                    WHERE id = ?
                                ");
                                $updateChannelCreatedBy->execute([$promotedMemberId, $channelId]);
                            }

                            $transferMsg = "transferred channel ownership to " . $candidateName;
                        }
                    } elseif ($memberRole === 'admin' && !$hasOtherOwner && !$hasOtherAdmin) {
                        // Need to transfer admin (role = 'admin')
                        $candidate = $memberCandidate;
                        if ($candidate) {
                            $promotedMemberId = $candidate['workspace_member_id'];
                            $candidateName = $candidate['first_name'] . ' ' . $candidate['last_name'];
                            
                            // Update role to admin
                            $updateRoleStmt = $db->prepare("
                                UPDATE channel_members 
                                SET role = 'admin' 
                                WHERE channel_id = ? AND workspace_member_id = ?
                            ");
                            $updateRoleStmt->execute([$channelId, $promotedMemberId]);
                            
                            $transferMsg = "promoted " . $candidateName . " to Admin";
                        }
                    }
                }
            }

            // Delete from channel_members
            $stmt = $db->prepare("DELETE FROM channel_members WHERE channel_id = ? AND workspace_member_id = ?");
            $stmt->execute([$channelId, $memberId]);

            // Delete from conversation_participants
            $stmt = $db->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND workspace_member_id = ?");
            $stmt->execute([$channel['conversation_id'], $memberId]);

            // MED-09: Sync member_count from real count to prevent drift
            $stmt = $db->prepare("
                UPDATE channels
                SET member_count = (SELECT COUNT(*) FROM channel_members WHERE channel_id = ? AND left_at IS NULL)
                WHERE id = ?
            ");
            $stmt->execute([$channelId, $channelId]);

            $systemMessages = [];

            // 1. Leave message
            $stmt = $db->prepare("
                INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
                VALUES (?, ?, ?, ?, 'system')
            ");
            $stmt->execute([$workspaceId, $channel['conversation_id'], $memberId, SystemMessage::bodyMemberLeft()]);
            $leaveMsgId = $db->lastInsertId();
            
            $formattedLeave = $this->getFormattedMessage((int)$leaveMsgId);
            if ($formattedLeave) {
                $systemMessages[] = $formattedLeave;
            }

            // 2. Transfer role message
            if ($transferMsg) {
                $stmt = $db->prepare("
                    INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
                    VALUES (?, ?, ?, ?, 'system')
                ");
                $stmt->execute([$workspaceId, $channel['conversation_id'], $memberId, $transferMsg]);
                $transferMsgId = $db->lastInsertId();

                $formattedTransfer = $this->getFormattedMessage((int)$transferMsgId);
                if ($formattedTransfer) {
                    $systemMessages[] = $formattedTransfer;
                }
            }

            $db->commit();
            \App\Helpers\Cache::invalidateConversationDashboardCache((int)$channel['conversation_id'], $workspaceId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Left channel successfully',
                'conversation_id' => (int)$channel['conversation_id'],
                'system_messages' => $systemMessages
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            \App\Core\ErrorHandler::logError($e);
            $msg = APP_DEBUG ? 'Failed to leave channel: ' . $e->getMessage() : 'Failed to leave channel due to an internal server error.';
            $this->jsonResponse(['error' => $msg], 500);
        }
    }

    private function getFormattedMessage(int $messageId): ?array
    {
        $db = Model::db();
        $stmt = $db->prepare("
            SELECT m.*, 
                   u.first_name, u.last_name, u.avatar_path, u.username AS sender_username,
                   CONCAT(u.first_name, ' ', u.last_name) AS sender_name
            FROM messages m
            JOIN workspace_members wm ON wm.id = m.sender_id
            JOIN users u ON u.id = wm.user_id
            WHERE m.id = ?
        ");
        $stmt->execute([$messageId]);
        $msgDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$msgDetails) return null;

        return [
            'id' => (int)$msgDetails['id'],
            'conversation_id' => (int)$msgDetails['conversation_id'],
            'conversation_type' => 'channel',
            'channel_id' => $msgDetails['channel_id'] ?? null,
            'sender_id' => (int)$msgDetails['sender_id'],
            'sender_name' => $msgDetails['sender_name'],
            'sender_avatar' => $msgDetails['avatar_path'],
            'sender_username' => $msgDetails['sender_username'],
            'body' => $msgDetails['body'],
            'message_type' => $msgDetails['message_type'],
            'reply_to_id' => null,
            'created_at' => $msgDetails['created_at'],
            'time_label' => date('h:i A', strtotime($msgDetails['created_at'])),
            'attachments' => [],
            'read_status' => 'sent'
        ];
    }

    public function update(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;
        $displayName = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
        $role = $user['role'] ?? 'member';

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $channelId = (int)($input['channel_id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $members = $input['members'] ?? [];

        if ($channelId <= 0) {
            $this->jsonResponse(['error' => 'Channel ID is required'], 400);
        }

        $db = Model::db();

        // 1. Fetch channel and check existence
        $stmt = $db->prepare("SELECT * FROM channels WHERE id = ? AND workspace_id = ?");
        $stmt->execute([$channelId, $workspaceId]);
        $channel = $stmt->fetch();
        if (!$channel) {
            $this->jsonResponse(['error' => 'Channel not found'], 404);
        }

        // 2. Permission check: Only channel owner/admin
        $roleStmt = $db->prepare("
            SELECT role FROM channel_members
            WHERE channel_id = ? AND workspace_member_id = ? AND left_at IS NULL
        ");
        $roleStmt->execute([$channelId, $memberId]);
        $channelRole = $roleStmt->fetchColumn() ?: 'member';

        $isChannelAdmin = in_array($channelRole, ['owner', 'admin'], true);

        if (!$isChannelAdmin) {
            $this->jsonResponse(['error' => 'You do not have permission to edit this channel.'], 403);
        }

        // Block editing default channels
        if ($channel['is_default']) {
            $this->jsonResponse(['error' => 'Default channels are not editable.'], 400);
        }

        // 3. Name validation and slug generation
        if (empty($name)) {
            $this->jsonResponse(['error' => 'Channel name is required'], 400);
        }
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', strtolower($name)));
        $cleanName = trim($cleanName, '-');
        if (empty($cleanName)) {
            $this->jsonResponse(['error' => 'Invalid channel name'], 400);
        }

        $newFormerSlugs = $channel['former_slugs'];
        if ($cleanName !== $channel['slug']) {
            // Check slug collision
            $stmt = $db->prepare("SELECT id FROM channels WHERE workspace_id = ? AND slug = ? AND id != ?");
            $stmt->execute([$workspaceId, $cleanName, $channelId]);
            if ($stmt->fetch()) {
                $this->jsonResponse(['error' => 'A channel with this name already exists'], 400);
            }

            // Append old slug to former_slugs list
            $oldSlug = $channel['slug'];
            $formerSlugsList = array_filter(explode(',', $channel['former_slugs'] ?? ''));
            if (!in_array($oldSlug, $formerSlugsList, true)) {
                $formerSlugsList[] = $oldSlug;
            }
            $newFormerSlugs = implode(',', $formerSlugsList);
        }

        // Fetch conversation ID
        $stmt = $db->prepare("SELECT id FROM conversations WHERE channel_id = ?");
        $stmt->execute([$channelId]);
        $conversationId = (int)$stmt->fetchColumn();

        $db->beginTransaction();
        try {
            // 4. Update channels table
            $stmt = $db->prepare("
                UPDATE channels 
                SET name = ?, slug = ?, description = ?, former_slugs = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $cleanName, $description, $newFormerSlugs, $channelId]);

            // 5. Reconcile members
            $creatorId = (int)$channel['created_by'];
            
            // Map members input to list of ints
            $members = array_map('intval', $members);
            
            // Ensure creator is always kept
            if (!in_array($creatorId, $members, true)) {
                $members[] = $creatorId;
            }

            // Get current members
            $stmt = $db->prepare("SELECT workspace_member_id FROM channel_members WHERE channel_id = ? AND left_at IS NULL");
            $stmt->execute([$channelId]);
            $currentMembers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $currentMembers = array_map('intval', $currentMembers);

            // Members to add
            $toAdd = [];
            foreach ($members as $m) {
                if ($m > 0 && !in_array($m, $currentMembers, true)) {
                    $toAdd[] = $m;
                }
            }

            // Members to remove
            $toRemove = [];
            foreach ($currentMembers as $m) {
                if ($m !== $creatorId && !$channel['is_default'] && !in_array($m, $members, true)) {
                    $toRemove[] = $m;
                }
            }

            // Process removals
            if (!empty($toRemove)) {
                // Get display names of members to remove for system messages
                $inQuery = implode(',', array_fill(0, count($toRemove), '?'));
                $stmt = $db->prepare("
                    SELECT wm.id, u.first_name, u.last_name 
                    FROM workspace_members wm 
                    JOIN users u ON wm.user_id = u.id 
                    WHERE wm.id IN ($inQuery)
                ");
                $stmt->execute($toRemove);
                $removedUsers = $stmt->fetchAll();

                foreach ($removedUsers as $ru) {
                    $ruName = $ru['first_name'] . ' ' . $ru['last_name'];
                    
                    // Delete from channel_members
                    $stmt = $db->prepare("DELETE FROM channel_members WHERE channel_id = ? AND workspace_member_id = ?");
                    $stmt->execute([$channelId, $ru['id']]);

                    // Delete from conversation_participants
                    $stmt = $db->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND workspace_member_id = ?");
                    $stmt->execute([$conversationId, $ru['id']]);

                    // System message: removed from channel
                    $stmt = $db->prepare("
                        INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
                        VALUES (?, ?, ?, ?, 'system')
                    ");
                    $stmt->execute([$workspaceId, $conversationId, $memberId, SystemMessage::bodyMemberRemoved($ruName)]);
                }
            }

            // Process additions
            if (!empty($toAdd)) {
                // Get display names of members to add for system messages
                $inQuery = implode(',', array_fill(0, count($toAdd), '?'));
                $stmt = $db->prepare("
                    SELECT wm.id, u.first_name, u.last_name 
                    FROM workspace_members wm 
                    JOIN users u ON wm.user_id = u.id 
                    WHERE wm.id IN ($inQuery)
                ");
                $stmt->execute($toAdd);
                $addedUsers = $stmt->fetchAll();

                foreach ($addedUsers as $au) {
                    $auName = $au['first_name'] . ' ' . $au['last_name'];

                    // Clean old join if any
                    $stmt = $db->prepare("DELETE FROM channel_members WHERE channel_id = ? AND workspace_member_id = ?");
                    $stmt->execute([$channelId, $au['id']]);

                    // Insert to channel_members
                    $stmt = $db->prepare("
                        INSERT INTO channel_members (channel_id, workspace_member_id, role)
                        VALUES (?, ?, 'member')
                    ");
                    $stmt->execute([$channelId, $au['id']]);

                    // Clean old participant if any
                    $stmt = $db->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND workspace_member_id = ?");
                    $stmt->execute([$conversationId, $au['id']]);

                    // Insert to conversation_participants
                    $stmt = $db->prepare("
                        INSERT INTO conversation_participants (conversation_id, workspace_member_id)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$conversationId, $au['id']]);

                    // System message: added to channel
                    $stmt = $db->prepare("
                        INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
                        VALUES (?, ?, ?, ?, 'system')
                    ");
                    $stmt->execute([$workspaceId, $conversationId, $memberId, SystemMessage::bodyMemberAdded($auName)]);
                }
            }

            // 6. Log audit action
            $stmt = $db->prepare("
                INSERT INTO audit_logs (workspace_id, actor_member_id, actor_label, status, activity_type, message)
                VALUES (?, ?, ?, 'complete', 'OTHER', ?)
            ");
            $stmt->execute([
                $workspaceId,
                $memberId,
                $displayName,
                "Channel #{$name} settings/members updated"
            ]);

            $db->commit();
            \App\Helpers\Cache::invalidateConversationDashboardCache((int)$conversationId, $workspaceId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Channel updated successfully',
                'slug' => $cleanName
            ]);

        } catch (\Exception $e) {
            $db->rollBack();
            \App\Core\ErrorHandler::logError($e);
            $msg = APP_DEBUG ? 'Failed to update channel: ' . $e->getMessage() : 'Failed to update channel due to an internal server error.';
            $this->jsonResponse(['error' => $msg], 500);
        }
    }

    public function approveMemberRequest(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;
        $role = $user['role'] ?? 'member';

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $requestId = (int)($input['request_id'] ?? 0);

        if ($requestId <= 0) {
            $this->jsonResponse(['error' => 'Request ID is required'], 400);
        }

        $db = Model::db();

        // Get the request
        $request = ChannelJoinRequest::getRequest($requestId, $workspaceId);
        if (!$request) {
            $this->jsonResponse(['error' => 'Request not found'], 404);
        }

        if ($request['status'] !== 'pending') {
            $this->jsonResponse(['error' => 'Only pending requests can be approved'], 400);
        }

        // Check channel owner/admin permission
        $channelId = (int)$request['channel_id'];
        $stmt = $db->prepare("
            SELECT c.*, cm.role as user_role
            FROM channels c
            LEFT JOIN channel_members cm ON c.id = cm.channel_id AND cm.workspace_member_id = ?
            WHERE c.id = ? AND c.workspace_id = ?
        ");
        $stmt->execute([$memberId, $channelId, $workspaceId]);
        $channel = $stmt->fetch();

        if (!$channel) {
            $this->jsonResponse(['error' => 'Channel not found'], 404);
        }

        $isChannelAdmin = in_array($channel['user_role'], ['owner', 'admin'], true);
        $isWorkspaceAdmin = in_array($role, ['owner', 'admin'], true);

        if (!$isChannelAdmin && !$isWorkspaceAdmin) {
            $this->jsonResponse(['error' => 'You do not have permission to approve join requests for this channel.'], 403);
        }

        $requestingMemberId = (int)$request['workspace_member_id'];

        // Check if already a member
        $stmt = $db->prepare("SELECT id FROM channel_members WHERE channel_id = ? AND workspace_member_id = ? AND left_at IS NULL");
        $stmt->execute([$channelId, $requestingMemberId]);
        if ($stmt->fetch()) {
            $this->jsonResponse(['error' => 'User is already a member of this channel'], 400);
        }

        // Get conversation
        $stmt = $db->prepare("SELECT id FROM conversations WHERE channel_id = ?");
        $stmt->execute([$channelId]);
        $conversationId = (int)$stmt->fetchColumn();

        $db->beginTransaction();
        try {
            // Update request status
            $stmt = $db->prepare("UPDATE channel_join_requests SET status = 'accepted', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$requestId]);

            // Add to channel_members
            $stmt = $db->prepare("DELETE FROM channel_members WHERE channel_id = ? AND workspace_member_id = ?");
            $stmt->execute([$channelId, $requestingMemberId]);

            $stmt = $db->prepare("
                INSERT INTO channel_members (channel_id, workspace_member_id, role)
                VALUES (?, ?, 'member')
            ");
            $stmt->execute([$channelId, $requestingMemberId]);

            // Add to conversation_participants
            $stmt = $db->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND workspace_member_id = ?");
            $stmt->execute([$conversationId, $requestingMemberId]);

            $stmt = $db->prepare("
                INSERT INTO conversation_participants (conversation_id, workspace_member_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$conversationId, $requestingMemberId]);

            // MED-09: Sync member_count from real count to prevent drift
            $stmt = $db->prepare("
                UPDATE channels
                SET member_count = (SELECT COUNT(*) FROM channel_members WHERE channel_id = ? AND left_at IS NULL)
                WHERE id = ?
            ");
            $stmt->execute([$channelId, $channelId]);

            // System message: member approved
            $stmt = $db->prepare("
                INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
                VALUES (?, ?, ?, ?, 'system')
            ");
            $stmt->execute([$workspaceId, $conversationId, $requestingMemberId, SystemMessage::bodyMemberJoined()]);

            // Get requester name for audit log
            $stmt = $db->prepare("SELECT u.first_name, u.last_name FROM workspace_members wm JOIN users u ON wm.user_id = u.id WHERE wm.id = ?");
            $stmt->execute([$requestingMemberId]);
            $requesterData = $stmt->fetch();
            $requesterName = ($requesterData['first_name'] ?? '') . ' ' . ($requesterData['last_name'] ?? '');

            // Log to audit trail
            AuditLog::log(
                $workspaceId,
                $memberId,
                $user['first_name'] . ' ' . $user['last_name'],
                'OTHER',
                "Approved join request from {$requesterName} for channel #{$channel['name']}"
            );

            // Create notification
            $stmt = $db->prepare("
                INSERT INTO notifications (workspace_id, recipient_id, type, actor_id, title, body, reference_type, reference_id)
                VALUES (?, ?, 'channel_join', ?, ?, ?, 'channel', ?)
            ");
            $stmt->execute([
                $workspaceId,
                $requestingMemberId,
                $memberId,
                'Join request approved',
                'Your request to join #' . htmlspecialchars($channel['name']) . ' has been approved.',
                $channelId
            ]);

            // Update the "Join request received" notifications to show as approved
            $adminName = $user['first_name'] . ' ' . $user['last_name'];
            $stmt = $db->prepare("
                UPDATE notifications 
                SET title = 'Join request approved',
                    body = ?
                WHERE workspace_id = ? 
                  AND type = 'channel_join' 
                  AND actor_id = ? 
                  AND reference_type = 'channel' 
                  AND reference_id = ?
                  AND title = 'Join request received'
            ");
            $stmt->execute([
                "{$adminName} approved {$requesterName}'s request to join #" . $channel['name'] . ".",
                $workspaceId,
                $requestingMemberId,
                $channelId
            ]);

            $db->commit();
            \App\Helpers\Cache::invalidateConversationDashboardCache((int)$conversationId, $workspaceId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Join request approved successfully',
                'recipient_id' => $requestingMemberId,
                'channel_name' => $channel['name'],
                'channel_id' => $channelId,
                'channel_slug' => $channel['slug']
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            \App\Core\ErrorHandler::logError($e);
            $msg = APP_DEBUG ? 'Failed to approve request: ' . $e->getMessage() : 'Failed to approve request due to an internal server error.';
            $this->jsonResponse(['error' => $msg], 500);
        }
    }

    public function rejectMemberRequest(): void
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;
        $role = $user['role'] ?? 'member';

        if ($workspaceId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $requestId = (int)($input['request_id'] ?? 0);

        if ($requestId <= 0) {
            $this->jsonResponse(['error' => 'Request ID is required'], 400);
        }

        $db = Model::db();

        // Get the request
        $request = ChannelJoinRequest::getRequest($requestId, $workspaceId);
        if (!$request) {
            $this->jsonResponse(['error' => 'Request not found'], 404);
        }

        if ($request['status'] !== 'pending') {
            $this->jsonResponse(['error' => 'Only pending requests can be rejected'], 400);
        }

        // Check channel owner/admin permission
        $channelId = (int)$request['channel_id'];
        $stmt = $db->prepare("
            SELECT c.*, cm.role as user_role
            FROM channels c
            LEFT JOIN channel_members cm ON c.id = cm.channel_id AND cm.workspace_member_id = ?
            WHERE c.id = ? AND c.workspace_id = ?
        ");
        $stmt->execute([$memberId, $channelId, $workspaceId]);
        $channel = $stmt->fetch();

        if (!$channel) {
            $this->jsonResponse(['error' => 'Channel not found'], 404);
        }

        $isChannelAdmin = in_array($channel['user_role'], ['owner', 'admin'], true);
        $isWorkspaceAdmin = in_array($role, ['owner', 'admin'], true);

        if (!$isChannelAdmin && !$isWorkspaceAdmin) {
            $this->jsonResponse(['error' => 'You do not have permission to reject join requests for this channel.'], 403);
        }

        try {
            $stmt = $db->prepare("UPDATE channel_join_requests SET status = 'rejected', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$requestId]);

            // Get requester name for audit log
            $requestingMemberId = (int)$request['workspace_member_id'];
            $stmt = $db->prepare("SELECT u.first_name, u.last_name FROM workspace_members wm JOIN users u ON wm.user_id = u.id WHERE wm.id = ?");
            $stmt->execute([$requestingMemberId]);
            $requesterData = $stmt->fetch();
            $requesterName = ($requesterData['first_name'] ?? '') . ' ' . ($requesterData['last_name'] ?? '');

            // Log to audit trail
            AuditLog::log(
                $workspaceId,
                $memberId,
                $user['first_name'] . ' ' . $user['last_name'],
                'OTHER',
                "Rejected join request from {$requesterName} for channel #{$channel['name']}"
            );

            // Create notification
            $stmt = $db->prepare("
                INSERT INTO notifications (workspace_id, recipient_id, type, actor_id, title, body, reference_type, reference_id)
                VALUES (?, ?, 'channel_join', ?, ?, ?, 'channel', ?)
            ");
            $stmt->execute([
                $workspaceId,
                $requestingMemberId,
                $memberId,
                'Join request rejected',
                'Your request to join #' . htmlspecialchars($channel['name']) . ' has been rejected.',
                $channelId
            ]);

            // Update the "Join request received" notifications to show as rejected
            $adminName = $user['first_name'] . ' ' . $user['last_name'];
            $stmt = $db->prepare("
                UPDATE notifications 
                SET title = 'Join request rejected',
                    body = ?
                WHERE workspace_id = ? 
                  AND type = 'channel_join' 
                  AND actor_id = ? 
                  AND reference_type = 'channel' 
                  AND reference_id = ?
                  AND title = 'Join request received'
            ");
            $stmt->execute([
                "{$adminName} rejected {$requesterName}'s request to join #" . $channel['name'] . ".",
                $workspaceId,
                $requestingMemberId,
                $channelId
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Join request rejected successfully',
                'recipient_id' => $requestingMemberId,
                'channel_name' => $channel['name'],
                'channel_id' => $channelId
            ]);
        } catch (\Exception $e) {
            \App\Core\ErrorHandler::logError($e);
            $msg = APP_DEBUG ? 'Failed to reject request: ' . $e->getMessage() : 'Failed to reject request due to an internal server error.';
            $this->jsonResponse(['error' => $msg], 500);
        }
    }
}
