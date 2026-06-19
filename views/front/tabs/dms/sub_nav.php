<div class="sub-nav">
    <div class="sub-nav-header">
        <h2>DMS</h2>
        <a href="people" class="circle-btn" title="New Message" style="text-decoration: none;">
            <i data-lucide="edit"></i>
        </a>
    </div>

    <div class="search-box">
        <i data-lucide="search" size="18"></i>
        <input type="text" placeholder="Search dms...">
    </div>

    <div class="dms-directory">
        <h4 class="section-label">DMS DIRECTORY</h4>
        <div class="dm-list">
            <?php foreach ($dm_sidebar_items as $item): ?>
                <a href="dms/<?php echo htmlspecialchars($item['id']); ?>"
                    data-dm-username="<?php echo htmlspecialchars($item['id']); ?>"
                    data-last-preview="<?php echo htmlspecialchars($item['preview']); ?>"
                    class="dm-item <?php echo ($active_with === $item['id']) ? 'active' : ''; ?>">
                    <div class="avatar-sm">
                        <img src="<?php echo htmlspecialchars($item['avatar']); ?>"
                            alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <span class="status-online"></span>
                    </div>
                    <div class="dm-info">
                        <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                        <div class="dm-msg">
                            <?php echo htmlspecialchars($item['preview']); ?>
                        </div>
                    </div>
                    <div class="dm-right">
                        <span class="time"><?php echo htmlspecialchars($item['time']); ?></span>
                        <?php if (!empty($item['unread'])): ?>
                            <span class="unread-count"><?php echo (int) $item['unread']; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
