<div class="sub-nav">
    <div class="sub-nav-header">
        <h2>ACTIVITY</h2>
    </div>

    <div class="search-box">
        <i data-lucide="search" size="18"></i>
        <input type="text" placeholder="Search activity...">
    </div>

    <div class="live-updates">
        <h4 class="section-label">LIVE UPDATES</h4>
        <div class="activity-quick-list">
            <?php if (empty($activity_updates)): ?>
                <div class="aq-item no-activity">
                    <div class="aq-icon bg-gray">
                        <i data-lucide="bell" size="14"></i>
                    </div>
                    <div class="aq-info">
                        <div class="aq-text">No recent activity</div>
                        <div class="aq-time">—</div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($activity_updates as $update): ?>
                    <?php
                        $preview = strip_tags($update['body_html'] ?? $update['body'] ?? '');
                        $preview = preg_replace('/\s+/', ' ', trim($preview));
                        if (strlen($preview) > 48) {
                            $preview = substr($preview, 0, 45) . '...';
                        }
                        $icon = 'bell';
                        $colorClass = 'bg-gray';
                        if ($update['notif_type'] === 'mention') {
                            $icon = 'bell';
                            $colorClass = 'bg-green';
                        } elseif ($update['notif_type'] === 'file_share' || $update['notif_type'] === 'file_upload') {
                            $icon = 'file-text';
                            $colorClass = 'bg-blue';
                        } elseif ($update['notif_type'] === 'reaction') {
                            $icon = 'smile';
                            $colorClass = 'bg-orange';
                        } elseif ($update['notif_type'] === 'channel_join') {
                            $icon = 'user-plus';
                            $colorClass = 'bg-cyan';
                        }
                    ?>
                    <div class="aq-item" data-target="<?php echo (int) $update['id']; ?>">
                        <div class="aq-icon <?php echo htmlspecialchars($colorClass); ?>">
                            <i data-lucide="<?php echo htmlspecialchars($icon); ?>" size="14"></i>
                        </div>
                        <div class="aq-info">
                            <div class="aq-text"><?php echo htmlspecialchars($preview); ?></div>
                            <div class="aq-time"><?php echo htmlspecialchars($update['time']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>