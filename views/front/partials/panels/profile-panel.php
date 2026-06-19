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
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150"
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
                <span class="profile-panel-name-text" id="profileNameText">James Wilson</span>
                <span class="profile-panel-email-text" id="profileEmailText">james.wilson@chatrox.com</span>
                <button type="button" class="profile-panel-identity-edit-btn js-profile-edit-identity"
                    title="Edit name and email">
                    <i data-lucide="pencil" size="12"></i>
                </button>
            </div>
            <div class="profile-panel-identity-edit profile-panel-identity-edit--hidden" id="profileIdentityEdit">
                <input type="text" class="profile-panel-name" id="profileUsername" placeholder="Your name"
                    value="James Wilson">
                <input type="email" class="profile-panel-email" id="profileEmail" placeholder="email@example.com"
                    value="james.wilson@chatrox.com">
                <button type="button" class="profile-panel-identity-done-btn js-profile-done-identity" title="Done">
                    <i data-lucide="check" size="12"></i>
                </button>
            </div>
        </div>
        <div class="profile-panel-field">
            <label class="profile-panel-label">Professional Bio</label>
            <textarea class="profile-panel-textarea" id="profileBio" rows="3"
                placeholder="Tell others about yourself...">Product lead at Chatrox. Love building tools that teams actually use.</textarea>
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
            </div>
            <div class="profile-panel-channels-search">
                <i data-lucide="search" size="16"></i>
                <input type="text" class="profile-panel-channels-search-input js-joined-channels-search"
                    placeholder="Search joined groups..." id="profileChannelsSearch"
                    aria-label="Search joined channels">
            </div>
            <div class="profile-panel-channels-list" id="profileChannelsList">
                <div class="profile-channel-row" data-channel-name="general">
                    <div class="profile-channel-icon"><span>#</span></div>
                    <div class="profile-channel-info">
                        <span class="profile-channel-name">#general</span>
                        <span class="profile-channel-members">3 members</span>
                    </div>
                    <button type="button" class="profile-channel-leave js-leave-channel" title="Leave channel"
                        data-channel="general" aria-label="Leave #general">Leave</button>
                </div>
                <div class="profile-channel-row" data-channel-name="development-announcements">
                    <div class="profile-channel-icon"><span>#</span></div>
                    <div class="profile-channel-info">
                        <span class="profile-channel-name">#development-announcements</span>
                        <span class="profile-channel-members">2 members</span>
                    </div>
                    <button type="button" class="profile-channel-leave js-leave-channel" title="Leave channel"
                        data-channel="development-announcements"
                        aria-label="Leave #development-announcements">Leave</button>
                </div>
                <div class="profile-channel-row" data-channel-name="design-huddle">
                    <div class="profile-channel-icon"><span>#</span></div>
                    <div class="profile-channel-info">
                        <span class="profile-channel-name">#design-huddle</span>
                        <span class="profile-channel-members">5 members</span>
                    </div>
                    <button type="button" class="profile-channel-leave js-leave-channel" title="Leave channel"
                        data-channel="design-huddle" aria-label="Leave #design-huddle">Leave</button>
                </div>
                <div class="profile-channel-row" data-channel-name="security-alerts">
                    <div class="profile-channel-icon"><span>#</span></div>
                    <div class="profile-channel-info">
                        <span class="profile-channel-name">#security-alerts</span>
                        <span class="profile-channel-members">4 members</span>
                    </div>
                    <button type="button" class="profile-channel-leave js-leave-channel" title="Leave channel"
                        data-channel="security-alerts" aria-label="Leave #security-alerts">Leave</button>
                </div>
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