<?php
// Get current page name for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="user-profile">
            <div class="avatar">C</div>
            <div class="user-info">
                <span class="user-name">ChatroxAdmin</span>
                <span class="user-role">Admin</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="section-title">ACCOUNT</span>
            <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <i data-lucide="home"></i>
                <span>Home</span>
            </a>
            <a href="profile.php" class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <i data-lucide="user"></i>
                <span>Account & profile</span>
            </a>
            <a href="analytics.php" class="nav-link <?php echo ($current_page == 'analytics.php') ? 'active' : ''; ?>">
                <i data-lucide="bar-chart-2"></i>
                <span>Analytics</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="section-title">ADMINISTRATION</span>
            <a href="members.php" class="nav-link <?php echo ($current_page == 'members.php') ? 'active' : ''; ?>">
                <i data-lucide="users"></i>
                <span>Manage members</span>
            </a>
            <a href="channels.php" class="nav-link <?php echo ($current_page == 'channels.php') ? 'active' : ''; ?>">
                <i data-lucide="hash"></i>
                <span>Channels</span>
            </a>
            <a href="announcements.php" class="nav-link <?php echo ($current_page == 'announcements.php') ? 'active' : ''; ?>">
                <i data-lucide="megaphone"></i>
                <span>Announcements</span>
            </a>
            <a href="files.php" class="nav-link <?php echo ($current_page == 'files.php') ? 'active' : ''; ?>">
                <i data-lucide="file-text"></i>
                <span>Files & Media</span>
            </a>
            <a href="activity.php" class="nav-link <?php echo ($current_page == 'activity.php') ? 'active' : ''; ?>">
                <i data-lucide="activity"></i>
                <span>Recent Activities</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <a href="../" class="nav-link logout">
            <i data-lucide="log-out"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>