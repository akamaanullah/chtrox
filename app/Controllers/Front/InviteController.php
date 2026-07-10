<?php

namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Database;
use App\Models\User;
use App\Models\WorkspaceMember;
use App\Models\UserPresence;
use App\Models\UserSession;
use App\Models\AuditLog;
use PDO;

class InviteController extends Controller
{
    private function findInvite(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $db = Database::connection();
        $stmt = $db->prepare("
            SELECT i.*, w.name as workspace_name, w.slug as workspace_slug
            FROM workspace_invites i
            JOIN workspaces w ON i.workspace_id = w.id
            WHERE i.token_hash = ? AND i.accepted_at IS NULL AND i.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$tokenHash]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function show(string $token): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/');
        }

        $invite = $this->findInvite($token);
        if (!$invite) {
            Session::setFlash('error', 'The invitation link is invalid, has expired, or has already been accepted.');
            $this->redirect('/login');
            return;
        }

        $email = $invite['email'];
        $isGeneric = ($email === 'generic-invite@chatrox.com');

        $this->renderAuth('invite_register', 'Join Workspace', [
            'workspaceName' => $invite['workspace_name'],
            'email' => $isGeneric ? '' : $email,
            'isGeneric' => $isGeneric,
            'error' => null
        ]);
    }

    public function process(string $token): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/');
        }

        $invite = $this->findInvite($token);
        if (!$invite) {
            Session::setFlash('error', 'The invitation link is invalid or expired.');
            $this->redirect('/login');
            return;
        }

        $workspaceId = (int)$invite['workspace_id'];
        $role = $invite['role'];
        $email = $invite['email'];
        $isGeneric = ($email === 'generic-invite@chatrox.com');

        // Form inputs
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $inputEmail = $isGeneric ? trim((string)($_POST['email'] ?? '')) : $email;
        $phone = trim((string)($_POST['phone'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($firstName === '' || $lastName === '' || $username === '' || $inputEmail === '' || $phone === '' || $password === '') {
            $this->renderAuth('invite_register', 'Join Workspace', [
                'workspaceName' => $invite['workspace_name'],
                'email' => $isGeneric ? $inputEmail : $email,
                'isGeneric' => $isGeneric,
                'error' => 'Please fill in all required fields.'
            ]);
            return;
        }

        if ($password !== $confirmPassword) {
            $this->renderAuth('invite_register', 'Join Workspace', [
                'workspaceName' => $invite['workspace_name'],
                'email' => $isGeneric ? $inputEmail : $email,
                'isGeneric' => $isGeneric,
                'error' => 'Passwords do not match.'
            ]);
            return;
        }

        if (strlen($password) < 8) {
            $this->renderAuth('invite_register', 'Join Workspace', [
                'workspaceName' => $invite['workspace_name'],
                'email' => $isGeneric ? $inputEmail : $email,
                'isGeneric' => $isGeneric,
                'error' => 'Password must be at least 8 characters long.'
            ]);
            return;
        }

        if (!preg_match('/^[a-z0-9._-]+$/', $username)) {
            $this->renderAuth('invite_register', 'Join Workspace', [
                'workspaceName' => $invite['workspace_name'],
                'email' => $isGeneric ? $inputEmail : $email,
                'isGeneric' => $isGeneric,
                'error' => 'Username must be lowercase and contain only letters, numbers, dots, hyphens, or underscores (no spaces).'
            ]);
            return;
        }

        // Check unique fields
        if (User::findByUsername($username)) {
            $this->renderAuth('invite_register', 'Join Workspace', [
                'workspaceName' => $invite['workspace_name'],
                'email' => $isGeneric ? $inputEmail : $email,
                'isGeneric' => $isGeneric,
                'error' => 'Username is already taken.'
            ]);
            return;
        }

        if (User::findByEmail($inputEmail)) {
            $this->renderAuth('invite_register', 'Join Workspace', [
                'workspaceName' => $invite['workspace_name'],
                'email' => $isGeneric ? $inputEmail : $email,
                'isGeneric' => $isGeneric,
                'error' => 'Email address is already registered.'
            ]);
            return;
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            // Create User
            $passwordHash = User::hashPassword($password);

            $userId = User::create([
                'email' => $inputEmail,
                'username' => $username,
                'password_hash' => $passwordHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone
            ]);

            // Create User Security Record
            $secStmt = $db->prepare('INSERT INTO user_security (user_id) VALUES (?)');
            $secStmt->execute([$userId]);

            // Create Workspace Member Link
            $memberId = WorkspaceMember::create([
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
                'role' => $role,
                'job_title' => 'Team Member',
                'status' => 'active'
            ]);

            // Accept Invite in DB
            $stmtAccept = $db->prepare("UPDATE workspace_invites SET accepted_at = NOW() WHERE id = ?");
            $stmtAccept->execute([$invite['id']]);

            // Audit log
            AuditLog::log(
                $workspaceId,
                $memberId,
                "$firstName $lastName",
                'OTHER',
                'Joined workspace via invitation link'
            );

            // Create DB session
            $sessionToken = bin2hex(random_bytes(32));
            UserSession::create($userId, $sessionToken);

            $db->commit();

            // Log user in
            Session::login([
                'id' => $userId,
                'username' => $username,
                'email' => $inputEmail,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'avatar_path' => null,
                'workspace_id' => $workspaceId,
                'workspace_name' => $invite['workspace_name'],
                'workspace_slug' => $invite['workspace_slug'],
                'workspace_member_id' => $memberId,
                'role' => $role,
                'session_token' => $sessionToken
            ]);

            UserPresence::setOnline($userId);

            $this->redirect('/');
        } catch (\Exception $e) {
            $db->rollBack();
            $this->renderAuth('invite_register', 'Join Workspace', [
                'workspaceName' => $invite['workspace_name'],
                'email' => $isGeneric ? $inputEmail : $email,
                'isGeneric' => $isGeneric,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}
