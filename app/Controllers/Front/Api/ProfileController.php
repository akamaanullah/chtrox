<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Model;
use App\Helpers\FileUploadPolicy;

class ProfileController extends Controller
{
    public function updateTheme(): void
    {
        $user = Session::user();
        $userId = $user['id'] ?? 0;

        if ($userId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Get POST data
        $input = $this->getRequestInput();
        $theme = $input['theme'] ?? '';

        $allowedThemes = [
            'indigo', 'blue', 'violet', 'emerald', 'rose', 'sky',
            'teal', 'amber', 'cyan', 'fuchsia', 'lime', 'orange'
        ];
        if (!in_array($theme, $allowedThemes, true)) {
            $this->jsonResponse(['error' => 'Invalid theme choice'], 400);
        }

        $db = Model::db();
        $stmt = $db->prepare("
            UPDATE user_preferences 
            SET theme_color = ? 
            WHERE user_id = ?
        ");
        $stmt->execute([$theme, $userId]);

        // Update active session values
        $_SESSION['chatrox_user']['theme_color'] = $theme;

        $this->jsonResponse([
            'success' => true,
            'theme' => $theme
        ]);
    }

    public function update(): void
    {
        $user = Session::user();
        $userId = $user['id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($userId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Get POST input
        $input = $this->getRequestInput();

        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $bio = trim($input['bio'] ?? '');
        $jobTitle = trim($input['job_title'] ?? '');

        if (empty($firstName) || empty($lastName)) {
            $this->jsonResponse(['error' => 'First name and last name are required'], 400);
        }

        $db = Model::db();
        $db->beginTransaction();

        try {
            // Update users table
            $stmt = $db->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, phone = ?, bio = ? 
                WHERE id = ?
            ");
            $stmt->execute([$firstName, $lastName, $phone, $bio, $userId]);

            // Update workspace_members job title
            $stmt = $db->prepare("
                UPDATE workspace_members 
                SET job_title = ? 
                WHERE id = ?
            ");
            $stmt->execute([$jobTitle, $memberId]);

            $db->commit();

            // Sync session
            $_SESSION['chatrox_user']['first_name'] = $firstName;
            $_SESSION['chatrox_user']['last_name'] = $lastName;
            $_SESSION['chatrox_user']['job_title'] = $jobTitle;

            $this->jsonResponse([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'job_title' => $jobTitle
                ]
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            \App\Core\ErrorHandler::logError($e);
            $this->jsonResponse(['error' => 'Failed to update profile. Please try again.'], 500);
        }
    }

    public function uploadAvatar(): void
    {
        $user = Session::user();
        $userId = $user['id'] ?? 0;

        if ($userId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        if (empty($_FILES['avatar'])) {
            $this->jsonResponse(['error' => 'No avatar file uploaded'], 400);
        }

        $file = $_FILES['avatar'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'Failed to upload file'], 400);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($ext, $allowedExtensions, true)) {
            $this->jsonResponse(['error' => 'Invalid image format. Supported: JPG, PNG, GIF, WEBP'], 400);
        }

        // Server-side MIME validation to prevent disguised uploads
        $detectedMime = FileUploadPolicy::detectMime($file['tmp_name']);
        if (!in_array($detectedMime, $allowedMimes, true)) {
            $this->jsonResponse(['error' => 'File content does not match a valid image type.'], 400);
        }

        $avatarDir = ROOT_DIR . '/public/uploads/avatars';
        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0755, true);
        }

        $filename = md5(uniqid('', true)) . '.' . $ext;
        $destination = $avatarDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $avatarUrl = 'uploads/avatars/' . $filename;
            
            $db = Model::db();
            $stmt = $db->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
            $stmt->execute([$avatarUrl, $userId]);
            
            $_SESSION['chatrox_user']['avatar_path'] = $avatarUrl;

            $this->jsonResponse([
                'success' => true,
                'avatar_path' => BASE_URL . '/' . $avatarUrl
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to save avatar image'], 500);
        }
    }
}
