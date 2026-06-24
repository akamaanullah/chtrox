<?php

namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\UserPresence;
use App\Models\UserSession;
use App\Models\AuditLog;
use App\Core\Database;
use PDO;

class AuthController extends Controller
{
    public function login(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!Session::verifyCsrf()) {
                $this->renderAuth('login', 'Sign In', [
                    'error' => 'Invalid form submission. Please try again.',
                ]);
                return;
            }
            $this->authenticate();
            return;
        }

        $flash = Session::getFlash();
        $this->renderAuth('login', 'Sign In', [
            'error' => ($flash['type'] ?? '') === 'error' ? ($flash['message'] ?? null) : null,
        ]);
    }

    public function register(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!Session::verifyCsrf()) {
                $this->renderAuth('register', 'Register Account', [
                    'error' => 'Invalid form submission. Please try again.',
                ]);
                return;
            }
            $this->processRegistration();
            return;
        }

        $this->renderAuth('register', 'Register Account');
    }

    public function logout(): void
    {
        $user = Session::user();
        if ($user && isset($user['id'])) {
            UserPresence::setOffline((int) $user['id']);
            if (isset($user['session_token'])) {
                UserSession::revoke($user['session_token']);
            }
            if (isset($user['workspace_id']) && isset($user['workspace_member_id'])) {
                AuditLog::log(
                    (int) $user['workspace_id'],
                    (int) $user['workspace_member_id'],
                    ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
                    'logout',
                    'User logged out successfully'
                );
            }
        }
        Session::destroy();
        $this->redirect('/login');
    }

    private function authenticate(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->renderAuth('login', 'Sign In', [
                'error' => 'Please enter your username and password.',
            ]);
            return;
        }

        // Find user by username or email
        $user = User::findByUsername($username);
        if (!$user) {
            $user = User::findByEmail($username);
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->renderAuth('login', 'Sign In', [
                'error' => 'Invalid username or password.',
            ]);
            return;
        }

        // Get active workspace memberships
        $memberships = WorkspaceMember::findActiveForUser($user['id']);
        if (empty($memberships)) {
            $this->renderAuth('login', 'Sign In', [
                'error' => 'Your account is not associated with any active workspace.',
            ]);
            return;
        }

        // Log in with the first active workspace
        $member = $memberships[0];

        // Generate and record database session
        $sessionToken = bin2hex(random_bytes(32));
        UserSession::create($user['id'], $sessionToken);

        Session::login([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'avatar_path' => $user['avatar_path'],
            'workspace_id' => $member['workspace_id'],
            'workspace_name' => $member['workspace_name'],
            'workspace_slug' => $member['workspace_slug'],
            'workspace_member_id' => $member['id'],
            'role' => $member['role'],
            'session_token' => $sessionToken,
        ]);

        UserPresence::setOnline($user['id']);

        // Log to database audit log
        AuditLog::log(
            (int) $member['workspace_id'],
            (int) $member['id'],
            $user['first_name'] . ' ' . $user['last_name'],
            'login',
            'User logged in successfully'
        );

        $this->redirect('/');
    }

    private function processRegistration(): void
    {
        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $industry = trim((string) ($_POST['industry'] ?? 'technology'));
        $organizationType = trim((string) ($_POST['organization_type'] ?? 'corporation'));
        $companyEmail = trim((string) ($_POST['company_email'] ?? ''));
        $companyPhone = trim((string) ($_POST['company_phone'] ?? ''));
        
        $address = trim((string) ($_POST['address_line1'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $state = trim((string) ($_POST['state'] ?? ''));
        $country = trim((string) ($_POST['country'] ?? ''));
        $zipCode = trim((string) ($_POST['postal_code'] ?? ''));

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        // Basic Validation
        if (
            $companyName === '' || $companyEmail === '' || $address === '' || $city === '' ||
            $country === '' || $firstName === '' || $lastName === '' || $username === '' ||
            $email === '' || $phone === '' || $password === ''
        ) {
            $this->renderAuth('register', 'Register Account', [
                'error' => 'Please fill in all required fields.',
            ]);
            return;
        }

        if ($password !== $confirmPassword) {
            $this->renderAuth('register', 'Register Account', [
                'error' => 'Passwords do not match.',
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderAuth('register', 'Register Account', [
                'error' => 'Invalid email address format.',
            ]);
            return;
        }

        // Check if username or email is already taken
        if (User::findByUsername($username)) {
            $this->renderAuth('register', 'Register Account', [
                'error' => 'Username is already taken.',
            ]);
            return;
        }

        if (User::findByEmail($email)) {
            $this->renderAuth('register', 'Register Account', [
                'error' => 'Email address is already registered.',
            ]);
            return;
        }

        // Handle company logo file upload
        $logoPath = null;
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = ROOT_DIR . '/public/uploads/logos';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                
                // Write a protective .htaccess file in the parent uploads directory
                $htaccessPath = ROOT_DIR . '/public/uploads/.htaccess';
                if (!is_file($htaccessPath)) {
                    $htaccessContent = "# Extra safety: deny script execution in public uploads\n" .
                                      "<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar|pl|py|cgi|asp|aspx|jsp|sh|bash)$\">\n" .
                                      "    Require all denied\n" .
                                      "</FilesMatch>\n";
                    @file_put_contents($htaccessPath, $htaccessContent);
                }
            }
            $tmpFile = $_FILES['company_logo']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
            
            // Validate extension and detected MIME type (strictly block SVG for XSS safety)
            $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif'];
            $allowedMimes = ['image/png', 'image/jpeg', 'image/gif'];
            
            $detectedMime = \App\Helpers\FileUploadPolicy::detectMime($tmpFile);
            
            if (in_array($ext, $allowedExtensions, true) && in_array($detectedMime, $allowedMimes, true)) {
                $filename = uniqid('logo_', true) . '.' . $ext;
                if (move_uploaded_file($tmpFile, $uploadDir . '/' . $filename)) {
                    $logoPath = 'uploads/logos/' . $filename;
                }
            }
        }

        $db = Database::connection();
        try {
            $db->beginTransaction();

            // 1. Create Workspace
            $slug = Workspace::generateUniqueSlug($companyName);
            $workspaceId = Workspace::create([
                'slug' => $slug,
                'name' => $companyName,
                'industry' => $industry,
                'organization_type' => $organizationType,
                'email' => $companyEmail,
                'phone' => $companyPhone !== '' ? $companyPhone : null,
                'logo_path' => $logoPath,
                'plan' => 'free',
                'status' => 'active'
            ]);

            // 2. Create Workspace Address
            Workspace::createAddress($workspaceId, [
                'address_line1' => $address,
                'city' => $city,
                'state' => $state !== '' ? $state : null,
                'country' => $country,
                'postal_code' => $zipCode
            ]);

            // 3. Create User
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $userId = User::create([
                'email' => $email,
                'username' => $username,
                'password_hash' => $passwordHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'avatar_path' => null,
                'bio' => null
            ]);

            // 4. Create Workspace Member (Role: Owner)
            $workspaceMemberId = WorkspaceMember::create([
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
                'role' => 'owner',
                'job_title' => 'Workspace Owner',
                'status' => 'active'
            ]);

            // 5. Initialize Preferences
            $prefStmt = $db->prepare('
                INSERT INTO user_preferences (user_id, theme_color, favorite_timezones, notification_settings, locale, timezone)
                VALUES (:user_id, "indigo", :fav_tz, :notif_settings, "en", "Asia/Karachi")
            ');
            $prefStmt->execute([
                'user_id' => $userId,
                'fav_tz' => json_encode([]),
                'notif_settings' => json_encode([])
            ]);

            // 6. Initialize Security record
            $secStmt = $db->prepare('
                INSERT INTO user_security (user_id, two_factor_enabled)
                VALUES (:user_id, 0)
            ');
            $secStmt->execute(['user_id' => $userId]);

            // 7. Initialize Presence record
            UserPresence::initialize($userId);

            // 8. Create Default '#general', '#announcements' and '#development-announcements' channels
            $defaultChannelsData = [
                [
                    'slug' => 'general',
                    'name' => 'general',
                    'description' => 'Company-wide announcements and work-based matters.',
                    'is_default' => 1
                ],
                [
                    'slug' => 'announcements',
                    'name' => 'announcements',
                    'description' => 'Workspace announcements, news, and notifications.',
                    'is_default' => 1
                ],
                [
                    'slug' => 'development-announcements',
                    'name' => 'development-announcements',
                    'description' => 'Announcements related to product development, updates, and releases.',
                    'is_default' => 1
                ]
            ];

            foreach ($defaultChannelsData as $ch) {
                $channelStmt = $db->prepare('
                    INSERT INTO channels (workspace_id, slug, name, description, visibility, status, is_default, created_by, member_count)
                    VALUES (:workspace_id, :slug, :name, :description, "public", "active", :is_default, :created_by, 1)
                ');
                $channelStmt->execute([
                    'workspace_id' => $workspaceId,
                    'slug' => $ch['slug'],
                    'name' => $ch['name'],
                    'description' => $ch['description'],
                    'is_default' => $ch['is_default'],
                    'created_by' => $workspaceMemberId
                ]);
                $channelId = (int) $db->lastInsertId();

                // Add Owner as member of the channel
                $channelMemberStmt = $db->prepare('
                    INSERT INTO channel_members (channel_id, workspace_member_id, role, notifications_muted)
                    VALUES (:channel_id, :workspace_member_id, "owner", 0)
                ');
                $channelMemberStmt->execute([
                    'channel_id' => $channelId,
                    'workspace_member_id' => $workspaceMemberId
                ]);

                // Create unified conversation for the channel
                $convStmt = $db->prepare('
                    INSERT INTO conversations (workspace_id, type, channel_id)
                    VALUES (:workspace_id, "channel", :channel_id)
                ');
                $convStmt->execute([
                    'workspace_id' => $workspaceId,
                    'channel_id' => $channelId
                ]);
                $conversationId = (int) $db->lastInsertId();

                // Add Owner as participant of the conversation
                $convParticipantStmt = $db->prepare('
                    INSERT INTO conversation_participants (conversation_id, workspace_member_id)
                    VALUES (:conversation_id, :workspace_member_id)
                ');
                $convParticipantStmt->execute([
                    'conversation_id' => $conversationId,
                    'workspace_member_id' => $workspaceMemberId
                ]);
            }

            $db->commit();

            // Set online presence
            UserPresence::setOnline($userId);

            // Generate and record database session
            $sessionToken = bin2hex(random_bytes(32));
            UserSession::create($userId, $sessionToken);

            // Log user in automatically after registration
            Session::login([
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'avatar_path' => null,
                'workspace_id' => $workspaceId,
                'workspace_name' => $companyName,
                'workspace_slug' => $slug,
                'workspace_member_id' => $workspaceMemberId,
                'role' => 'owner',
                'session_token' => $sessionToken,
            ]);

            // Log registration and auto-login
            AuditLog::log(
                $workspaceId,
                $workspaceMemberId,
                $firstName . ' ' . $lastName,
                'channel_create',
                'Workspace created and general channel set up'
            );

            AuditLog::log(
                $workspaceId,
                $workspaceMemberId,
                $firstName . ' ' . $lastName,
                'login',
                'User registered and logged in automatically'
            );

            $this->redirect('/');

        } catch (\Exception $e) {
            $db->rollBack();
            $errorMessage = APP_DEBUG 
                ? 'Registration failed: ' . $e->getMessage() 
                : 'Registration failed. Please verify your inputs and try again.';
            $this->renderAuth('register', 'Register Account', [
                'error' => $errorMessage,
            ]);
        }
    }
}
