<?php

use App\Core\View;
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo">
                <img src="<?php echo View::asset('assets/images/logo.png'); ?>" alt="ChatRox" width="50" height="50">
            </div>
            <div class="sidebar-brand-info">
                <span class="sidebar-brand-name">ChatRox Admin</span>
                <span class="sidebar-brand-role">Admin</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($nav_sections as $section): ?>
            <div class="nav-section">
                <span class="section-title"><?php echo View::e($section['title']); ?></span>
                <?php foreach ($section['links'] as $link): ?>
                    <a href="<?php echo View::dashboardUrl($link['id']); ?>"
                        class="nav-link <?php echo ($active_page === $link['id']) ? 'active' : ''; ?>">
                        <i data-lucide="<?php echo View::e($link['icon']); ?>"></i>
                        <span class="nav-label nav-label--full"><?php echo View::e($link['label']); ?></span>
                        <span class="nav-label nav-label--short"><?php echo View::e($link['short_label'] ?? $link['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo View::adminUrl('logout'); ?>" class="nav-link logout" title="Logout">
            <i data-lucide="log-out"></i>
            <span class="nav-label nav-label--full">Logout</span>
            <span class="nav-label nav-label--short">Exit</span>
        </a>
    </div>
</aside>
