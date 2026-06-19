<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Model;
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

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $visibility = $input['visibility'] ?? 'public'; // public or private

        if (empty($name)) {
            $this->jsonResponse(['error' => 'Channel name is required'], 400);
        }

        // Clean name (lowercase, replace spaces/underscores with dashes)
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', strtolower($name)));
        $cleanName = trim($cleanName, '-');
        if (empty($cleanName)) {
            $this->jsonResponse(['error' => 'Invalid channel name'], 400);
        }

        $db = Model::db();

        // Check if name/slug already exists in this workspace
        $stmt = $db->prepare("SELECT id FROM channels WHERE workspace_id = ? AND slug = ?");
        $stmt->execute([$workspaceId, $cleanName]);
        if ($stmt->fetch()) {
            $this->jsonResponse(['error' => 'A channel with this name already exists'], 400);
        }

        $db->beginTransaction();
        try {
            // 1. Insert into channels
            $stmt = $db->prepare("
                INSERT INTO channels (workspace_id, slug, name, description, visibility, status, created_by, member_count)
                VALUES (?, ?, ?, ?, ?, 'active', ?, 1)
            ");
            $stmt->execute([$workspaceId, $cleanName, $name, $description, $visibility, $memberId]);
            $channelId = $db->lastInsertId();

            // 2. Insert into conversations
            $stmt = $db->prepare("
                INSERT INTO conversations (workspace_id, type, channel_id)
                VALUES (?, 'channel', ?)
            ");
            $stmt->execute([$workspaceId, $channelId]);
            $conversationId = $db->lastInsertId();

            // 3. Add to channel_members (role: owner)
            $stmt = $db->prepare("
                INSERT INTO channel_members (channel_id, workspace_member_id, role)
                VALUES (?, ?, 'owner')
            ");
            $stmt->execute([$channelId, $memberId]);

            // 4. Add to conversation_participants
            $stmt = $db->prepare("
                INSERT INTO conversation_participants (conversation_id, workspace_member_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$conversationId, $memberId]);

            // 5. Create starting system message
            $stmt = $db->prepare("
                INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
                VALUES (?, ?, ?, ?, 'system')
            ");
            $stmt->execute([$workspaceId, $conversationId, $memberId, "created channel #{$cleanName}"]);

            // 6. Log audit action
            $stmt = $db->prepare("
                INSERT INTO audit_logs (workspace_id, actor_member_id, actor_label, status, activity_type, message)
                VALUES (?, ?, ?, 'complete', 'channel_create', ?)
            ");
            $stmt->execute([
                $workspaceId,
                $memberId,
                $displayName,
                "Channel #{$name} created"
            ]);

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'channel' => [
                    'id' => $channelId,
                    'name' => '#' . $name,
                    'slug' => $cleanName,
                    'conversation_id' => $conversationId,
                    'description' => $description
                ]
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => 'Failed to create channel: ' . $e->getMessage()], 500);
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

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
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

        if ($channel['visibility'] === 'private') {
            $this->jsonResponse(['error' => 'Private channels cannot be joined directly'], 403);
        }

        // Check if already joined
        $stmt = $db->prepare("SELECT id FROM channel_members WHERE channel_id = ? AND workspace_member_id = ? AND left_at IS NULL");
        $stmt->execute([$channelId, $memberId]);
        if ($stmt->fetch()) {
            $this->jsonResponse(['success' => true, 'message' => 'Already joined', 'slug' => $channel['slug']]);
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

            // System join message
            $stmt = $db->prepare("
                INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
                VALUES (?, ?, ?, ?, 'system')
            ");
            $stmt->execute([$workspaceId, $channel['conversation_id'], $memberId, "joined the channel"]);

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Joined channel successfully',
                'slug' => $channel['slug']
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => 'Failed to join channel: ' . $e->getMessage()], 500);
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

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
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

        $db->beginTransaction();
        try {
            // Delete from channel_members
            $stmt = $db->prepare("DELETE FROM channel_members WHERE channel_id = ? AND workspace_member_id = ?");
            $stmt->execute([$channelId, $memberId]);

            // Delete from conversation_participants
            $stmt = $db->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND workspace_member_id = ?");
            $stmt->execute([$channel['conversation_id'], $memberId]);

            // Leave message
            $stmt = $db->prepare("
                INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
                VALUES (?, ?, ?, ?, 'system')
            ");
            $stmt->execute([$workspaceId, $channel['conversation_id'], $memberId, "left the channel"]);

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Left channel successfully'
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => 'Failed to leave channel: ' . $e->getMessage()], 500);
        }
    }
}
