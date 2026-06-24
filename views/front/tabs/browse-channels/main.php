<div class="content-inner">
    <div class="browse-channels-header">
        <div class="bch-left">
            <div class="aph-left">
                <div class="aph-icon-box">
                    <i data-lucide="hash" size="20"></i>
                </div>
                <div class="aph-titles">
                    <h3>All Channels</h3>
                    <span class="label-tiny" style="letter-spacing: 2px;">BROWSE & JOIN</span>
                </div>
            </div>
        </div>
        <div class="bch-right" style="display: flex; gap: 16px; align-items: center;">
            <div class="search-box" style="width: 280px; margin-bottom: 0;">
                <i data-lucide="search" size="18"></i>
                <input type="text" placeholder="Search by name...">
            </div>
            <button type="button" class="btn-create-channel-main js-open-create-channel-modal">
                <i data-lucide="plus" size="18"></i>
                Create channel
            </button>
        </div>
    </div>

    <div class="all-channels-list" id="allChannelsList">
        <?php foreach ($browse_channels as $channel): ?>
            <div class="channel-row <?php echo $channel['joined'] ? 'joined' : ''; ?>" data-channel-id="<?php echo $channel['id']; ?>">
                <div class="channel-row-icon">
                    <i data-lucide="<?php echo htmlspecialchars($channel['icon']); ?>" size="20"
                        style="color: var(--indigo-600);"></i>
                </div>
                <div class="channel-row-info">
                    <h3><?php echo htmlspecialchars($channel['name']); ?></h3>
                    <span class="channel-meta"><?php echo htmlspecialchars($channel['meta']); ?></span>
                </div>
                <?php if ($channel['joined']): ?>
                    <span class="btn-joined">Joined</span>
                <?php elseif (!empty($channel['request_pending'])): ?>
                    <span class="btn-joined">Requested</span>
                <?php else: ?>
                    <button type="button" class="btn-join"><?php echo $channel['visibility'] === 'private' ? 'Request' : 'Join'; ?></button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
