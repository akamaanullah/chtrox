<div class="content-inner">
    <div class="browse-channels-header">
        <div class="bch-left">
            <span class="label-tiny text-primary" style="letter-spacing: 2px;">BROWSE & JOIN</span>
            <h1>All Channels</h1>
            <p>All workspace channels are listed here. Click Join on any channel you want to be part of.</p>
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

    <!-- All Channels List -->
    <div class="all-channels-list" id="allChannelsList">
        <!-- Channel Row 1 -->
        <div class="channel-row">
            <div class="channel-row-icon">
                <i data-lucide="hash" size="20" style="color: var(--indigo-600);"></i>
            </div>
            <div class="channel-row-info">
                <h3>#general</h3>
                <span class="channel-meta">Company-wide updates, announcements &amp; casual chat · 24 members</span>
            </div>
            <button class="btn-join">Join</button>
        </div>

        <!-- Channel Row 2 -->
        <div class="channel-row">
            <div class="channel-row-icon">
                <i data-lucide="code" size="20" style="color: var(--indigo-600);"></i>
            </div>
            <div class="channel-row-info">
                <h3>#development-announcements</h3>
                <span class="channel-meta">Releases, deployments &amp; dev updates · 12 members</span>
            </div>
            <button class="btn-join">Join</button>
        </div>

        <!-- Channel Row 3 -->
        <div class="channel-row">
            <div class="channel-row-icon">
                <i data-lucide="palette" size="20" style="color: var(--indigo-600);"></i>
            </div>
            <div class="channel-row-info">
                <h3>#design-huddle</h3>
                <span class="channel-meta">Design reviews, Figma links &amp; creative feedback · 8 members</span>
            </div>
            <button class="btn-join">Join</button>
        </div>

        <!-- Channel Row 4 -->
        <div class="channel-row">
            <div class="channel-row-icon">
                <i data-lucide="image" size="20" style="color: var(--indigo-600);"></i>
            </div>
            <div class="channel-row-info">
                <h3>#branding-assets</h3>
                <span class="channel-meta">Logos, guidelines &amp; brand resources · 6 members</span>
            </div>
            <button class="btn-join">Join</button>
        </div>

        <!-- Channel Row 5 -->
        <div class="channel-row">
            <div class="channel-row-icon">
                <i data-lucide="briefcase" size="20" style="color: var(--indigo-600);"></i>
            </div>
            <div class="channel-row-info">
                <h3>#project-nexus</h3>
                <span class="channel-meta">Nexus Interface Redesign &amp; cross-team sync · 10 members</span>
            </div>
            <button class="btn-join">Join</button>
        </div>

        <!-- Channel Row 6 -->
        <div class="channel-row">
            <div class="channel-row-icon">
                <i data-lucide="message-circle" size="20" style="color: var(--indigo-600);"></i>
            </div>
            <div class="channel-row-info">
                <h3>#random</h3>
                <span class="channel-meta">Off-topic, memes &amp; water cooler · 18 members</span>
            </div>
            <button class="btn-join">Join</button>
        </div>

        <!-- Channel Row 7 - Already joined (optional state) -->
        <div class="channel-row joined">
            <div class="channel-row-icon">
                <i data-lucide="shield" size="20" style="color: var(--indigo-600);"></i>
            </div>
            <div class="channel-row-info">
                <h3>#security-alerts</h3>
                <span class="channel-meta">Security updates &amp; incident channel · 5 members</span>
            </div>
            <span class="btn-joined">Joined</span>
        </div>
    </div>
</div>