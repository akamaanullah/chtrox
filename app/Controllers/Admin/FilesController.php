<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;
use App\Models\AuditLog;
use App\Core\Session;
use App\Core\Database;
use PDO;

class FilesController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('files', [
            'page_title' => 'Files & Media - ChatRox',
            'files' => AdminOverview::files(),
        ]);
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
            $this->jsonResponse(['error' => 'File ID is required.'], 400);
        }

        $db = Database::connection();

        // Verify ownership/workspace
        $check = $db->prepare("SELECT original_name FROM files WHERE id = ? AND workspace_id = ? AND deleted_at IS NULL");
        $check->execute([$id, $workspaceId]);
        $file = $check->fetch(PDO::FETCH_ASSOC);
        if (!$file) {
            $this->jsonResponse(['error' => 'File not found.'], 404);
        }

        try {
            $stmt = $db->prepare("UPDATE files SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);

            AuditLog::log(
                $workspaceId,
                $adminMemberId,
                $adminName,
                'OTHER',
                "Deleted file: {$file['original_name']}"
            );

            $this->jsonResponse(['success' => true, 'message' => 'File deleted successfully.']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
}
