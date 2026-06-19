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
        $userId = $user['user_id'] ?? 0;

        if ($userId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Get POST data
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
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
        $userId = $user['user_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($userId === 0 || $memberId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Get POST input
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

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
}
