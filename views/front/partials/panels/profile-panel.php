<?php
$currentUser = $currentUser ?? [];
$joinedChannels = $joinedChannels ?? [];
?>
<!-- Profile Panel - Right side slide-over -->
<div class="profile-panel-overlay" id="profilePanelOverlay"></div>
<div class="profile-panel" id="profilePanel">
    <div class="profile-panel-header">
        <h3>Profile</h3>
        <button type="button" class="profile-panel-close js-close-profile-panel">
            <i data-lucide="x" size="20"></i>
        </button>
    </div>
    <div class="profile-panel-body">
        <div class="profile-panel-avatar-wrap">
            <div class="profile-panel-avatar">
                <?php
                $avatarPath = $currentUser['avatar_path'] ?? null;
                if ($avatarPath) {
                    if (strpos($avatarPath, 'http://') !== 0 && strpos($avatarPath, 'https://') !== 0) {
                        $avatarUrl = \App\Core\View::asset($avatarPath);
                    } else {
                        $avatarUrl = $avatarPath;
                    }
                } else {
                    $avatarUrl = DEFAULT_AVATAR_URL;
                }
                ?>
                <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                    alt="Profile" id="profilePanelAvatarImg">
            </div>
            <span class="profile-panel-avatar-overlay"></span>
            <label class="profile-panel-avatar-edit" title="Change photo">
                <i data-lucide="camera" size="16"></i>
                <input type="file" accept="image/*" id="profileAvatarInput" hidden>
            </label>
        </div>
        <div class="profile-panel-identity">
            <div class="profile-panel-identity-view" id="profileIdentityView">
                <span class="profile-panel-name-text" id="profileNameText"><?php echo htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')); ?></span>
                <span class="profile-panel-email-text" id="profileEmailText"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></span>
                <button type="button" class="profile-panel-identity-edit-btn js-profile-edit-identity"
                    title="Edit name and email">
                    <i data-lucide="pencil" size="12"></i>
                </button>
            </div>
            <div class="profile-panel-identity-edit profile-panel-identity-edit--hidden" id="profileIdentityEdit">
                <input type="text" class="profile-panel-name" id="profileUsername" placeholder="Your name"
                    value="<?php echo htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')); ?>">
                <input type="email" class="profile-panel-email" id="profileEmail" placeholder="email@example.com"
                    value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" readonly disabled>
                <button type="button" class="profile-panel-identity-done-btn js-profile-done-identity" title="Done">
                    <i data-lucide="check" size="12"></i>
                </button>
            </div>
        </div>
        <div class="profile-panel-field">
            <label class="profile-panel-label">Professional Bio</label>
            <textarea class="profile-panel-textarea" id="profileBio" rows="3"
                placeholder="Tell others about yourself..."><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
        </div>
        <div class="profile-panel-field profile-panel-field--theme theme-collapsed" id="profileThemeField">
            <div class="profile-panel-theme-header js-theme-color-toggle" role="button" tabindex="0"
                title="Toggle theme options">
                <span class="profile-panel-label">THEME COLOR</span>
                <i data-lucide="chevron-up" size="16" class="theme-chevron"></i>
            </div>
            <div class="profile-panel-theme-options" id="profileThemeOptions">
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="indigo">
                    <span class="theme-swatch theme-swatch--indigo"></span>
                    <span class="theme-name">Indigo</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="blue">
                    <span class="theme-swatch theme-swatch--blue"></span>
                    <span class="theme-name">Blue</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="violet">
                    <span class="theme-swatch theme-swatch--violet"></span>
                    <span class="theme-name">Violet</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="emerald">
                    <span class="theme-swatch theme-swatch--emerald"></span>
                    <span class="theme-name">Emerald</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="rose">
                    <span class="theme-swatch theme-swatch--rose"></span>
                    <span class="theme-name">Rose</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="sky">
                    <span class="theme-swatch theme-swatch--sky"></span>
                    <span class="theme-name">Sky</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="teal">
                    <span class="theme-swatch theme-swatch--teal"></span>
                    <span class="theme-name">Teal</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="amber">
                    <span class="theme-swatch theme-swatch--amber"></span>
                    <span class="theme-name">Amber</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="cyan">
                    <span class="theme-swatch theme-swatch--cyan"></span>
                    <span class="theme-name">Cyan</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="fuchsia">
                    <span class="theme-swatch theme-swatch--fuchsia"></span>
                    <span class="theme-name">Fuchsia</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="lime">
                    <span class="theme-swatch theme-swatch--lime"></span>
                    <span class="theme-name">Lime</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_color" value="orange">
                    <span class="theme-swatch theme-swatch--orange"></span>
                    <span class="theme-name">Orange</span>
                </label>
            </div>
        </div>
        <div class="profile-panel-field profile-panel-field--channels">
            <div class="profile-panel-channels-header">
                <label class="profile-panel-label">Joined Channels</label>
                <span class="profile-panel-channels-count" id="profileChannelsActiveBadge" style="font-size: 11px; font-weight: 600; color: #0f766e; background: #f0fdf4; padding: 2px 8px; border-radius: 9999px; margin-left: 8px;"><?php echo count($joinedChannels); ?> active</span>
            </div>
            <div class="profile-panel-channels-search">
                <i data-lucide="search" size="16"></i>
                <input type="text" class="profile-panel-channels-search-input js-joined-channels-search"
                    placeholder="Search joined groups..." id="profileChannelsSearch"
                    aria-label="Search joined channels">
            </div>
            <div class="profile-panel-channels-list" id="profileChannelsList">
                <?php foreach ($joinedChannels as $ch): ?>
                <div class="profile-channel-row" data-channel-name="<?php echo htmlspecialchars(strtolower($ch['name'])); ?>">
                    <div class="profile-channel-icon"><span>#</span></div>
                    <div class="profile-channel-info">
                        <span class="profile-channel-name">#<?php echo htmlspecialchars($ch['name']); ?></span>
                        <span class="profile-channel-members"><?php echo htmlspecialchars($ch['member_count']); ?> members</span>
                    </div>
                    <button type="button" class="profile-channel-leave js-leave-channel" title="Leave channel"
                        data-channel-id="<?php echo (int)$ch['id']; ?>"
                        data-channel="<?php echo htmlspecialchars($ch['name']); ?>" aria-label="Leave #<?php echo htmlspecialchars($ch['name']); ?>">Leave</button>
                </div>
                <?php endforeach; ?>
                <?php if (empty($joinedChannels)): ?>
                <div style="padding: 20px; text-align: center; color: var(--text-secondary, #475569); font-size: 13px;">No joined channels</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-panel-actions">
            <button type="button" class="profile-panel-btn profile-panel-btn--secondary">
                <i data-lucide="key-round" size="16"></i>
                Change password
            </button>
            <a href="<?php echo \App\Core\View::url('logout'); ?>" class="profile-panel-btn profile-panel-btn--danger">
                <i data-lucide="log-out" size="16"></i>
                Logout
            </a>
        </div>
    </div>
</div>