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
                <div class="pill">REQUESTS</div>
            </div>
        </div>
    </div>

    <div class="activity-feed">
        <?php foreach ($activity_items as $item): ?>
            <div id="activity-card-<?php echo (int) $item['id']; ?>" class="activity-card<?php echo ($item['notif_type'] === 'channel_join') ? ' activity-card--join-request' : ''; ?>" 
                 data-id="<?php echo (int) $item['id']; ?>" 
                 data-path="<?php echo htmlspecialchars($item['path'] ?? ''); ?>"
                 <?php if ($item['notif_type'] === 'channel_join' && !empty($item['request_id'])): ?>
                    data-request-id="<?php echo (int) $item['request_id']; ?>"
                    data-notif-id="<?php echo (int) $item['id']; ?>"
                 <?php endif; ?>>
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
                <?php elseif ($item['type'] === 'channel-join'): ?>
                    <div class="ac-avatar-system" style="background: #dbeafe; color: #0284c7;">
                        <i data-lucide="user-plus" size="18"></i>
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
                    <?php if ($item['notif_type'] === 'channel_join' && !empty($item['request_id']) && in_array($item['current_channel_role'] ?? '', ['owner', 'admin'], true)): ?>
                    <div class="ac-actions">
                        <button type="button" class="ac-action-btn ac-action-approve js-activity-approve-request" 
                                data-request-id="<?php echo (int) $item['request_id']; ?>"
                                title="Approve">
                            Approve
                        </button>
                        <button type="button" class="ac-action-btn ac-action-reject js-activity-reject-request" 
                                data-request-id="<?php echo (int) $item['request_id']; ?>"
                                title="Reject">
                            Reject
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
