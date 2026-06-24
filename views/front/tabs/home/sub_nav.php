<?php

$home_chat_card = $home_chat_card ?? [
    'badge' => 'LIVE',
    'label' => 'Chat Inbox',
    'title' => "You're all caught up!",
    'progress' => 100,
    'progress_label' => 'Inbox clear',
];
$home_sidebar_dms = $home_sidebar_dms ?? [];
$home_sidebar_channels = $home_sidebar_channels ?? [];
$home_sidebar_activity = $home_sidebar_activity ?? [];
$progress = max(0, min(100, (int)($home_chat_card['progress'] ?? 0)));
$progressLabel = $home_chat_card['progress_label'] ?? 'Inbox clear';
?>
<div class="sub-nav">
    <div class="sub-nav-header">
        <h2>HOME</h2>
    </div>

    <div class="search-box">
        <i data-lucide="search" size="18"></i>
        <input type="text" id="homeSubNavSearch" placeholder="Search home..." autocomplete="off" aria-label="Search home sidebar">
    </div>

    <div class="sub-nav-content" id="homeSubNavContent">
        <div class="priority-card" id="homeInboxCard" data-home-group="inbox">
            <div class="card-tag" id="homeInboxBadge"><?php echo htmlspecialchars($home_chat_card['badge']); ?></div>
            <span class="label"><?php echo htmlspecialchars($home_chat_card['label']); ?></span>
            <h3 id="homeInboxTitle"><?php echo htmlspecialchars($home_chat_card['title']); ?></h3>
            <div class="progress-container">
                <span class="progress-text" id="homeInboxProgressText"><?php echo htmlspecialchars($progressLabel); ?> <?php echo $progress; ?>%</span>
                <div class="progress-bar">
                    <div class="progress-fill" id="homeInboxProgressFill" style="width: <?php echo $progress; ?>%;"></div>
                </div>
            </div>
        </div>

        <div class="sub-nav-group" data-home-group="dms">
            <div class="group-header">
                <h4 class="section-label">DIRECT MESSAGES</h4>
                <a href="dms" class="view-all">VIEW ALL</a>
            </div>
            <div class="mini-list" id="homeSidebarDms">
                <?php if (empty($home_sidebar_dms)): ?>
                    <div class="mini-item no-hover">
                        <div class="mini-info">
                            <span class="mini-name">No conversations yet</span>
                            <span class="mini-preview">Start a DM from People</span>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($home_sidebar_dms as $dm): ?>
                        <a href="dms/<?php echo htmlspecialchars($dm['id']); ?>" class="mini-item">
                            <div class="mini-avatar">
                                <img src="<?php echo htmlspecialchars($dm['avatar']); ?>"
                                    alt="<?php echo htmlspecialchars($dm['name']); ?>">
                                <?php if (!empty($dm['is_online'])): ?>
                                    <span class="mini-status online"></span>
                                <?php endif; ?>
                            </div>
                            <div class="mini-info">
                                <span class="mini-name"><?php echo htmlspecialchars($dm['name']); ?></span>
                                <span class="mini-preview"><?php echo htmlspecialchars($dm['preview']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="sub-nav-group" data-home-group="channels">
            <div class="group-header">
                <h4 class="section-label">CHANNELS</h4>
                <a href="browse-channels" class="view-all">EXPLORE</a>
            </div>
            <div class="mini-list" id="homeSidebarChannels">
                <?php if (empty($home_sidebar_channels)): ?>
                    <div class="mini-item no-hover">
                        <div class="mini-info">
                            <span class="mini-name">No channels joined</span>
                            <span class="mini-preview">Browse channels to get started</span>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($home_sidebar_channels as $channel): ?>
                        <a href="channels/<?php echo htmlspecialchars($channel['slug']); ?>" class="mini-item">
                            <div class="mini-icon-box">#</div>
                            <div class="mini-info">
                                <span class="mini-name"><?php echo htmlspecialchars($channel['name']); ?></span>
                                <span class="mini-preview"><?php echo htmlspecialchars($channel['preview']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="sub-nav-group" data-home-group="activity">
            <div class="group-header">
                <h4 class="section-label">RECENT ACTIVITY</h4>
            </div>
            <div class="mini-list" id="homeSidebarActivity">
                <?php if (empty($home_sidebar_activity)): ?>
                    <div class="mini-item no-hover">
                        <div class="mini-info">
                            <span class="mini-name">No recent activity</span>
                            <span class="mini-preview">Updates will appear here</span>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($home_sidebar_activity as $activity): ?>
                        <div class="mini-item no-hover">
                            <div class="mini-symbol <?php echo htmlspecialchars($activity['symbol'] ?? 'bell'); ?>"></div>
                            <div class="mini-info">
                                <span class="mini-name"><?php echo htmlspecialchars($activity['name']); ?></span>
                                <span class="mini-preview">
                                    <?php
                                    echo htmlspecialchars(
                                        !empty($activity['time'])
                                            ? ($activity['preview'] . ' · ' . $activity['time'])
                                            : $activity['preview']
                                    );
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
