<?php

namespace App\Services;

use App\Core\Model;
use App\Helpers\SystemMessage;
use Exception;
use PDO;

class ChannelService
{
    /**
     * Create a new channel and set up its conversation context.
     *
     * @param int $workspaceId
     * @param int $memberId
     * @param string $displayName
     * @param string $name
     * @param string $description
     * @param string $visibility
     * @param bool $addAll
     * @param array $members
     * @return array The created channel details.
     * @throws Exception
     */
    public function create(
        int $workspaceId,
        int $memberId,
        string $displayName,
        string $name,
        string $description = '',
        string $visibility = 'public',
        bool $addAll = false,
        array $members = []
    ): array {
        if (empty($name)) {
            throw new Exception('Channel name is required', 400);
        }

        // Transliterate Unicode/accented characters to ASCII before slugifying
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $slugBase = ($transliterated !== false && $transliterated !== '') ? $transliterated : $name;
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', strtolower($slugBase)));
        $cleanName = preg_replace('/-+/', '-', $cleanName); // collapse multiple dashes
        $cleanName = trim($cleanName, '-');
        if (empty($cleanName)) {
            throw new Exception('Invalid channel name', 400);
        }

        $db = Model::db();

        // Check if name/slug already exists in this workspace
        $stmt = $db->prepare("SELECT id FROM channels WHERE workspace_id = ? AND slug = ?");
        $stmt->execute([$workspaceId, $cleanName]);
        if ($stmt->fetch()) {
            throw new Exception('A channel with this name already exists', 400);
        }

        $db->beginTransaction();
        try {
            // 1. Insert into channels
            $stmt = $db->prepare("
                INSERT INTO channels (workspace_id, slug, name, description, visibility, status, created_by, member_count)
                VALUES (?, ?, ?, ?, ?, 'active', ?, 1)
            ");
            $stmt->execute([$workspaceId, $cleanName, $name, $description, $visibility, $memberId]);
            $channelId = (int)$db->lastInsertId();

            // 2. Insert into conversations
            $stmt = $db->prepare("
                INSERT INTO conversations (workspace_id, type, channel_id)
                VALUES (?, 'channel', ?)
            ");
            $stmt->execute([$workspaceId, $channelId]);
            $conversationId = (int)$db->lastInsertId();

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

            // Process additions of other members
            if ($addAll) {
                // Fetch all active workspace members except the creator
                $stmt = $db->prepare("SELECT id FROM workspace_members WHERE workspace_id = ? AND status = 'active' AND id != ?");
                $stmt->execute([$workspaceId, $memberId]);
                $membersToAdd = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            } else {
                $membersToAdd = [];
                foreach ($members as $m) {
                    $m = (int)$m;
                    if ($m > 0 && $m !== $memberId) {
                        $membersToAdd[] = $m;
                    }
                }
            }

            foreach ($membersToAdd as $mId) {
                // Add to channel_members (role: member)
                $stmt = $db->prepare("
                    INSERT INTO channel_members (channel_id, workspace_member_id, role)
                    VALUES (?, ?, 'member')
                ");
                $stmt->execute([$channelId, $mId]);

                // Add to conversation_participants
                $stmt = $db->prepare("
                    INSERT INTO conversation_participants (conversation_id, workspace_member_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$conversationId, $mId]);
            }

            // Sync member_count from real channel_members row count to prevent drift (MED-09)
            $stmt = $db->prepare("
                UPDATE channels
                SET member_count = (
                    SELECT COUNT(*) FROM channel_members WHERE channel_id = ? AND left_at IS NULL
                )
                WHERE id = ?
            ");
            $stmt->execute([$channelId, $channelId]);

            // Insert system message about channel creation
            SystemMessage::post(
                $db,
                $workspaceId,
                $conversationId,
                'channel_created',
                $memberId,
                "created channel **#{$name}**"
            );

            // Fetch newly created channel information to return
            $stmtChan = $db->prepare("SELECT * FROM channels WHERE id = ?");
            $stmtChan->execute([$channelId]);
            $channel = $stmtChan->fetch(PDO::FETCH_ASSOC);

            $db->commit();

            return [
                'id' => (int)$channel['id'],
                'slug' => $channel['slug'],
                'name' => $channel['name'],
                'description' => $channel['description'],
                'visibility' => $channel['visibility'],
                'conversation_id' => $conversationId,
                'member_count' => (int)$channel['member_count'],
                'created_by' => (int)$channel['created_by']
            ];

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
