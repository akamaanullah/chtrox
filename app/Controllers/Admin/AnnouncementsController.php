<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;
use App\Models\AuditLog;
use App\Core\Session;
use App\Core\Database;
use PDO;

class AnnouncementsController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('announcements', [
            'page_title' => 'Announcements - ChatRox',
            'announcements' => AdminOverview::announcements(),
        ]);
    }

    public function add(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);
        $adminMemberId = (int)($admin['workspace_member_id'] ?? 0);
        $adminName = ($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '');

        if ($workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $title = trim((string)($input['title'] ?? ''));
        $tag = trim((string)($input['tag'] ?? 'UPDATE'));
        $message = trim((string)($input['message'] ?? ''));
        $startDate = trim((string)($input['start_date'] ?? ''));
        $endDate = trim((string)($input['end_date'] ?? ''));

        if ($title === '' || $message === '' || $startDate === '' || $endDate === '') {
            $this->jsonResponse(['error' => 'All fields are required.'], 400);
        }

        $db = Database::connection();

        try {
            $stmt = $db->prepare("
                INSERT INTO announcements (workspace_id, created_by, title, tag, message, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $workspaceId,
                $adminMemberId,
                $title,
                $tag,
                $message,
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);

            $announcementId = (int)$db->lastInsertId();

            AuditLog::log(
                $workspaceId,
                $adminMemberId,
                $adminName,
                'OTHER',
                "Published new announcement: {$title}"
            );

            // Fetch all active workspace members to notify them
            $memberStmt = $db->prepare("
                SELECT id 
                FROM workspace_members 
                WHERE workspace_id = ? 
                  AND status = 'active'
            ");
            $memberStmt->execute([$workspaceId]);
            $recipientIds = $memberStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($recipientIds)) {
                $notifStmt = $db->prepare("
                    INSERT INTO notifications (workspace_id, recipient_id, type, actor_id, title, body, body_html, reference_type, reference_id)
                    VALUES (?, ?, 'system', ?, ?, ?, ?, 'announcement', ?)
                ");
                
                $notifTitle = 'New Announcement';
                $notifBody = 'Posted a new announcement: "' . $title . '"';
                $notifBodyHtml = 'Posted a new announcement: <span class="text-primary font-bold">' . htmlspecialchars($title) . '</span>';

                foreach ($recipientIds as $recipientId) {
                    $notifStmt->execute([
                        $workspaceId,
                        (int)$recipientId,
                        $adminMemberId,
                        $notifTitle,
                        $notifBody,
                        $notifBodyHtml,
                        $announcementId
                    ]);

                    // Invalidate nav badges cache for this recipient
                    \App\Helpers\Cache::delete("nav_badges_{$recipientId}_{$workspaceId}");
                }
            }

            // Generate a WebSocket ticket for the admin so they can connect and broadcast
            $userId = (int)$admin['id'];
            $sessionToken = $admin['session_token'] ?? '';
            $ticket = \App\Models\WebSocketTicket::createTicket($userId, $adminMemberId, $workspaceId, $sessionToken);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Announcement posted successfully.',
                'ticket' => $ticket,
                'workspace_id' => $workspaceId,
                'recipient_ids' => array_map('intval', $recipientIds),
                'announcement_id' => $announcementId,
                'announcement_title' => $title
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
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
        $id = (int)($input['id'] ?? 0);
        $title = trim((string)($input['title'] ?? ''));
        $tag = trim((string)($input['tag'] ?? 'UPDATE'));
        $message = trim((string)($input['message'] ?? ''));
        $startDate = trim((string)($input['start_date'] ?? ''));
        $endDate = trim((string)($input['end_date'] ?? ''));

        if ($id === 0 || $title === '' || $message === '' || $startDate === '' || $endDate === '') {
            $this->jsonResponse(['error' => 'All fields are required.'], 400);
        }

        $db = Database::connection();

        // Verify ownership/workspace
        $check = $db->prepare("SELECT id FROM announcements WHERE id = ? AND workspace_id = ?");
        $check->execute([$id, $workspaceId]);
        if (!$check->fetch()) {
            $this->jsonResponse(['error' => 'Announcement not found.'], 404);
        }

        try {
            $stmt = $db->prepare("
                UPDATE announcements 
                SET title = ?, tag = ?, message = ?, start_date = ?, end_date = ?
                WHERE id = ? AND workspace_id = ?
            ");
            $stmt->execute([
                $title,
                $tag,
                $message,
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
                $id,
                $workspaceId
            ]);

            AuditLog::log(
                $workspaceId,
                $adminMemberId,
                $adminName,
                'OTHER',
                "Modified announcement: {$title}"
            );

            $this->jsonResponse(['success' => true, 'message' => 'Announcement updated successfully.']);
        } catch (\Exception $e) {
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
        $id = (int)($input['id'] ?? 0);

        if ($id === 0) {
            $this->jsonResponse(['error' => 'Announcement ID is required.'], 400);
        }

        $db = Database::connection();

        // Verify ownership/workspace
        $check = $db->prepare("SELECT title FROM announcements WHERE id = ? AND workspace_id = ? AND deleted_at IS NULL");
        $check->execute([$id, $workspaceId]);
        $ann = $check->fetch(PDO::FETCH_ASSOC);
        if (!$ann) {
            $this->jsonResponse(['error' => 'Announcement not found.'], 404);
        }

        try {
            $stmt = $db->prepare("UPDATE announcements SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);

            AuditLog::log(
                $workspaceId,
                $adminMemberId,
                $adminName,
                'OTHER',
                "Removed announcement: {$ann['title']}"
            );

            $this->jsonResponse(['success' => true, 'message' => 'Announcement removed successfully.']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
}
