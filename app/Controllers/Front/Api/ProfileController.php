<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Model;

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

        $allowedThemes = ['indigo', 'rose', 'emerald', 'amber', 'slate'];
        if (!in_array($theme, $allowedThemes)) {
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
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
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
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $this->jsonResponse(['error' => 'Invalid image format. Supported: JPG, PNG, GIF, WEBP'], 400);
        }

        $avatarDir = ROOT_DIR . '/public/uploads/avatars';
        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0755, true);
        }

        $filename = md5(uniqid('', true)) . '.' . $ext;
        $destination = $avatarDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $avatarUrl = 'public/uploads/avatars/' . $filename;
            
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
