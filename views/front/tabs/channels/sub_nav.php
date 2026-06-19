<div class="sub-nav">
    <div class="sub-nav-header">
        <h2>CHANNELS</h2>
        <div class="circle-btn js-open-create-channel-modal" title="Create channel">
            <i data-lucide="plus" size="18"></i>
        </div>
    </div>

    <div class="search-box">
        <i data-lucide="search" size="18"></i>
        <input type="text" placeholder="Search channels...">
    </div>

    <div class="channels-directory">
        <h4 class="section-label">CHANNELS DIRECTORY</h4>
        <div class="dir-list">
            <?php foreach ($channel_sidebar_items as $item): ?>
                <a href="channels/<?php echo htmlspecialchars($item['id']); ?>"
                    data-channel-id="<?php echo htmlspecialchars($item['channel_id']); ?>"
                    class="dir-item <?php echo ($active_channel_id === $item['id']) ? 'active' : ''; ?>">
                    <div class="dir-icon-box">
                        <?php echo htmlspecialchars($item['initials']); ?>
                    </div>
                    <div class="dir-info">
                        <div class="dir-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="dir-meta"><?php echo htmlspecialchars($item['meta']); ?></div>
                    </div>
                    <div class="dir-time">
                        <?php echo htmlspecialchars($item['time']); ?>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="badge-dot"><?php echo (int) $item['badge']; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
