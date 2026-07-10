<?php
use App\Core\View;
?>
<header class="content-header">
    <div class="greeting-area">
        <div class="greeting-icon" style="background: var(--orange-50, rgba(249, 115, 22, 0.08)); color: var(--orange-500, #f97316);">
            <i data-lucide="key"></i>
        </div>
        <div class="greeting-text">
            <h1>Password Reset Requests</h1>
            <p class="date">Manage and resolve user requests to reset their forgotten passwords.</p>
        </div>
    </div>
</header>

<div class="members-container">
    <div class="members-table-wrapper">
        <table class="members-table">
            <thead>
                <tr>
                    <th>USER</th>
                    <th>REQUEST DATE</th>
                    <th>STATUS</th>
                    <th class="text-right">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 40px; color: var(--text-slate); font-weight: 500;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                <i data-lucide="shield-check" style="width: 48px; height: 48px; color: var(--green-500, #10b981);"></i>
                                <span style="font-size: 16px; font-weight: 600;">No Pending Requests</span>
                                <span style="font-size: 13px;">All password reset requests have been resolved!</span>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <tr class="request-row" data-id="<?php echo (int)$req['id']; ?>" data-username="<?php echo View::e($req['username']); ?>">
                            <td>
                                <div class="member-info-cell">
                                    <?php
                                        $initials = '';
                                        $names = explode(' ', $req['user_name']);
                                        foreach ($names as $n) {
                                            $initials .= strtoupper(substr($n, 0, 1));
                                        }
                                        $initials = substr($initials, 0, 2);
                                        $colors = ['bg-indigo', 'bg-pink', 'bg-orange', 'bg-purple', 'bg-green', 'bg-cyan', 'bg-yellow', 'bg-red', 'bg-blue', 'bg-emerald', 'bg-slate', 'bg-amber', 'bg-rose'];
                                        $colorClass = $colors[ord(substr($req['user_name'], 0, 1)) % count($colors)];
                                    ?>
                                    <div class="avatar-mini <?php echo $colorClass; ?>"><?php echo View::e($initials); ?></div>
                                    <div class="member-details">
                                        <span class="member-name"><?php echo View::e($req['user_name']); ?></span>
                                        <span class="member-email"><?php echo View::e($req['email']); ?> (<?php echo View::e($req['username']); ?>)</span>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?></td>
                            <td>
                                <span class="tag-pill tag-bug" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">Pending</span>
                            </td>
                            <td class="text-right">
                                <button class="btn-primary reset-btn" onclick="openResetModal(<?php echo (int)$req['id']; ?>, '<?php echo View::e($req['username']); ?>')" style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i data-lucide="key" style="width: 14px; height: 14px;"></i>
                                    <span>Reset Password</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal-overlay" style="display: none;">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title-area">
                <div>
                    <h3>Reset User Password</h3>
                    <p style="font-size: 11px; text-transform: uppercase; color: var(--text-slate);">Enter new password for: <strong id="resetTargetUsername">username</strong></p>
                </div>
            </div>
            <button class="modal-close" onclick="closeResetModal()">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="modal-error-alert" style="display: none; background: #fee2e2; color: #ef4444; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 13px; font-weight: 500; border: 1px solid rgba(239, 68, 68, 0.1);"></div>
            
            <form id="resetPasswordForm" class="modal-form" onsubmit="event.preventDefault();">
                <input type="hidden" id="resetRequestId">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="newPassword">New Password (Min 8 characters)</label>
                        <div class="password-input-wrapper" style="position: relative;">
                            <input type="password" id="newPassword" placeholder="••••••••" required style="width: 100%; padding-right: 40px; height: 42px; border-radius: 8px; border: 1px solid var(--border-color); padding-left: 12px;">
                            <i data-lucide="eye" class="toggle-password" onclick="togglePasswordVisibility('newPassword')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-slate);"></i>
                        </div>
                    </div>
                    <div class="form-group full-width" style="margin-top: 16px;">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="password-input-wrapper" style="position: relative;">
                            <input type="password" id="confirmPassword" placeholder="••••••••" required style="width: 100%; padding-right: 40px; height: 42px; border-radius: 8px; border: 1px solid var(--border-color); padding-left: 12px;">
                            <i data-lucide="eye" class="toggle-password" onclick="togglePasswordVisibility('confirmPassword')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-slate);"></i>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="margin-top: 24px;">
            <button class="btn-primary" id="saveResetBtn" onclick="submitPasswordReset()" style="width: 100%; justify-content: center; height: 42px;">
                <i data-lucide="check"></i>
                <span>Confirm Reset</span>
            </button>
        </div>
    </div>
</div>

<script>
function openResetModal(requestId, username) {
    document.getElementById('resetRequestId').value = requestId;
    document.getElementById('resetTargetUsername').textContent = username;
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    document.getElementById('modal-error-alert').style.display = 'none';
    
    const modal = document.getElementById('resetPasswordModal');
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
}

function closeResetModal() {
    const modal = document.getElementById('resetPasswordModal');
    modal.classList.remove('active');
    setTimeout(() => modal.style.display = 'none', 300);
}

function togglePasswordVisibility(fieldId) {
    const input = document.getElementById(fieldId);
    const icon = event.currentTarget;
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    if (window.lucide) {
        lucide.createIcons({ nodes: [icon.parentElement] });
    }
}

function submitPasswordReset() {
    const requestId = document.getElementById('resetRequestId').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const errorAlert = document.getElementById('modal-error-alert');
    const saveBtn = document.getElementById('saveResetBtn');

    if (newPassword.length < 8) {
        errorAlert.textContent = 'Password must be at least 8 characters long.';
        errorAlert.style.display = 'block';
        return;
    }

    if (newPassword !== confirmPassword) {
        errorAlert.textContent = 'Passwords do not match.';
        errorAlert.style.display = 'block';
        return;
    }

    errorAlert.style.display = 'none';
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span>Processing...</span>';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch('<?php echo BASE_URL; ?>/api/admin/resets/process', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            request_id: requestId,
            new_password: newPassword,
            confirm_password: confirmPassword
        })
    })
    .then(response => response.json().then(data => ({ status: response.status, data })))
    .then(({ status, data }) => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i data-lucide="check"></i><span>Confirm Reset</span>';
        if (window.lucide) lucide.createIcons({ nodes: [saveBtn] });

        if (status === 200 && data.success) {
            if (typeof utils !== 'undefined' && typeof utils.showToast === 'function') {
                utils.showToast(data.message, 'success');
            }
            closeResetModal();
            // Reload page to refresh list after 1 second
            setTimeout(() => window.location.reload(), 1000);
        } else {
            errorAlert.textContent = data.error || 'Failed to reset password.';
            errorAlert.style.display = 'block';
        }
    })
    .catch(err => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i data-lucide="check"></i><span>Confirm Reset</span>';
        if (window.lucide) lucide.createIcons({ nodes: [saveBtn] });
        errorAlert.textContent = 'An unexpected connection error occurred.';
        errorAlert.style.display = 'block';
    });
}
</script>
