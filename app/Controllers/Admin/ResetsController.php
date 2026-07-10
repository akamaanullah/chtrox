<?php

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Core\Database;
use App\Models\User;
use App\Models\AuditLog;
use PDO;

class ResetsController extends AdminController
{
    public function index(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            $this->redirect('/admin/login');
        }

        $db = Database::connection();

        // Fetch all pending password reset requests for this workspace
        $stmt = $db->prepare("
            SELECT prr.*, 
                   u.username, u.email, u.first_name, u.last_name,
                   CONCAT(u.first_name, ' ', u.last_name) AS user_name
            FROM password_reset_requests prr
            JOIN users u ON u.id = prr.user_id
            JOIN workspace_members wm ON wm.user_id = u.id
            WHERE wm.workspace_id = ? AND prr.status = 'pending'
            ORDER BY prr.created_at DESC
        ");
        $stmt->execute([$workspaceId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->renderDashboard('resets', [
            'page_title' => 'ChatRox Admin - Password Resets',
            'requests' => $requests
        ]);
    }

    public function process(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $requestId = (int)($input['request_id'] ?? 0);
        $newPassword = trim((string)($input['new_password'] ?? ''));
        $confirmPassword = trim((string)($input['confirm_password'] ?? ''));

        if ($requestId <= 0) {
            $this->jsonResponse(['error' => 'Invalid request ID.'], 400);
        }

        if ($newPassword === '') {
            $this->jsonResponse(['error' => 'Please enter a new password.'], 400);
        }

        if (strlen($newPassword) < 8) {
            $this->jsonResponse(['error' => 'Password must be at least 8 characters long.'], 400);
        }

        if ($newPassword !== $confirmPassword) {
            $this->jsonResponse(['error' => 'Passwords do not match.'], 400);
        }

        $db = Database::connection();

        // Verify request exists, is pending, and belongs to this workspace
        $stmt = $db->prepare("
            SELECT prr.*, u.id AS user_id, u.first_name, u.last_name, u.username
            FROM password_reset_requests prr
            JOIN users u ON u.id = prr.user_id
            JOIN workspace_members wm ON wm.user_id = u.id
            WHERE prr.id = ? AND wm.workspace_id = ? AND prr.status = 'pending'
        ");
        $stmt->execute([$requestId, $workspaceId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            $this->jsonResponse(['error' => 'Reset request not found or already processed.'], 404);
        }

        $userId = (int)$request['user_id'];
        $userFullName = $request['first_name'] . ' ' . $request['last_name'];

        try {
            $db->beginTransaction();

            // Update user password hash
            $passwordHash = User::hashPassword($newPassword);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$passwordHash, $userId]);

            // Update reset request status
            $stmt = $db->prepare("UPDATE password_reset_requests SET status = 'completed', resolved_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$requestId]);

            // Log activity
            AuditLog::log(
                $workspaceId,
                (int)($admin['workspace_member_id'] ?? 0),
                $admin['first_name'] . ' ' . $admin['last_name'],
                'password_change',
                "Administrator reset password for user: {$request['username']} ({$userFullName})"
            );

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Password reset successfully.']);

        } catch (\Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => 'Failed to process password reset: ' . $e->getMessage()], 500);
        }
    }
}
