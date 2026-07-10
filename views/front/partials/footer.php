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
View::render('partials/modals/feedback-modal.php');
View::render('partials/panels/profile-panel.php', [
    'currentUser' => $currentUser,
    'joinedChannels' => $joinedChannels
]);

$footerDb = \App\Core\Database::connection();
$footerUserId = (int)($currentUser['id'] ?? 0);
$footerPrefs = [
    'theme_color' => 'indigo',
    'notification_settings' => [
        'all' => true,
        'dm' => true,
        'channels' => true,
        'channel_requests' => true,
        'mentions' => true,
        'tone' => 'default'
    ]
];
$footerPresence = 'online';

if ($footerUserId > 0) {
    $footerStmt = $footerDb->prepare("SELECT theme_color, notification_settings FROM user_preferences WHERE user_id = ? LIMIT 1");
    $footerStmt->execute([$footerUserId]);
    $footerPrefsRow = $footerStmt->fetch(PDO::FETCH_ASSOC);
    if ($footerPrefsRow) {
        $footerPrefs = [
            'theme_color' => $footerPrefsRow['theme_color'] ?? 'indigo',
            'notification_settings' => array_merge($footerPrefs['notification_settings'], json_decode($footerPrefsRow['notification_settings'] ?? '{}', true))
        ];
    }
    $footerStmtPresence = $footerDb->prepare("SELECT status FROM user_presence WHERE user_id = ? LIMIT 1");
    $footerStmtPresence->execute([$footerUserId]);
    $footerPresenceRow = $footerStmtPresence->fetch(PDO::FETCH_ASSOC);
    if ($footerPresenceRow) {
        $footerPresence = $footerPresenceRow['status'] ?? 'online';
    }
}
?>
<script>
    window.CHATROX = <?php echo json_encode([
        'integrations' => $integrations ?? [],
        'user' => [
            'id' => $currentUser['id'] ?? null,
            'preferences' => $footerPrefs,
            'presence_status' => $footerPresence,
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
        'apiUrl' => BASE_URL . '/api/v1',
        'basePath' => rtrim((string)(parse_url(BASE_URL, PHP_URL_PATH) ?: ''), '/'),
        'wsPort' => WS_PORT,
        'csrfToken' => \App\Core\Session::csrfToken(),
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
                <button type="button" id="customConfirmOkBtn" class="profile-panel-btn profile-panel-btn--primary" style="margin: 0; padding: 12px 20px; font-size: 14px; font-weight: 600; border-radius: 10px; cursor: pointer; flex: 1; background: var(--indigo-600, #4f46e5); color: #ffffff;">Confirm</button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js" defer></script>
<script>
    window.highlightCodeBlocks = function (scope) {
        if (window.Prism) {
            var target = scope || document;
            target.querySelectorAll('pre code').forEach(function (block) {
                if (!block.className || block.className.indexOf('language-') === -1) {
                    block.className = 'language-javascript';
                }
                Prism.highlightElement(block);
            });
        }
    };
</script>
<script>
    (function () {
        // Global listener for image loading errors (capturing phase to catch non-bubbling 'error' events on IMG tags)
        window.addEventListener('error', function (e) {
            if (!e.target || e.target.tagName !== 'IMG') return;
            var img = e.target;

            // 1. Avatars (files list avatar, details avatar, sidebar avatar, profile avatar)
            if (img.classList.contains('file-card-avatar') || 
                img.classList.contains('dm-chat-header-avatar') || 
                img.classList.contains('dm-details-avatar') || 
                img.classList.contains('profile-panel-avatar') ||
                img.classList.contains('avatar') ||
                img.src.indexOf('avatar') !== -1) {
                img.onerror = null;
                img.src = (window.CHATROX ? window.CHATROX.baseUrl : '') + '/assets/images/default-avatar.svg';
                return;
            }

            // 2. File Card Previews (Files tab)
            if (img.classList.contains('file-card-img')) {
                var parent = img.parentElement;
                if (!parent) return;
                var placeholder = document.createElement('div');
                placeholder.className = 'file-card-icon-placeholder bg-gray js-file-preview-trigger';
                placeholder.setAttribute('data-index', img.getAttribute('data-index') || '');
                placeholder.innerHTML = '<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">' +
                    '<i data-lucide="image-off" size="36"></i>' +
                    '<span style="font-size: 11px; font-weight: 500; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px;">No image found</span>' +
                    '</div>';
                parent.replaceChild(placeholder, img);
                if (window.lucide) window.lucide.createIcons({ nodes: [placeholder] });
                return;
            }

            // 3. Sidebar Details Media Grid thumbnails
            if (img.classList.contains('dm-details-media-thumb')) {
                var parent = img.parentElement;
                if (!parent) return;
                var placeholder = document.createElement('div');
                placeholder.className = 'dm-details-media-thumb';
                placeholder.setAttribute('alt', img.getAttribute('alt') || '');
                placeholder.setAttribute('data-index', img.getAttribute('data-index') || '');
                placeholder.style.display = 'flex';
                placeholder.style.flexDirection = 'column';
                placeholder.style.alignItems = 'center';
                placeholder.style.justifyContent = 'center';
                placeholder.style.background = '#f1f5f9';
                placeholder.style.border = '1px dashed #cbd5e1';
                placeholder.style.color = '#94a3b8';
                placeholder.innerHTML = '<i data-lucide="image-off" size="20"></i>';
                parent.replaceChild(placeholder, img);
                if (window.lucide) window.lucide.createIcons({ nodes: [placeholder] });
                return;
            }

            // 4. Chat Message Images
            if (img.classList.contains('dm-msg-img') || img.classList.contains('js-msg-img')) {
                var parent = img.parentElement;
                if (!parent) return;
                var placeholder = document.createElement('div');
                placeholder.className = 'dm-msg-images dm-msg-images--single';
                placeholder.style.display = 'flex';
                placeholder.style.flexDirection = 'column';
                placeholder.style.alignItems = 'center';
                placeholder.style.justifyContent = 'center';
                placeholder.style.background = '#f8fafc';
                placeholder.style.border = '1px dashed #cbd5e1';
                placeholder.style.borderRadius = '12px';
                placeholder.style.padding = '16px';
                placeholder.style.color = '#94a3b8';
                placeholder.style.gap = '6px';
                placeholder.innerHTML = '<i data-lucide="image-off" size="24"></i>' +
                    '<span style="font-size: 11px; font-weight: 500; opacity: 0.8;">No image found</span>';
                parent.replaceChild(placeholder, img);
                if (window.lucide) window.lucide.createIcons({ nodes: [placeholder] });
                return;
            }
        }, true);
    })();
</script>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('service-worker.js')
                .then(function(registration) {
                    console.log('ChatRox ServiceWorker registered successfully with scope: ', registration.scope);
                })
                .catch(function(err) {
                    console.log('ChatRox ServiceWorker registration failed: ', err);
                });
        });
    }
</script>
</body>
</html>
