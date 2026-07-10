<?php

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Core\Model;
use App\Core\Database;

class FeedbackController extends AdminController
{
    public function index(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            $this->redirect('/admin/login');
        }

        $db = Database::connection();

        // Fetch all feedbacks for this workspace, joined with sender info
        $stmt = $db->prepare("
            SELECT f.*, 
                   u.first_name, u.last_name, u.email, u.avatar_path,
                   CONCAT(u.first_name, ' ', u.last_name) AS user_name
            FROM feedbacks f
            JOIN users u ON u.id = f.user_id
            WHERE f.workspace_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$workspaceId]);
        $allFeedbacks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calculate counts
        $counts = [
            'all' => count($allFeedbacks),
            'bug' => 0,
            'feature' => 0,
            'usability' => 0,
            'feedback' => 0
        ];

        foreach ($allFeedbacks as $item) {
            $type = $item['type'];
            if (array_key_exists($type, $counts)) {
                $counts[$type]++;
            } else {
                $counts['feedback']++;
            }
        }

        $this->renderDashboard('feedback', [
            'page_title' => 'ChatRox Admin - Feedbacks & Reports',
            'feedbacks' => $allFeedbacks,
            'counts' => $counts
        ]);
    }

    public function delete(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = $this->getRequestInput();
        $feedbackId = (int)($input['id'] ?? 0);

        if ($feedbackId === 0) {
            $this->jsonResponse(['error' => 'Feedback ID is required.'], 400);
        }

        $db = Database::connection();

        // Find feedback first to check ownership and delete file attachment
        $stmt = $db->prepare("SELECT * FROM feedbacks WHERE id = ? AND workspace_id = ? LIMIT 1");
        $stmt->execute([$feedbackId, $workspaceId]);
        $feedback = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$feedback) {
            $this->jsonResponse(['error' => 'Feedback not found.'], 404);
        }

        try {
            $db->beginTransaction();

            // Delete from database
            $stmtDel = $db->prepare("DELETE FROM feedbacks WHERE id = ? AND workspace_id = ?");
            $stmtDel->execute([$feedbackId, $workspaceId]);

            // If there's a file attachment, delete from disk
            if (!empty($feedback['attachment_path'])) {
                $filePath = ROOT_DIR . '/public/' . $feedback['attachment_path'];
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Feedback report deleted successfully.'
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            \App\Core\ErrorHandler::logError($e);
            $this->jsonResponse(['error' => 'Failed to delete feedback. Please try again.'], 500);
        }
    }
}
