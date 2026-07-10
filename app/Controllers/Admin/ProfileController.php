<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;
use App\Models\User;
use App\Core\Session;
use App\Core\Database;
use App\Models\AuditLog;
use PDO;

class ProfileController extends AdminController
{
    public function index(): void
    {
        $admin = Session::adminUser();
        $userId = (int)($admin['id'] ?? 0);
        $workspaceId = (int)($admin['workspace_id'] ?? 0);

        if ($userId === 0) {
            $this->redirect('/admin/login');
        }

        $db = Database::connection();

        // 1. Fetch User details
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profileUser) {
            $this->redirect('/admin/login');
        }

        // 2. Fetch Workspace Details
        $stmt = $db->prepare("SELECT * FROM workspaces WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$workspaceId]);
        $workspace = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Fetch Workspace Address
        $stmt = $db->prepare("SELECT * FROM workspace_addresses WHERE workspace_id = ?");
        $stmt->execute([$workspaceId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. Fetch User Preferences
        $stmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);

        // 5. Fetch User Security
        $stmt = $db->prepare("SELECT * FROM user_security WHERE user_id = ?");
        $stmt->execute([$userId]);
        $security = $stmt->fetch(PDO::FETCH_ASSOC);

        // 6. Fetch Workspace Member info
        $stmt = $db->prepare("SELECT * FROM workspace_members WHERE user_id = ? AND workspace_id = ? AND status = 'active'");
        $stmt->execute([$userId, $workspaceId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        // 7. Fetch Recent sessions
        $stmt = $db->prepare("
            SELECT ip_address, user_agent, created_at, last_seen_at 
            FROM user_sessions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->renderDashboard('profile', [
            'page_title' => 'Account & Profile - ChatRox',
            'stats' => AdminOverview::stats(),
            'profile_user' => $profileUser,
            'workspace' => $workspace,
            'address' => $address,
            'preferences' => $preferences,
            'security' => $security,
            'member' => $member,
            'sessions' => $sessions
        ]);
    }

    public function save(): void
    {
        $admin = Session::adminUser();
        $userId = (int)($admin['id'] ?? 0);
        $workspaceId = (int)($admin['workspace_id'] ?? 0);
        $memberId = (int)($admin['workspace_member_id'] ?? 0);

        if ($userId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $db = Database::connection();

        // Retrieve input fields
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['personal_email'] ?? ''));
        $phone = trim((string)($_POST['personal_phone'] ?? ''));

        $companyName = trim((string)($_POST['company_name'] ?? ''));
        $industry = trim((string)($_POST['industry'] ?? ''));
        $orgType = trim((string)($_POST['org_type'] ?? ''));
        $companyEmail = trim((string)($_POST['company_email'] ?? ''));
        $companyPhone = trim((string)($_POST['company_phone'] ?? ''));

        $addressLine1 = trim((string)($_POST['address'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $state = trim((string)($_POST['state'] ?? ''));
        $country = trim((string)($_POST['country'] ?? ''));
        $zipCode = trim((string)($_POST['zip_code'] ?? ''));

        $themeColor = trim((string)($_POST['theme_color'] ?? 'indigo'));
        $twoFactorEnabled = isset($_POST['two_factor_enabled']) && $_POST['two_factor_enabled'] === '1' ? 1 : 0;

        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        // Basic validation
        if (empty($firstName) || empty($lastName) || empty($username) || empty($email)) {
            $this->jsonResponse(['error' => 'First name, last name, username, and email are required.'], 400);
        }

        // Email / Username duplicate checks
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetchColumn()) {
            $this->jsonResponse(['error' => 'Email is already in use by another account.'], 400);
        }

        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ? AND deleted_at IS NULL");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetchColumn()) {
            $this->jsonResponse(['error' => 'Username is already taken.'], 400);
        }

        // Password matching
        $passwordHash = null;
        if (!empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                $this->jsonResponse(['error' => 'Passwords do not match.'], 400);
            }
            if (strlen($newPassword) < 6) {
                $this->jsonResponse(['error' => 'Password must be at least 6 characters.'], 400);
            }
            $passwordHash = User::hashPassword($newPassword);
        }

        $db->beginTransaction();
        try {
            // 1. Update users table
            if ($passwordHash) {
                $stmt = $db->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, username = ?, email = ?, phone = ?, password_hash = ?
                    WHERE id = ?
                ");
                $stmt->execute([$firstName, $lastName, $username, $email, $phone, $passwordHash, $userId]);
            } else {
                $stmt = $db->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, username = ?, email = ?, phone = ?
                    WHERE id = ?
                ");
                $stmt->execute([$firstName, $lastName, $username, $email, $phone, $userId]);
            }

            // 2. Update workspaces table
            $stmt = $db->prepare("
                UPDATE workspaces 
                SET name = ?, industry = ?, organization_type = ?, email = ?, phone = ?
                WHERE id = ?
            ");
            $stmt->execute([$companyName, $industry, $orgType, $companyEmail, $companyPhone, $workspaceId]);

            // 3. Update or Insert workspace_addresses table
            $stmt = $db->prepare("SELECT 1 FROM workspace_addresses WHERE workspace_id = ?");
            $stmt->execute([$workspaceId]);
            if ($stmt->fetchColumn()) {
                $stmt = $db->prepare("
                    UPDATE workspace_addresses 
                    SET address_line1 = ?, city = ?, state = ?, country = ?, postal_code = ?
                    WHERE workspace_id = ?
                ");
                $stmt->execute([$addressLine1, $city, $state, $country, $zipCode, $workspaceId]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO workspace_addresses (workspace_id, address_line1, city, state, country, postal_code)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$workspaceId, $addressLine1, $city, $state, $country, $zipCode]);
            }

            // 4. Update user_preferences table
            $stmt = $db->prepare("
                UPDATE user_preferences 
                SET theme_color = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([$themeColor, $userId]);

            // 5. Update user_security table
            $stmt = $db->prepare("
                UPDATE user_security 
                SET two_factor_enabled = ?, password_changed_at = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $twoFactorEnabled, 
                $passwordHash ? date('Y-m-d H:i:s') : null, 
                $userId
            ]);

            // 6. Handle Avatar File Upload
            $avatarPath = null;
            if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                    $detectedMime = \App\Helpers\FileUploadPolicy::detectMime($file['tmp_name']);
                    if (in_array($detectedMime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                        $avatarDir = ROOT_DIR . '/public/uploads/avatars';
                        if (!is_dir($avatarDir)) {
                            mkdir($avatarDir, 0755, true);
                        }
                        $filename = md5(uniqid('', true)) . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $avatarDir . '/' . $filename)) {
                            $avatarPath = 'uploads/avatars/' . $filename;
                            
                            $stmt = $db->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
                            $stmt->execute([$avatarPath, $userId]);
                        }
                    }
                }
            }

            $db->commit();

            // Sync values in the active session
            $_SESSION['chatrox_admin']['first_name'] = $firstName;
            $_SESSION['chatrox_admin']['last_name'] = $lastName;
            $_SESSION['chatrox_admin']['username'] = $username;
            $_SESSION['chatrox_admin']['email'] = $email;
            if ($avatarPath) {
                $_SESSION['chatrox_admin']['avatar_path'] = $avatarPath;
            }

            // Log activity
            AuditLog::log(
                $workspaceId,
                $memberId,
                $firstName . ' ' . $lastName,
                'settings_update',
                'Updated profile details'
            );

            $this->jsonResponse(['success' => true, 'message' => 'Profile updated successfully.']);
        } catch (\Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
}
