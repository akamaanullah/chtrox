<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\WorkspaceMember;
use App\Core\Database;
use App\Models\UserPresence;
use App\Models\UserSession;
use App\Models\AuditLog;

class AuthController extends Controller
{
    public function showLoginForm(): void
    {
        if (Session::isAdminLoggedIn()) {
            $this->redirect('/admin');
        }

        $flash = Session::getFlash();
        $this->view('admin/login', [
            'error' => ($flash['type'] ?? '') === 'error' ? ($flash['message'] ?? null) : null,
        ]);
    }

    public function login(): void
    {
        if (!Session::verifyCsrf()) {
            Session::setFlash('error', 'Invalid form submission. Please try again.');
            $this->redirect('/admin/login');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            Session::setFlash('error', 'Please enter your username and password.');
            $this->redirect('/admin/login');
        }

        // Find user by username or email
        $user = User::findByUsername($username);
        if (!$user) {
            $user = User::findByEmail($username);
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::setFlash('error', 'Invalid username or password.');
            $this->redirect('/admin/login');
        }

        // Find active workspace member record where role is owner or admin
        $db = Database::connection();
        $stmt = $db->prepare('
            SELECT wm.*, w.name as workspace_name, w.slug as workspace_slug
            FROM workspace_members wm
            JOIN workspaces w ON wm.workspace_id = w.id
            WHERE wm.user_id = ? AND wm.status = "active" AND wm.role IN ("owner", "admin") AND w.status = "active" AND w.deleted_at IS NULL
        ');
        $stmt->execute([$user['id']]);
        $adminMemberships = $stmt->fetchAll();

        if (empty($adminMemberships)) {
            Session::setFlash('error', 'Access denied. You do not have administrator permissions for any workspace.');
            $this->redirect('/admin/login');
        }

        $member = $adminMemberships[0];

        // Generate and record database session
        $sessionToken = bin2hex(random_bytes(32));
        UserSession::create($user['id'], $sessionToken);

        Session::adminLogin([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'workspace_id' => $member['workspace_id'],
            'workspace_name' => $member['workspace_name'],
            'workspace_slug' => $member['workspace_slug'],
            'workspace_member_id' => $member['id'],
            'role' => $member['role'],
            'session_token' => $sessionToken,
        ]);

        // Set online status
        UserPresence::setOnline($user['id']);

        // Log to database audit log
        AuditLog::log(
            (int) $member['workspace_id'],
            (int) $member['id'],
            $user['first_name'] . ' ' . $user['last_name'],
            'login',
            'Administrator logged in successfully'
        );

        $this->redirect('/admin');
    }

    public function logout(): void
    {
        $admin = Session::adminUser();
        if ($admin && isset($admin['id'])) {
            UserPresence::setOffline((int) $admin['id']);
            if (isset($admin['session_token'])) {
                UserSession::revoke($admin['session_token']);
            }
            if (isset($admin['workspace_id']) && isset($admin['workspace_member_id'])) {
                AuditLog::log(
                    (int) $admin['workspace_id'],
                    (int) $admin['workspace_member_id'],
                    ($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''),
                    'logout',
                    'Administrator logged out successfully'
                );
            }
        }
        Session::adminLogout();
        $this->redirect('/admin/login');
    }
}
