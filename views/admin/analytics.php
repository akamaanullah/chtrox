<header class="content-header">
    <div class="greeting-area">
        <div class="greeting-icon">
            <i data-lucide="bar-chart-2"></i>
        </div>
        <div class="greeting-text">
            <h1>Analytics & Insights</h1>
            <p class="date">Real-time data on workspace engagement and growth.</p>
        </div>
    </div>
    <div class="header-actions">
        <button class="btn-outline"><i data-lucide="download"></i> Export Report</button>
    </div>
</header>

<!-- Analytics KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Total Messages</span>
            <span class="stat-badge positive">Live</span>
        </div>
        <span class="stat-value"><?php echo $total_messages; ?></span>
        <div class="stat-line green"></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Monthly Active Users</span>
            <span class="stat-badge positive">30d</span>
        </div>
        <span class="stat-value"><?php echo $active_users; ?></span>
        <div class="stat-line blue"></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Storage Used</span>
            <span class="stat-badge neutral">10GB max</span>
        </div>
        <span class="stat-value"><?php echo $storage_label; ?></span>
        <div class="stat-line orange"></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Average Response Time</span>
            <span class="stat-badge positive">-12m</span>
        </div>
        <span class="stat-value">2.4m</span>
        <div class="stat-line red"></div>
    </div>
</div>

<div class="analytics-grid">
    <!-- Message Trend Chart -->
    <div class="chart-container main-trend">
        <div class="chart-header">
            <h3>Message Volume Trend</h3>
            <p>Weekly activity overview</p>
        </div>
        <div id="messageTrendChart"></div>
    </div>

    <!-- Engagement Distribution -->
    <div class="chart-container engagement-donut">
        <div class="chart-header">
            <h3>Engagement Source</h3>
            <p>Channels vs DMs</p>
        </div>
        <div id="engagementChart"></div>
    </div>
</div>

<div class="analytics-bottom-grid">
    <!-- Peak Hours Chart -->
    <div class="chart-container peak-hours">
        <div class="chart-header">
            <h3>Peak Activity Hours</h3>
            <p>Based on pings and replies</p>
        </div>
        <div id="peakHoursChart"></div>
    </div>

    <!-- Member Growth Chart -->
    <div class="chart-container member-growth">
        <div class="chart-header">
            <h3>Member Growth</h3>
            <p>New workspace joins (Last 6 Months)</p>
        </div>
        <div id="memberGrowthChart"></div>
    </div>
</div>

<div class="analytics-details-grid" style="margin-top: 24px; margin-bottom: 40px;">
    <div class="data-card file-analytics">
        <div class="card-header">
            <h3>Storage Breakdown</h3>
            <p style="font-size: 13px; color: var(--text-slate);">Detailed space utilization</p>
        </div>
        <div id="fileTypeChart" style="margin: auto 0;"></div>

    </div>

    <!-- Pinned Activity -->
    <div class="data-card pinning-insights">
        <div class="card-header">
            <h3>Pinned Trends</h3>
            <i data-lucide="pin" class="header-icon"></i>
        </div>
        <div class="pin-content-wrapper" style="margin: auto 0;">
            <div id="pinTrendChart" style="height: 200px;"></div>

            <div class="pin-highlights-grid" style="margin-top: 20px;">
                <div class="p-highlight-box">
                    <span class="h-val"><?php echo $total_pins; ?></span>
                    <span class="h-lbl">TOTAL PINS</span>
                </div>
                <div class="p-highlight-box">
                    <span class="h-val"><?php echo $priority_level; ?></span>
                    <span class="h-lbl">PRIORITY LVL</span>
                </div>
                <div class="p-highlight-box">
                    <span class="h-val"><?php echo $pins_per_day; ?></span>
                    <span class="h-lbl">PINS / DAY</span>
                </div>
                <div class="p-highlight-box">
                    <span class="h-val"><?php echo $interaction_pct; ?></span>
                    <span class="h-lbl">INTERACTION</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performing Channels (Moved here) -->
    <div class="data-card top-channels">
        <div class="card-header">
            <h3>Top Active Channels</h3>
            <a href="<?php echo \App\Core\View::url('admin/channels'); ?>" class="view-all">View All</a>
        </div>
        <div class="data-list">
            <?php if (empty($top_channels)): ?>
                <div class="data-item" style="justify-content: center; padding: 24px; color: var(--slate-400);">
                    No activity recorded yet
                </div>
            <?php else: ?>
                <?php $rank = 1; foreach ($top_channels as $tc): ?>
                    <div class="data-item">
                        <div class="item-rank"><?php echo $rank++; ?></div>
                        <div class="item-info">
                            <span class="item-name">#<?php echo \App\Core\View::e($tc['name']); ?></span>
                            <span class="item-meta"><?php echo number_format($tc['message_count']); ?> messages</span>
                        </div>
                        <div class="item-trend positive"><i data-lucide="trending-up"></i></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
