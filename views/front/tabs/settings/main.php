<?php
$notification_settings = $notification_settings ?? [];
$my_usage_bytes = $my_usage_bytes ?? 0;
$workspace_used_bytes = $workspace_used_bytes ?? 0;
$workspace_quota_bytes = $workspace_quota_bytes ?? 16106127360;

// Use pre-existing professional helper method for byte formatting
$myUsageFormatted = \App\Helpers\FileUploadPolicy::formatSize($my_usage_bytes);
$workspaceUsedFormatted = \App\Helpers\FileUploadPolicy::formatSize($workspace_used_bytes);
$workspaceQuotaFormatted = \App\Helpers\FileUploadPolicy::formatSize($workspace_quota_bytes);

$usagePercentage = 0;
if ($workspace_quota_bytes > 0) {
    $usagePercentage = round(($workspace_used_bytes / $workspace_quota_bytes) * 100, 1);
}
$usagePercentage = min(100, max(0, $usagePercentage));

$activeTheme = $_SESSION['chatrox_user']['theme_color'] ?? 'indigo';
?>
<!-- Settings Tab View Styling -->
<link rel="stylesheet" href="<?php echo \App\Core\View::asset('css/tabs/settings/settings.css'); ?>">

<div class="content-inner settings-page">
    <div class="files-page-header">
        <div class="aph-left">
            <div class="aph-icon-box">
                <i data-lucide="settings" size="20"></i>
            </div>
            <div class="aph-titles">
                <span class="label-tiny text-primary">USER PREFERENCES</span>
                <h3>Workspace Settings</h3>
                <p class="aph-subtitle">Configure notification settings, browser tones, security credentials, and storage resources.</p>
            </div>
        </div>
    </div>

    <!-- Notifications Tab -->
    <div class="settings-tab-content active" data-settings-tab="notifications" id="tab-notifications">
        <div class="settings-card">
            <h3 class="settings-card-title">
                <i data-lucide="bell" size="20"></i>
                Notification Preferences
            </h3>
            <p class="settings-card-desc">Configure when and where you receive real-time push alerts on your desktop browser.</p>
            
            <div class="settings-form-wrapper">
                <div class="setting-row">
                    <div class="setting-info">
                        <span class="setting-label">Global Desktop Notifications</span>
                        <span class="setting-desc">Toggle all system browser notifications on or off</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="notif-all" <?php echo ($notification_settings['all'] ?? true) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <span class="setting-label">Direct Message Alerts</span>
                        <span class="setting-desc">Get notified of direct messages from other users</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="notif-dm" <?php echo ($notification_settings['dm'] ?? true) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <span class="setting-label">Channel Message Alerts</span>
                        <span class="setting-desc">Get notified of new messages in your joined channels</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="notif-channels" <?php echo ($notification_settings['channels'] ?? true) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <span class="setting-label">Channel Join Requests</span>
                        <span class="setting-desc">Get notified when someone requests to join your channels</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="notif-requests" <?php echo ($notification_settings['channel_requests'] ?? true) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-info">
                        <span class="setting-label">Mentions & Replies</span>
                        <span class="setting-desc">Get notified when you are @mentioned in any message</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="notif-mentions" <?php echo ($notification_settings['mentions'] ?? true) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div style="margin-top: 10px;">
                    <label class="settings-input-label">Notification Tone</label>
                    <div class="sound-selector-row" style="margin-top: 6px;">
                        <select id="notif-tone" class="sound-select">
                            <option value="default" <?php echo ($notification_settings['tone'] ?? 'default') === 'default' ? 'selected' : ''; ?>>Default Tone 🔔</option>
                            <option value="chime" <?php echo ($notification_settings['tone'] ?? '') === 'chime' ? 'selected' : ''; ?>>Chime 🎵</option>
                            <option value="pop" <?php echo ($notification_settings['tone'] ?? '') === 'pop' ? 'selected' : ''; ?>>Pop 💥</option>
                            <option value="ping" <?php echo ($notification_settings['tone'] ?? '') === 'ping' ? 'selected' : ''; ?>>Ping 🏓</option>
                            <option value="none" <?php echo ($notification_settings['tone'] ?? '') === 'none' ? 'selected' : ''; ?>>Silent 🔇</option>
                        </select>
                        <button type="button" class="btn-sound-test" id="btnSoundTest" title="Preview notification tone">
                            <i data-lucide="volume-2" size="20"></i>
                        </button>
                    </div>
                </div>

                <div style="margin-top: 12px;">
                    <button type="button" class="btn-settings-action" id="btnSavePreferences">
                        <i data-lucide="save" size="16"></i>
                        Save Preferences
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Preference Tab -->
    <div class="settings-tab-content" data-settings-tab="theme" id="tab-theme">
        <div class="settings-card">
            <h3 class="settings-card-title">
                <i data-lucide="palette" size="20"></i>
                Theme Preference
            </h3>
            <p class="settings-card-desc">Select your workspace accent theme. Accent colors are applied instantly across the entire interface.</p>
            
            <div class="theme-gallery">
                <?php
                $colors = [
                    'indigo' => '#6366f1',
                    'blue' => '#3b82f6',
                    'violet' => '#8b5cf6',
                    'emerald' => '#10b981',
                    'rose' => '#f43f5e',
                    'sky' => '#0ea5e9',
                    'teal' => '#14b8a6',
                    'amber' => '#f59e0b',
                    'cyan' => '#06b6d4',
                    'fuchsia' => '#d946ef',
                    'lime' => '#84cc16',
                    'orange' => '#f97316'
                ];
                foreach ($colors as $name => $hex):
                    $isCurrent = $activeTheme === $name;
                ?>
                <label class="theme-card-option <?php echo $isCurrent ? 'active-card' : ''; ?>" data-theme="<?php echo $name; ?>" title="<?php echo ucfirst($name); ?>">
                    <input type="radio" name="theme_color" value="<?php echo $name; ?>" <?php echo $isCurrent ? 'checked' : ''; ?>>
                    <span class="theme-swatch-circle" style="background: <?php echo $hex; ?>;"></span>
                    <span class="theme-swatch-label"><?php echo $name; ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Change Password Tab -->
    <div class="settings-tab-content" data-settings-tab="password" id="tab-password">
        <div class="settings-card">
            <h3 class="settings-card-title">
                <i data-lucide="lock" size="20"></i>
                Change Password
            </h3>
            <p class="settings-card-desc">Keep your workspace account secure by updating your credentials regularly.</p>

            <div class="settings-form-wrapper">
                <form id="formChangePassword" autocomplete="off" onsubmit="return false;" style="display: flex; flex-direction: column; gap: 16px; width: 100%;">
                    <div class="settings-input-group">
                        <label class="settings-input-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" class="settings-input" placeholder="••••••••" required>
                    </div>

                    <div class="settings-input-group">
                        <label class="settings-input-label" for="new_password">New Password</label>
                        <input type="password" id="new_password" class="settings-input" placeholder="••••••••" required>
                    </div>

                    <div class="settings-input-group">
                        <label class="settings-input-label" for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" class="settings-input" placeholder="••••••••" required>
                    </div>

                    <div style="margin-top: 10px;">
                        <button type="submit" class="btn-settings-action" id="btnUpdatePassword">
                            <i data-lucide="shield-check" size="16"></i>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Security & Sessions Tab -->
    <div class="settings-tab-content" data-settings-tab="security" id="tab-security">
        <div class="settings-card">
            <h3 class="settings-card-title">
                <i data-lucide="shield" size="20"></i>
                Security, Presence & Sessions
            </h3>
            <p class="settings-card-desc">Manage your presence status and review active logged-in device sessions.</p>

            <div class="settings-form-wrapper">
                <!-- Presence Status Card -->
                <div style="border-bottom: 1px solid var(--border-color, #e2e8f0); padding-bottom: 24px; margin-bottom: 12px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 15px; font-weight: 600; color: var(--text-primary);">Presence Status</h4>
                    <p style="margin: 0 0 16px 0; font-size: 13px; color: var(--text-secondary);">Set your global presence. Select "Do Not Disturb" to automatically mute notification sound chimes.</p>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <select id="selectPresenceStatus" class="settings-input" style="max-width: 240px; padding: 8px 12px;">
                            <option value="online">Online 🟢</option>
                            <option value="away">Away 🟡</option>
                            <option value="dnd">Do Not Disturb 🔴</option>
                        </select>
                        <button type="button" class="btn-settings-action" id="btnUpdatePresence" style="margin-top: 0; padding: 8px 16px;">Update Status</button>
                    </div>
                </div>

                <!-- Active Sessions List -->
                <div>
                    <h4 style="margin: 0 0 8px 0; font-size: 15px; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                        <span>Active Devices & Sessions</span>
                        <span id="sessionCountBadge" style="font-size: 12px; background: var(--indigo-50); color: var(--indigo-600); padding: 2px 8px; border-radius: 9999px; font-weight: 700;">0</span>
                    </h4>
                    <p style="margin: 0 0 16px 0; font-size: 13px; color: var(--text-secondary);">These devices are currently logged into your ChatRox account. You can revoke any session to sign it out remotely.</p>
                    
                    <div class="sessions-list-container" id="sessionsListContainer" style="display: flex; flex-direction: column; gap: 12px;">
                        <div class="sessions-loading" style="color: var(--text-muted); font-size: 14px; display: flex; align-items: center; gap: 8px; padding: 12px 0;">
                            <span class="spinner" style="border: 2px solid var(--border-color, #e2e8f0); border-top-color: var(--indigo-600, #4f46e5); border-radius: 50%; width: 16px; height: 16px; display: inline-block; animation: spin 1s linear infinite;"></span>
                            Loading active sessions...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Storage & Usage Tab -->
    <div class="settings-tab-content" data-settings-tab="usage" id="tab-usage">
        <div class="settings-card">
            <h3 class="settings-card-title">
                <i data-lucide="hard-drive" size="20"></i>
                Storage & Space Usage
            </h3>
            <p class="settings-card-desc">Monitor details about your workspace allocated quota and see your personal file upload footprint.</p>

            <div class="settings-form-wrapper">
                <div class="usage-card-wrapper">
                    <div class="usage-summary">
                        <span>Workspace Storage Allocation</span>
                        <span><?php echo $workspaceUsedFormatted; ?> / <?php echo $workspaceQuotaFormatted; ?></span>
                    </div>

                    <div class="usage-progress-container">
                        <div class="usage-progress-fill" style="width: <?php echo $usagePercentage; ?>%; background: <?php echo $usagePercentage > 90 ? '#ef4444' : ($usagePercentage > 75 ? '#eab308' : 'var(--indigo-600, #4f46e5)'); ?>;"></div>
                    </div>

                    <div class="usage-details">
                        <span><?php echo $usagePercentage; ?>% used</span>
                        <span class="personal-usage-badge" title="Storage space taken by your uploads">
                            My Uploads: <?php echo $myUsageFormatted; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
