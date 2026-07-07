<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;
use App\Services\ChannelService;
use App\Models\AuditLog;
use App\Core\Session;
use App\Core\Database;
use Exception;
use PDO;

class ChannelsController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('channels', [
            'page_title' => 'Channels - ChatRox',
            'channels' => AdminOverview::channels(),
            'members' => AdminOverview::members(),
        ]);
    }

    public function create(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);
        $adminMemberId = (int)($admin['workspace_member_id'] ?? 0);
        $adminName = ($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '');

        if ($workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $name = trim((string)($input['channel_name'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $visibility = trim((string)($input['visibility'] ?? 'public'));
        $addAll = !empty($input['add_all_members']);
        $members = $input['members'] ?? [];

        try {
            $channelService = new ChannelService();
            $channelData = $channelService->create(
                $workspaceId,
                $adminMemberId,
                $adminName,
                $name,
                $description,
                $visibility,
                $addAll,
                $members
            );

            // Log activity audit trail
            AuditLog::log(
                $workspaceId,
                $adminMemberId,
                $adminName,
                'channel_create',
                "Created channel #{$name} ({$visibility})"
            );

            $this->jsonResponse([
                'success' => true,
                'channel' => $channelData
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function edit(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);
        $adminMemberId = (int)($admin['workspace_member_id'] ?? 0);
        $adminName = ($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '');

        if ($workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $channelId = (int)($input['id'] ?? 0);
        $name = trim((string)($input['channel_name'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $visibility = trim((string)($input['visibility'] ?? 'public'));
        $addAll = !empty($input['add_all_members']);
        $membersInput = $input['members'] ?? [];

        if ($channelId === 0 || $name === '') {
            $this->jsonResponse(['error' => 'Channel ID and Name are required.'], 400);
        }

        $db = Database::connection();

        // Find channel
        $stmt = $db->prepare("SELECT * FROM channels WHERE id = ? AND workspace_id = ?");
        $stmt->execute([$channelId, $workspaceId]);
        $channel = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$channel) {
            $this->jsonResponse(['error' => 'Channel not found.'], 404);
        }

        // Check if name conflict exists
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $slugBase = ($transliterated !== false && $transliterated !== '') ? $transliterated : $name;
        $cleanSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', strtolower($slugBase)));
        $cleanSlug = preg_replace('/-+/', '-', $cleanSlug);
        $cleanSlug = trim($cleanSlug, '-');

        $stmt = $db->prepare("SELECT id FROM channels WHERE workspace_id = ? AND slug = ? AND id != ?");
        $stmt->execute([$workspaceId, $cleanSlug, $channelId]);
        if ($stmt->fetch()) {
            $this->jsonResponse(['error' => 'A channel with this name already exists.'], 400);
        }

        $db->beginTransaction();
        try {
            // Update channel
            $upd = $db->prepare("UPDATE channels SET name = ?, slug = ?, description = ?, visibility = ? WHERE id = ?");
            $upd->execute([$name, $cleanSlug, $description, $visibility, $channelId]);

            // Find channel conversation
            $stmt = $db->prepare("SELECT id FROM conversations WHERE channel_id = ?");
            $stmt->execute([$channelId]);
            $conversationId = (int)$stmt->fetchColumn();

            // Calculate target members list
            if ($addAll) {
                $stmt = $db->prepare("SELECT id FROM workspace_members WHERE workspace_id = ? AND status = 'active'");
                $stmt->execute([$workspaceId]);
                $targetMembers = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            } else {
                $targetMembers = [];
                // Creator/admin must remain in the channel
                $targetMembers[] = $adminMemberId;
                foreach ($membersInput as $mId) {
                    $mId = (int)$mId;
                    if ($mId > 0 && !in_array($mId, $targetMembers)) {
                        $targetMembers[] = $mId;
                    }
                }
            }

            // Sync channel memberships
            // 1. Remove members no longer in the list (actually delete or mark left_at)
            // Let's delete for simplicity or set left_at = NOW()
            $currentMembersQuery = $db->prepare("SELECT workspace_member_id FROM channel_members WHERE channel_id = ? AND left_at IS NULL");
            $currentMembersQuery->execute([$channelId]);
            $currentMembers = $currentMembersQuery->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $membersToRemove = array_diff($currentMembers, $targetMembers);
            if (!empty($membersToRemove)) {
                $removePlaceholders = implode(',', array_fill(0, count($membersToRemove), '?'));
                // Delete from channel_members
                $delMem = $db->prepare("DELETE FROM channel_members WHERE channel_id = ? AND workspace_member_id IN ($removePlaceholders)");
                $delMem->execute(array_merge([$channelId], $membersToRemove));

                // Delete from conversation_participants
                $delPart = $db->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND workspace_member_id IN ($removePlaceholders)");
                $delPart->execute(array_merge([$conversationId], $membersToRemove));
            }

            // 2. Add new members
            $membersToAdd = array_diff($targetMembers, $currentMembers);
            foreach ($membersToAdd as $mId) {
                // Add to channel_members (role: member)
                $addMem = $db->prepare("INSERT INTO channel_members (channel_id, workspace_member_id, role) VALUES (?, ?, 'member')");
                $addMem->execute([$channelId, $mId]);

                // Add to conversation_participants
                $addPart = $db->prepare("INSERT INTO conversation_participants (conversation_id, workspace_member_id) VALUES (?, ?)");
                $addPart->execute([$conversationId, $mId]);
            }

            // Update member count
            $updCount = $db->prepare("UPDATE channels SET member_count = (SELECT COUNT(*) FROM channel_members WHERE channel_id = ? AND left_at IS NULL) WHERE id = ?");
            $updCount->execute([$channelId, $channelId]);

            // Log activity
            AuditLog::log(
                $workspaceId,
                $adminMemberId,
                $adminName,
                'settings_update',
                "Updated details and membership for channel #{$name}"
            );

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Channel updated successfully.']);
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function delete(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);
        $adminMemberId = (int)($admin['workspace_member_id'] ?? 0);
        $adminName = ($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '');

        if ($workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $channelId = (int)($input['id'] ?? 0);

        if ($channelId === 0) {
            $this->jsonResponse(['error' => 'Channel ID is required.'], 400);
        }

        $db = Database::connection();

        // Find channel
        $stmt = $db->prepare("SELECT * FROM channels WHERE id = ? AND workspace_id = ?");
        $stmt->execute([$channelId, $workspaceId]);
        $channel = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$channel) {
            $this->jsonResponse(['error' => 'Channel not found.'], 404);
        }

        try {
            $db->beginTransaction();

            // Set status to archived or delete
            // Let's archive it (status = 'archived')
            $stmt = $db->prepare("UPDATE channels SET status = 'archived' WHERE id = ?");
            $stmt->execute([$channelId]);

            // Log activity
            AuditLog::log(
                $workspaceId,
                $adminMemberId,
                $adminName,
                'channel_archive',
                "Archived channel #{$channel['name']}"
            );

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Channel archived successfully.']);
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function members(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);
        if ($workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $channelId = (int)($_GET['id'] ?? 0);
        $db = Database::connection();
        $stmt = $db->prepare("
            SELECT u.first_name, u.last_name, u.avatar_path, wm.role
            FROM channel_members cm
            JOIN workspace_members wm ON cm.workspace_member_id = wm.id
            JOIN users u ON wm.user_id = u.id
            WHERE cm.channel_id = ? AND cm.left_at IS NULL AND wm.workspace_id = ?
            ORDER BY u.first_name ASC
        ");
        $stmt->execute([$channelId, $workspaceId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = [];
        foreach ($members as $m) {
            $formatted[] = [
                'name' => $m['first_name'] . ' ' . $m['last_name'],
                'role' => ucfirst($m['role']),
                'avatar' => \App\Core\View::avatar($m['avatar_path']),
            ];
        }

        $this->jsonResponse(['success' => true, 'members' => $formatted]);
    }
}
