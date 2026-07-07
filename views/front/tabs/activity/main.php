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
        <div class="aph-right" style="display: flex; gap: 16px; align-items: center;">
            <div class="filter-pills">
                <div class="pill active">ALL</div>
                <div class="pill">MENTION</div>
                <div class="pill">FILE</div>
                <div class="pill">REACTION</div>
                <div class="pill">REQUESTS</div>
            </div>
            <button type="button" class="btn-clear-all js-clear-all-activity" style="background: none; border: 1px solid var(--border-color, #e2e8f0); color: #ef4444; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='none'">
                <i data-lucide="trash-2" size="14"></i>
                Clear All
            </button>
        </div>
    </div>

    <div class="activity-feed">
        <?php if (empty($activity_items)): ?>
            <div class="activity-empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 24px; text-align: center; border: 1.5px dashed var(--border-color, #e2e8f0); background: #f8fafc; border-radius: 16px; min-height: 250px; width: 100%; gap: 12px; box-sizing: border-box;">
                <div style="background: var(--indigo-50); color: var(--indigo-600); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 4px;">
                    <i data-lucide="bell-off" size="28"></i>
                </div>
                <p style="margin: 0; color: var(--text-primary, #0f172a); font-size: 16px; font-weight: 600; font-family: inherit;">No Notifications Yet</p>
                <span style="color: var(--text-muted, #64748b); font-size: 13px; font-family: inherit; max-width: 320px;">We will notify you here when you receive new mentions, files, or reactions.</span>
            </div>
        <?php else: ?>
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
                        <?php if ($item['notif_type'] === 'channel_join' && !empty($item['request_id']) && ($item['has_admin_access'] ?? false)): ?>
                        <div class="ac-actions">
                            <button type="button" class="ac-action-btn ac-action-approve js-activity-approve-request" 
                                    data-request-id="<?php echo (int) $item['request_id']; ?>"
                                    title="Approve">
                                <i data-lucide="check" size="14"></i>
                                <span>Approve</span>
                            </button>
                            <button type="button" class="ac-action-btn ac-action-reject js-activity-reject-request" 
                                    data-request-id="<?php echo (int) $item['request_id']; ?>"
                                    title="Reject">
                                <i data-lucide="x" size="14"></i>
                                <span>Reject</span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
