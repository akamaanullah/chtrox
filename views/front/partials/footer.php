<?php

use App\Core\View;

$currentUser = \App\Core\Session::user();
$joinedChannels = [];

if ($currentUser) {
    $db = \App\Core\Model::db();
    
    // Fetch latest user details (bio, phone) and member details (job_title)
    $stmt = $db->prepare("
        SELECT u.bio, u.phone, wm.job_title 
        FROM users u 
        JOIN workspace_members wm ON wm.user_id = u.id AND wm.id = ? 
        WHERE u.id = ?
    ");
    $stmt->execute([$currentUser['workspace_member_id'], $currentUser['id']]);
    $extra = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($extra) {
        $currentUser = array_merge($currentUser, $extra);
    }

    // Fetch channels the user has joined
    $stmt = $db->prepare("
        SELECT c.id, c.slug, c.name, c.member_count 
        FROM channels c
        JOIN channel_members cm ON cm.channel_id = c.id AND cm.workspace_member_id = ? AND cm.left_at IS NULL
        WHERE c.workspace_id = ? AND c.status = 'active'
        ORDER BY c.name ASC
    ");
    $stmt->execute([$currentUser['workspace_member_id'], $currentUser['workspace_id']]);
    $joinedChannels = $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

View::render('partials/modals/create-channel-modal.php');
View::render('partials/panels/profile-panel.php', [
    'currentUser' => $currentUser,
    'joinedChannels' => $joinedChannels
]);
?>
<script>
    window.CHATROX = <?php echo json_encode([
        'integrations' => $integrations ?? [],
        'user' => [
            'id' => $currentUser['id'] ?? null,
            'workspace_member_id' => $currentUser['workspace_member_id'] ?? null,
            'workspace_id' => $currentUser['workspace_id'] ?? null,
            'session_token' => $currentUser['session_token'] ?? null,
            'username' => $currentUser['username'] ?? null,
            'first_name' => $currentUser['first_name'] ?? null,
            'last_name' => $currentUser['last_name'] ?? null,
            'avatar' => ($currentUser['avatar_path'] ?? null) ? (strpos($currentUser['avatar_path'], 'http://') === 0 || strpos($currentUser['avatar_path'], 'https://') === 0 ? $currentUser['avatar_path'] : \App\Core\View::asset($currentUser['avatar_path'])) : null,
            'phone' => $currentUser['phone'] ?? null,
            'bio' => $currentUser['bio'] ?? null,
            'job_title' => $currentUser['job_title'] ?? null
        ],
        'baseUrl' => BASE_URL,
        'basePath' => rtrim((string)(parse_url(BASE_URL, PHP_URL_PATH) ?: ''), '/'),
        'wsPort' => $_ENV['WS_PORT'] ?? 8080,
        'maxFileSizeBytes' => MAX_FILE_SIZE_BYTES,
        'maxFileSizeLabel' => \App\Helpers\FileUploadPolicy::formatSize(MAX_FILE_SIZE_BYTES),
    ], JSON_UNESCAPED_SLASHES); ?>;
</script>
<?php foreach ($page_scripts as $script): ?>
<script src="<?php echo View::asset($script); ?>" defer data-chatrox-tab="1"></script>
<?php endforeach; ?>

<!-- Custom Alert / Confirm Dialog Modal -->
<div class="modal-overlay" id="customConfirmModal" style="display: flex; align-items: center; justify-content: center; z-index: 9999;">
    <div class="modal-content" style="width: 400px; max-width: 90%; border-radius: 20px; background: var(--bg-surface, #ffffff); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden; border: 1px solid var(--border-color, #e2e8f0);">
        <div style="padding: 24px 32px; text-align: center;">
            <h3 id="customConfirmTitle" style="font-size: 18px; font-weight: 700; color: var(--text-primary, #0f172a); margin: 0 0 8px 0; font-family: 'Inter', sans-serif;">Confirm Action</h3>
            <p id="customConfirmMessage" style="font-size: 14px; color: var(--text-secondary, #475569); line-height: 1.5; margin: 0 0 24px 0; font-family: 'Inter', sans-serif;"></p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" id="customConfirmCancelBtn" class="profile-panel-btn profile-panel-btn--secondary" style="margin: 0; padding: 12px 20px; font-size: 14px; font-weight: 600; border-radius: 10px; cursor: pointer; flex: 1;">Cancel</button>
                <button type="button" id="customConfirmOkBtn" class="profile-panel-btn profile-panel-btn--primary" style="margin: 0; padding: 12px 20px; font-size: 14px; font-weight: 600; border-radius: 10px; cursor: pointer; flex: 1; background: #0f766e; color: #ffffff;">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const modal = document.getElementById('customConfirmModal');
        const titleEl = document.getElementById('customConfirmTitle');
        const msgEl = document.getElementById('customConfirmMessage');
        const cancelBtn = document.getElementById('customConfirmCancelBtn');
        const okBtn = document.getElementById('customConfirmOkBtn');

        window.ChatRoxDialog = {
            alert: function(message, title = 'Alert') {
                return new Promise((resolve) => {
                    titleEl.textContent = title;
                    msgEl.textContent = message;
                    cancelBtn.style.display = 'none';
                    okBtn.textContent = 'OK';
                    
                    function onOk() {
                        cleanup();
                        resolve(true);
                    }
                    
                    function cleanup() {
                        okBtn.removeEventListener('click', onOk);
                        modal.classList.remove('active');
                    }
                    
                    okBtn.addEventListener('click', onOk);
                    modal.classList.add('active');
                });
            },
            confirm: function(message, title = 'Confirm Action') {
                return new Promise((resolve) => {
                    titleEl.textContent = title;
                    msgEl.textContent = message;
                    cancelBtn.style.display = '';
                    okBtn.textContent = 'Confirm';
                    
                    function onOk() {
                        cleanup();
                        resolve(true);
                    }
                    
                    function onCancel() {
                        cleanup();
                        resolve(false);
                    }
                    
                    function cleanup() {
                        okBtn.removeEventListener('click', onOk);
                        cancelBtn.removeEventListener('click', onCancel);
                        modal.classList.remove('active');
                    }
                    
                    okBtn.addEventListener('click', onOk);
                    cancelBtn.addEventListener('click', onCancel);
                    modal.classList.add('active');
                });
            }
        };
    })();
</script>
</body>

</html>
