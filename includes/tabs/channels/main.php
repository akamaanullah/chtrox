<?php
if (!empty($_GET['id'])) {
    include __DIR__ . '/chat.php';
    return;
}
?>
<div class="content-inner channels-hero-bg">
    <!-- Hero Section -->
    <div class="channels-hero">
        <div class="hero-hash-box">
            <i data-lucide="hash" size="64"></i>
            <span class="status-online-glow"></span>
        </div>
        <h1>Collective Intelligence</h1>
        <p>Channels are where the entire Chatrox team aligns, discusses <br> projects, and shares breakthroughs.</p>
    </div>

    <!-- Cards Grid -->
    <div class="channels-grid">
        <!-- Card 1 -->
        <a href="channels/general" class="channel-card" style="text-decoration: none; color: inherit;">
            <div class="ch-icon-pill">
                <i data-lucide="hash" size="18" style="color: var(--indigo-600);"></i>
            </div>
            <h3>#general</h3>
            <span class="ch-stat">3 TEAM MEMBERS ACTIVE</span>
            <div class="ch-footer">
                <span class="jump-in">JUMP IN</span>
                <i data-lucide="chevron-right" size="14"></i>
            </div>
            <div class="bg-hash-overlay">#</div>
        </a>

        <!-- Card 2 -->
        <a href="channels/development-announcements" class="channel-card"
            style="text-decoration: none; color: inherit;">
            <div class="ch-icon-pill">
                <i data-lucide="code" size="18" style="color: var(--indigo-600);"></i>
            </div>
            <h3>#development-announcements</h3>
            <span class="ch-stat">2 TEAM MEMBERS ACTIVE</span>
            <div class="ch-footer">
                <span class="jump-in">JUMP IN</span>
                <i data-lucide="chevron-right" size="14"></i>
            </div>
            <div class="bg-hash-overlay">#</div>
        </a>

        <!-- Card 3 - New Channel (opens create modal) -->
        <div class="channel-card dark js-open-create-channel-modal" role="button" tabindex="0"
            title="Create new channel">
            <div class="ch-icon-pill dark">
                <i data-lucide="plus" size="18" style="color: white;"></i>
            </div>
            <h3>New Channel</h3>
            <span class="ch-stat">SCALE THE CULTURE</span>
            <div class="bg-hash-overlay">#</div>
        </div>
    </div>

</div>