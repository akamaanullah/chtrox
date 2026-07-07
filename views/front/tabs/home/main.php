<div class="content-inner">
    <div class="dash-top">
        <div class="greeting">
            <div class="office-tag"><?php echo htmlspecialchars(strtoupper($home_greeting['workspace_name'] ?? 'Office HQ') . ' DASHBOARD'); ?></div>
            <h1>Welcome, <span class="text-primary"><?php echo htmlspecialchars($home_greeting['user_name']); ?></span> 👋</h1>
            <p id="homeDateLabel"><?php echo htmlspecialchars($home_greeting['date_label']); ?></p>
        </div>
    </div>

    <div class="dash-grid">
        <?php foreach ($home_stats as $stat): ?>
            <div class="stat-card <?php echo htmlspecialchars($stat['variant']); ?>"<?php echo !empty($stat['stat_id']) ? ' id="' . htmlspecialchars($stat['stat_id']) . '"' : ''; ?>>
                <?php if (!empty($stat['overlay_icon'])): ?>
                    <div class="stat-bg-overlay" aria-hidden="true">
                        <i data-lucide="<?php echo htmlspecialchars($stat['overlay_icon']); ?>"></i>
                    </div>
                <?php endif; ?>
                <span class="stat-label"><?php echo htmlspecialchars($stat['label']); ?></span>
                <?php if (!empty($stat['is_timer'])): ?>
                    <span class="stat-value" id="focusTimerValue"><?php echo htmlspecialchars($stat['value']); ?></span>
                    <div class="card-actions">
                        <button type="button" class="action-btn primary" id="focusTimerStart">START</button>
                        <button type="button" class="action-btn" id="focusTimerReset">RESET</button>
                    </div>
                <?php else: ?>
                    <span class="stat-value"<?php echo !empty($stat['value_id']) ? ' id="' . htmlspecialchars($stat['value_id']) . '"' : ''; ?>><?php echo htmlspecialchars($stat['value']); ?></span>
                    <span class="stat-footer <?php echo htmlspecialchars($stat['footer_class'] ?? ''); ?>"<?php echo !empty($stat['footer_id']) ? ' id="' . htmlspecialchars($stat['footer_id']) . '"' : ''; ?>>
                        <?php echo htmlspecialchars($stat['footer']); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="quick-connect-card">
            <span class="stat-label">Quick Connect</span>
            <a href="people" class="qc-item"
                style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center;">
                <div class="qc-content">
                    <div class="qc-icon-box"><i data-lucide="message-square" size="14"></i></div>
                    <span>NEW DM</span>
                </div>
                <i data-lucide="chevron-right" size="14" class="chevron"></i>
            </a>
            <a href="browse-channels" class="qc-item"
                style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center;">
                <div class="qc-content">
                    <div class="qc-icon-box"><i data-lucide="hash" size="14"></i></div>
                    <span>JOIN CHANNEL</span>
                </div>
                <i data-lucide="chevron-right" size="14" class="chevron"></i>
            </a>
        </div>
    </div>

    <div class="bottom-grid">
        <div class="global-search-card" id="homeGlobalSearch">
            <div class="search-card-header">
                <div class="search-icon-box"><i data-lucide="search" size="20"></i></div>
                <h3>Global Workspace Search</h3>
            </div>
            <div class="search-input-wrap">
                <input type="text" placeholder="Search messages, files, or people across ChatRox..."
                    class="dash-search-input" id="homeGlobalSearchInput" autocomplete="off" aria-label="Global workspace search">
                <button type="button" class="search-submit" id="homeGlobalSearchSubmit" aria-label="Search">
                    <i data-lucide="arrow-right" size="18"></i>
                </button>
            </div>
            <div class="home-search-results" id="homeGlobalSearchResults" hidden></div>
            <div class="quick-tags" id="homeQuickTags">
                <?php foreach ($home_search_tags as $tag): ?>
                    <?php
                    $tagLabel = is_array($tag) ? ($tag['label'] ?? '') : (string)$tag;
                    $tagType = is_array($tag) ? ($tag['type'] ?? 'text') : 'text';
                    $tagQuery = is_array($tag) ? ($tag['query'] ?? $tagLabel) : $tagLabel;
                    $tagSlug = is_array($tag) ? ($tag['slug'] ?? '') : '';
                    $tagUsername = is_array($tag) ? ($tag['username'] ?? '') : '';
                    $tagJoined = is_array($tag) ? !empty($tag['joined']) : true;
                    ?>
                    <button type="button" class="q-tag js-home-quick-tag"
                        data-tag-type="<?php echo htmlspecialchars($tagType); ?>"
                        data-tag-query="<?php echo htmlspecialchars($tagQuery); ?>"
                        data-tag-slug="<?php echo htmlspecialchars($tagSlug); ?>"
                        data-tag-username="<?php echo htmlspecialchars($tagUsername); ?>"
                        data-tag-joined="<?php echo $tagJoined ? '1' : '0'; ?>"><?php echo htmlspecialchars($tagLabel); ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="world-clocks-section">
            <div class="section-header">
                <h3>World Clocks</h3>
                <span class="label-tiny">Global HQ Time</span>
            </div>
            <?php ob_start(); ?>
            <div class="clock-markers" aria-hidden="true">
                <?php for ($marker = 1; $marker <= 12; $marker++): ?>
                    <span class="clock-marker" style="--marker-i: <?php echo $marker; ?>"><?php echo $marker; ?></span>
                <?php endfor; ?>
            </div>
            <?php $clockMarkersHtml = ob_get_clean(); ?>
            <div class="clocks-grid">
                <?php foreach ($home_world_clocks as $clock): ?>
                    <div class="clock-card" data-timezone="<?php echo htmlspecialchars($clock['timezone']); ?>">
                        <div class="clock-face">
                            <?php echo $clockMarkersHtml; ?>
                            <div class="clock-hand hour" id="<?php echo htmlspecialchars($clock['id']); ?>-hour"></div>
                            <div class="clock-hand minute" id="<?php echo htmlspecialchars($clock['id']); ?>-min"></div>
                            <div class="clock-hand second" id="<?php echo htmlspecialchars($clock['id']); ?>-sec"></div>
                            <div class="clock-center"></div>
                        </div>
                        <div class="clock-info">
                            <h4><?php echo htmlspecialchars($clock['label']); ?></h4>
                            <span class="digital-time" id="<?php echo htmlspecialchars($clock['id']); ?>-digital">--:-- --</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="announcements">
        <div class="ann-header">
            <h2>Workspace Announcements</h2>
        </div>
        <div class="ann-grid" id="homeAnnouncementsGrid">
            <?php if (empty($home_announcements)): ?>
                <div class="ann-card ann-card--empty" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 24px; text-align: center; border: 1.5px dashed var(--border-color, #e2e8f0); background: #f8fafc; border-radius: 16px; min-height: 180px; width: 100%; gap: 12px; box-sizing: border-box; grid-column: 1 / -1;">
                    <div style="background: var(--indigo-50, rgba(99, 102, 241, 0.06)); color: var(--indigo-600, #4f46e5); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 4px;">
                        <i data-lucide="megaphone-off" size="24"></i>
                    </div>
                    <p style="margin: 0; color: var(--text-primary, #0f172a); font-size: 15px; font-weight: 600; font-family: inherit;">No Active Announcements</p>
                    <span style="color: var(--text-muted, #64748b); font-size: 13px; font-family: inherit;">Important updates or company events will be displayed here.</span>
                </div>
            <?php else: ?>
                <?php foreach ($home_announcements as $ann): ?>
                    <div class="ann-card" data-announcement-id="<?php echo (int)$ann['id']; ?>">
                        <div class="ann-card-top">
                            <span class="ann-card-icon" aria-hidden="true"><?php echo $ann['icon']; ?></span>
                            <span class="tag <?php echo htmlspecialchars($ann['tag_class']); ?>"><?php echo htmlspecialchars($ann['tag']); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
                        <p><?php echo htmlspecialchars($ann['body']); ?></p>
                        <div class="ann-footer">
                            <span class="ann-date"><?php echo htmlspecialchars($ann['date']); ?></span>
                            <button type="button" class="details-btn js-ann-details"
                                data-title="<?php echo htmlspecialchars($ann['title']); ?>"
                                data-body="<?php echo htmlspecialchars($ann['body']); ?>"
                                data-tag="<?php echo htmlspecialchars($ann['tag']); ?>"
                                data-tag-class="<?php echo htmlspecialchars($ann['tag_class']); ?>"
                                data-posted-by="<?php echo htmlspecialchars($ann['posted_by'] ?? 'Workspace Admin'); ?>"
                                data-posted-at="<?php echo htmlspecialchars($ann['posted_at'] ?? $ann['date']); ?>">Details</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer class="final-footer">
        <div class="footer-brand">
            <h4>CHATROX</h4>
            <p>Built for high-performance distributed teams</p>
        </div>
        <div class="footer-links">
            <a href="javascript:void(0)" onclick="toggleAboutModal()">About</a>
            <a href="javascript:void(0)" onclick="togglePrivacyModal()">Privacy</a>
            <a href="javascript:void(0)" onclick="toggleGuideModal()">Guide</a>
        </div>
    </footer>
</div>

<?php

use App\Core\View;

View::render('partials/home/modals.php');
