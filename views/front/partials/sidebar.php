<?php

use App\Core\View;
?>
<aside class="sidebar">
    <div class="sidebar-logo" aria-label="ChatRox">
        <div class="logo-icon">
            <img src="<?php echo View::asset('assets/images/logo.png'); ?>" alt="ChatRox" class="sidebar-logo-img" width="50" height="50">
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($sidebar_tabs as $tab): ?>
            <div class="nav-item-wrapper <?php echo (isset($tab['is_more'])) ? 'more-trigger' : ''; ?>">
                <?php if (isset($tab['is_more'])): ?>
                    <div
                        class="nav-item no-link <?php echo ($active_tab == 'files' || $active_tab == 'browse-channels') ? 'active' : ''; ?>">
                        <?php if ($active_tab == 'files' || $active_tab == 'browse-channels'): ?>
                            <div class="active-bar"></div>
                        <?php endif; ?>
                        <div class="nav-icon-box">
                            <i data-lucide="<?php echo $tab['icon']; ?>"></i>
                        </div>
                        <span class="nav-text">
                            <?php echo $tab['label']; ?>
                        </span>
                    </div>
                <?php else: ?>
                    <a href="<?php echo View::url($tab['id']); ?>"
                        class="nav-item <?php echo ($active_tab == $tab['id']) ? 'active' : ''; ?>"
                        data-nav-tab="<?php echo htmlspecialchars($tab['id']); ?>">
                        <?php if ($active_tab == $tab['id']): ?>
                            <div class="active-bar"></div>
                        <?php endif; ?>
                        <div class="nav-icon-box">
                            <i data-lucide="<?php echo $tab['icon']; ?>"></i>
                            <?php if (isset($tab['badge'])): ?>
                                <span class="badge" id="navBadge-<?php echo htmlspecialchars($tab['id']); ?>">
                                    <?php echo $tab['badge']; ?>
                                </span>
                            <?php else: ?>
                                <span class="badge" id="navBadge-<?php echo htmlspecialchars($tab['id']); ?>" style="display:none"></span>
                            <?php endif; ?>
                        </div>
                        <span class="nav-text">
                            <?php echo $tab['label']; ?>
                        </span>
                    </a>
                <?php endif; ?>

                <?php if (isset($tab['is_more'])): ?>
                    <div class="more-popup">
                        <div class="more-popup-inner">
                            <a href="<?php echo View::url('files'); ?>" class="more-option <?php echo ($active_tab == 'files') ? 'active' : ''; ?>">
                                <i data-lucide="file-text" size="16"></i>
                                <span>File</span>
                            </a>
                            <a href="<?php echo View::url('browse-channels'); ?>"
                                class="more-option <?php echo ($active_tab == 'browse-channels') ? 'active' : ''; ?>">
                                <i data-lucide="layers" size="16"></i>
                                <span>Channels</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-bottom">
        <?php
        $currentUser = \App\Core\Session::user();
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
        <a href="#" class="nav-item js-open-profile-panel" id="sidebarAccountBtn">
            <div class="account-box">
                <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                    alt="Account" class="sidebar-user-avatar">
                <div class="status-dot"></div>
            </div>
            <span class="nav-text">Account</span>
        </a>
    </div>
</aside>