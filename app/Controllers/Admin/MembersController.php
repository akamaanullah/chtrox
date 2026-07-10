<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;
use App\Models\User;
use App\Models\WorkspaceMember;
use App\Models\AuditLog;
use App\Core\Session;
use App\Core\Database;

class MembersController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('members', [
            'page_title' => 'Manage Members - ChatRox',
            'members' => AdminOverview::members(),
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
        $username = trim((string)($input['username'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $confirmPassword = (string)($input['confirmPassword'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            $this->jsonResponse(['error' => 'All fields are required.'], 400);
        }

        if ($password !== $confirmPassword) {
            $this->jsonResponse(['error' => 'Passwords do not match.'], 400);
        }

        if (strlen($password) < 8) {
            $this->jsonResponse(['error' => 'Password must be at least 8 characters long.'], 400);
        }

        $db = Database::connection();

        // Check if user already exists
        $user = User::findByUsername($username);
        if ($user) {
            $this->jsonResponse(['error' => 'Username is already taken.'], 400);
        }

        $user = User::findByEmail($email);
        if ($user) {
            $this->jsonResponse(['error' => 'Email is already registered.'], 400);
        }

        try {
            $db->beginTransaction();

            // Create User
            $passwordHash = User::hashPassword($password);
            
            // Format first and last name from username nicely
            $parts = explode('_', $username);
            $firstName = ucfirst($parts[0]);
            $lastName = isset($parts[1]) ? ucfirst($parts[1]) : 'Member';

            $userId = User::create([
                'email' => $email,
                'username' => $username,
                'password_hash' => $passwordHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);

            // Create User Security Record
            $secStmt = $db->prepare('INSERT INTO user_security (user_id) VALUES (?)');
            $secStmt->execute([$userId]);

            // Create Workspace Member
            $memberId = WorkspaceMember::create([
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
                'role' => 'member',
                'job_title' => 'Team Member',
                'status' => 'active'
            ]);

            // Log activity
            AuditLog::log(
                $workspaceId,
                $adminMemberId,
                $adminName,
                'member_invite',
                "Invited and created member: {$firstName} {$lastName} (@{$username})"
            );

            $db->commit();

            $this->jsonResponse(['success' => true, 'message' => 'Member added successfully.']);
        } catch (\Exception $e) {
            $db->rollBack();
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
        $memberId = (int)($input['id'] ?? 0);
        $fullName = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $role = strtolower(trim((string)($input['role'] ?? 'member')));
        $password = (string)($input['password'] ?? '');

        if ($memberId === 0 || $fullName === '' || $email === '') {
            $this->jsonResponse(['error' => 'All fields are required.'], 400);
        }

        // Find member
        $member = WorkspaceMember::findById($memberId);
        if (!$member || (int)$member['workspace_id'] !== $workspaceId) {
            $this->jsonResponse(['error' => 'Member not found.'], 404);
        }

        $userId = (int)$member['user_id'];
        $db = Database::connection();

        // Check if email already taken by someone else
        $emailCheck = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL');
        $emailCheck->execute([$email, $userId]);
        if ($emailCheck->fetch()) {
            $this->jsonResponse(['error' => 'Email is already in use by another account.'], 400);
        }

        try {
            $db->beginTransaction();

            // Split full name
            $parts = explode(' ', $fullName, 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';

            // Update user details
            $userStmt = $db->prepare('UPDATE users SET email = ?, first_name = ?, last_name = ? WHERE id = ?');
            $userStmt->execute([$email, $firstName, $lastName, $userId]);

            // Update workspace member role
            $memberStmt = $db->prepare('UPDATE workspace_members SET role = ? WHERE id = ?');
            $memberStmt->execute([$role, $memberId]);

            // Update password if provided
            if ($password !== '') {
                $passwordHash = User::hashPassword($password);
                $pwStmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $pwStmt->execute([$passwordHash, $userId]);

                // Update security timestamp
                $secStmt = $db->prepare('UPDATE user_security SET password_changed_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $secStmt->execute([$userId]);
            }

            // Log activity
            AuditLog::log(
                $workspaceId,
                $adminMemberId,
                $adminName,
                'role_change',
                "Updated details and role for member: {$fullName} to " . ucfirst($role)
            );

            $db->commit();

            $this->jsonResponse(['success' => true, 'message' => 'Member updated successfully.']);
        } catch (\Exception $e) {
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
        $memberId = (int)($input['id'] ?? 0);

        if ($memberId === 0) {
            $this->jsonResponse(['error' => 'Member ID is required.'], 400);
        }

        if ($memberId === $adminMemberId) {
            $this->jsonResponse(['error' => 'You cannot remove yourself from the workspace.'], 400);
        }

        // Find member
        $member = WorkspaceMember::findById($memberId);
        if (!$member || (int)$member['workspace_id'] !== $workspaceId) {
            $this->jsonResponse(['error' => 'Member not found.'], 404);
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            // Set member status to inactive
            $stmt = $db->prepare('UPDATE workspace_members SET status = "inactive" WHERE id = ?');
            $stmt->execute([$memberId]);

            // Set user presence to offline
            $stmt = $db->prepare('UPDATE user_presence SET status = "offline" WHERE user_id = ?');
            $stmt->execute([$member['user_id']]);

            // Fetch user details for log
            $user = User::findById((int)$member['user_id']);
            $fullName = $user ? ($user['first_name'] . ' ' . $user['last_name']) : 'Unknown User';

            // Log activity
            AuditLog::log(
                $workspaceId,
                $adminMemberId,
                $adminName,
                'member_remove',
                "Removed member: {$fullName} from workspace"
            );

            $db->commit();

            $this->jsonResponse(['success' => true, 'message' => 'Member removed successfully.']);
        } catch (\Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function generateInviteLink(): void
    {
        $admin = Session::adminUser();
        $workspaceId = (int)($admin['workspace_id'] ?? 0);
        $adminMemberId = (int)($admin['workspace_member_id'] ?? 0);

        if ($workspaceId === 0) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $input = $this->getRequestInput();
        $email = trim((string)($input['email'] ?? ''));
        $role = trim((string)($input['role'] ?? 'member'));

        if (!in_array($role, ['admin', 'member'], true)) {
            $role = 'member';
        }

        if (empty($email)) {
            $email = 'generic-invite@chatrox.com';
        }

        // Generate token and its hash
        $token = bin2hex(random_bytes(16)); // 32 characters hex
        $tokenHash = hash('sha256', $token);

        $db = Database::connection();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        try {
            $stmt = $db->prepare("
                INSERT INTO workspace_invites (workspace_id, email, role, token_hash, invited_by, expires_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $workspaceId,
                $email,
                $role,
                $tokenHash,
                $adminMemberId,
                $expiresAt
            ]);

            // Formulate join URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $joinUrl = $protocol . '://' . $host . '/join/' . $token;

            $this->jsonResponse([
                'success' => true,
                'join_url' => $joinUrl,
                'expires_at' => $expiresAt
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Failed to generate invite: ' . $e->getMessage()], 500);
        }
    }
}
