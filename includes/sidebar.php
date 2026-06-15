<?php
$tabs = [
    ['id' => 'home', 'icon' => 'home', 'label' => 'Home'],
    ['id' => 'dms', 'icon' => 'message-square-text', 'label' => 'Dms', 'badge' => 1],
    ['id' => 'channels', 'icon' => 'hash', 'label' => 'Channels'],
    ['id' => 'people', 'icon' => 'users-round', 'label' => 'People'],
    ['id' => 'activity', 'icon' => 'bell-ring', 'label' => 'Activity'],
    ['id' => 'more', 'icon' => 'more-horizontal', 'label' => 'More', 'is_more' => true],
];
?>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">
            <i data-lucide="zap"></i>
        </div>
        <div class="logo-dot"></div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($tabs as $tab): ?>
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
                    <a href="<?php echo $tab['id']; ?>"
                        class="nav-item <?php echo ($active_tab == $tab['id']) ? 'active' : ''; ?>">
                        <?php if ($active_tab == $tab['id']): ?>
                            <div class="active-bar"></div>
                        <?php endif; ?>
                        <div class="nav-icon-box">
                            <i data-lucide="<?php echo $tab['icon']; ?>"></i>
                            <?php if (isset($tab['badge'])): ?>
                                <span class="badge">
                                    <?php echo $tab['badge']; ?>
                                </span>
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
                            <a href="files" class="more-option <?php echo ($active_tab == 'files') ? 'active' : ''; ?>">
                                <i data-lucide="file-text" size="16"></i>
                                <span>File</span>
                            </a>
                            <a href="browse-channels"
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
        <a href="#" class="nav-item js-open-profile-panel" id="sidebarAccountBtn">
            <div class="account-box">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150"
                    alt="Account">
                <div class="status-dot"></div>
            </div>
            <span class="nav-text">Account</span>
        </a>
    </div>
</aside>