<div class="content-inner">
    <div class="activity-page-header">
        <div class="aph-left">
            <div class="aph-icon-box">
                <i data-lucide="bell" size="20"></i>
            </div>
            <div class="aph-titles">
                <h3>Activity Pulse</h3>
                <span class="label-tiny">CHATROX UPDATES</span>
            </div>
        </div>
        <div class="aph-right">
            <div class="filter-pills">
                <div class="pill active">ALL</div>
                <div class="pill">MENTION</div>
                <div class="pill">FILE</div>
                <div class="pill">REACTION</div>
            </div>
        </div>
    </div>

    <div class="activity-feed">
        <?php foreach ($activity_items as $item): ?>
            <div class="activity-card" data-id="<?php echo (int) $item['id']; ?>">
                <button class="ac-delete js-delete-activity" title="Remove notification">
                    <i data-lucide="delete" size="18"></i>
                </button>
                <?php if ($item['type'] === 'user'): ?>
                    <div class="ac-avatar">
                        <img src="<?php echo htmlspecialchars($item['avatar']); ?>"
                            alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </div>
                <?php elseif ($item['type'] === 'missed-call'): ?>
                    <div class="ac-avatar-system" style="background: #fff1f2; color: #e11d48;">
                        <i data-lucide="phone-missed" size="18"></i>
                    </div>
                <?php else: ?>
                    <div class="ac-avatar-system">
                        <i data-lucide="info" size="18"></i>
                    </div>
                <?php endif; ?>
                <div class="ac-content">
                    <div class="ac-header">
                        <span class="ac-name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="ac-dot"></span>
                        <span class="ac-time"><?php echo htmlspecialchars($item['time']); ?></span>
                    </div>
                    <div class="ac-body">
                        <?php if (!empty($item['body_html'])): ?>
                            <p><?php echo $item['body_html']; ?></p>
                        <?php else: ?>
                            <p><?php echo htmlspecialchars($item['body']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
