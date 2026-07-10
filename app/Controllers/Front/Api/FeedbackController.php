<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Model;
use App\Helpers\FileUploadPolicy;

class FeedbackController extends Controller
{
    public function submit(): void
    {
        $user = Session::user();
        $userId = $user['id'] ?? 0;
        $workspaceId = $user['workspace_id'] ?? 0;

        if ($userId === 0 || $workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Since it can contain file upload, we read $_POST and $_FILES
        $type = trim($_POST['type'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;

        if (empty($type) || empty($subject) || empty($message)) {
            $this->jsonResponse(['error' => 'Feedback type, subject, and message are required.'], 400);
        }

        $allowedTypes = ['bug', 'feature', 'feedback', 'usability'];
        if (!in_array($type, $allowedTypes, true)) {
            $this->jsonResponse(['error' => 'Invalid feedback type.'], 400);
        }

        $attachmentPath = null;
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];

            // Limit file size (5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                $this->jsonResponse(['error' => 'Attachment size must not exceed 5MB.'], 400);
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'log'];
            if (!in_array($ext, $allowedExtensions, true)) {
                $this->jsonResponse(['error' => 'Invalid attachment format. Supported: JPG, PNG, GIF, WEBP, PDF, TXT, LOG'], 400);
            }

            $feedbackDir = ROOT_DIR . '/public/uploads/feedbacks';
            if (!is_dir($feedbackDir)) {
                mkdir($feedbackDir, 0755, true);
            }

            $filename = md5(uniqid('', true)) . '.' . $ext;
            $destination = $feedbackDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $attachmentPath = 'uploads/feedbacks/' . $filename;
            } else {
                $this->jsonResponse(['error' => 'Failed to save attachment file.'], 500);
            }
        }

        try {
            $db = Model::db();
            $stmt = $db->prepare("
                INSERT INTO feedbacks (workspace_id, user_id, type, subject, message, rating, attachment_path, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $workspaceId,
                $userId,
                $type,
                $subject,
                $message,
                $rating,
                $attachmentPath
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Thank you! Your feedback has been submitted successfully.'
            ]);
        } catch (\Exception $e) {
            \App\Core\ErrorHandler::logError($e);
            $this->jsonResponse(['error' => 'Failed to submit feedback. Please try again.'], 500);
        }
    }
}
