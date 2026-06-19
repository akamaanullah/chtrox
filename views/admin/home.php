<?php use App\Core\View; ?>
<header class="content-header">
    <div class="greeting-area">
        <div class="greeting-icon">
            <i data-lucide="home"></i>
        </div>
        <div class="greeting-text">
            <h1>Good evening, <?php echo View::e($greeting['user_name']); ?></h1>
            <p class="date"><?php echo View::e($greeting['date_label']); ?></p>
        </div>
    </div>
    <a href="<?php echo \App\Core\View::url('home'); ?>" class="go-to-chat">
        <i data-lucide="message-square-text"></i>
        <span>Go to Chat</span>
    </a>
</header>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">MEMBERS</span>
        <span class="stat-value"><?php echo View::e((string) $stats['members']); ?></span>
        <div class="stat-line green"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">CHANNELS</span>
        <span class="stat-value"><?php echo View::e((string) $stats['channels']); ?></span>
        <div class="stat-line blue"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">ONLINE NOW</span>
        <span class="stat-value"><?php echo View::e((string) $stats['online']); ?></span>
        <div class="stat-line orange"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">UNREAD</span>
        <span class="stat-value"><?php echo View::e((string) $stats['unread']); ?></span>
        <div class="stat-line red"></div>
    </div>
</div>

<h3 class="section-heading" style="margin-top: 40px; margin-bottom: 24px;">QUICK ACCESS</h3>

<div class="access-grid">
    <!-- Row 1 -->
    <div class="access-card">
        <div class="card-icon"><i data-lucide="user"></i></div>
        <div class="card-content">
            <h3>Account Settings</h3>
            <p>Edit your profile, update username and password, and manage account settings.</p>
        </div>
        <i data-lucide="chevron-right" class="arrow"></i>
    </div>

    <div class="access-card">
        <div class="card-icon"><i data-lucide="users"></i></div>
        <div class="card-content">
            <h3>Manage Your Workspace</h3>
            <p>Create and manage announcements for your workspace members.</p>
            <span class="card-badge"><?php echo View::e((string) $stats['members']); ?> members</span>
        </div>
        <i data-lucide="chevron-right" class="arrow"></i>
    </div>

    <div class="access-card">
        <div class="card-icon"><i data-lucide="bar-chart-3"></i></div>
        <div class="card-content">
            <h3>Analytics</h3>
            <p>View stats for your workspace, including activity, files, and integrations.</p>
            <span class="card-badge pulse">Live</span>
        </div>
        <i data-lucide="chevron-right" class="arrow"></i>
    </div>

    <!-- Row 2 -->
    <div class="access-card">
        <div class="card-icon highlight"><i data-lucide="hash"></i></div>
        <div class="card-content">
            <h3>Channels</h3>
            <p>View and manage workspace channels. Create, edit, or archive channels.</p>
            <span class="card-badge"><?php echo View::e((string) $stats['channels']); ?> active</span>
        </div>
        <i data-lucide="chevron-right" class="arrow"></i>
    </div>

    <div class="access-card">
        <div class="card-icon"><i data-lucide="megaphone"></i></div>
        <div class="card-content">
            <h3>Announcements</h3>
            <p>Create and manage announcements for your workspace members.</p>
        </div>
        <i data-lucide="chevron-right" class="arrow"></i>
    </div>

    <div class="access-card">
        <div class="card-icon"><i data-lucide="activity"></i></div>
        <div class="card-content">
            <h3>Recent Activities</h3>
            <p>View all recent activities and changes in your workspace.</p>
            <span class="card-badge"><?php echo View::e((string) $stats['activity_new']); ?> new</span>
        </div>
        <i data-lucide="chevron-right" class="arrow"></i>
    </div>

    <!-- Row 3 -->
    <div class="access-card">
        <div class="card-icon"><i data-lucide="rss"></i></div>
        <div class="card-content">
            <h3>Team Pulse</h3>
            <p>See who's online and active in your workspace right now.</p>
            <span class="card-badge pulse"><?php echo View::e((string) $stats['online']); ?> online</span>
        </div>
        <i data-lucide="chevron-right" class="arrow"></i>
    </div>

    <div class="access-card">
        <div class="card-icon"><i data-lucide="bell"></i></div>
        <div class="card-content">
            <h3>Pending Pings</h3>
            <p>Unread messages and pending notifications across channels and DMs.</p>
            <span class="card-badge red"><?php echo View::e((string) $stats['unread']); ?> unread</span>
        </div>
        <i data-lucide="chevron-right" class="arrow"></i>
    </div>

    <div class="access-card">
        <div class="card-icon"><i data-lucide="folder"></i></div>
        <div class="card-content">
            <h3>Files & Media</h3>
            <p>Files and media shared in your workspace. Storage and usage overview.</p>
            <span class="card-badge"><?php echo View::e((string) $stats['files']); ?> files</span>
        </div>
        <i data-lucide="chevron-right" class="arrow"></i>
    </div>
</div>
