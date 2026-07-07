/**
 * Home dashboard live updates — refreshes stats/sidebar from API on WebSocket events.
 */
(function (global) {
    'use strict';

    var refreshTimer = null;
    var inflight = null;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function isHomeVisible() {
        return !!(document.getElementById('homeUnreadCount') || document.getElementById('homeInboxTitle'));
    }

    function scheduleRefresh() {
        if (!isHomeVisible()) return;
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(refreshHomeSummary, 350);
    }

    function setNavBadge(tabId, count) {
        var badge = document.getElementById('navBadge-' + tabId);
        if (!badge) return;
        count = Math.max(0, parseInt(count, 10) || 0);
        if (count > 0) {
            badge.textContent = String(count);
            badge.style.display = '';
        } else {
            badge.textContent = '';
            badge.style.display = 'none';
        }
    }

    function renderDmList(items) {
        var container = document.getElementById('homeSidebarDms');
        if (!container) return;

        if (!items || !items.length) {
            container.innerHTML = '<div class="mini-item no-hover"><div class="mini-info">' +
                '<span class="mini-name">No conversations yet</span>' +
                '<span class="mini-preview">Start a DM from People</span></div></div>';
            return;
        }

        container.innerHTML = items.map(function (dm) {
            var online = dm.is_online
                ? '<span class="mini-status online"></span>'
                : '';
            return '<a href="dms/' + escapeHtml(dm.id) + '" class="mini-item">' +
                '<div class="mini-avatar">' +
                '<img src="' + escapeHtml(dm.avatar) + '" alt="' + escapeHtml(dm.name) + '">' +
                online +
                '</div>' +
                '<div class="mini-info">' +
                '<span class="mini-name">' + escapeHtml(dm.name) + '</span>' +
                '<span class="mini-preview">' + escapeHtml(dm.preview) + '</span>' +
                '</div></a>';
        }).join('');
    }

    function renderChannelList(items) {
        var container = document.getElementById('homeSidebarChannels');
        if (!container) return;

        if (!items || !items.length) {
            container.innerHTML = '<div class="mini-item no-hover"><div class="mini-info">' +
                '<span class="mini-name">No channels joined</span>' +
                '<span class="mini-preview">Browse channels to get started</span></div></div>';
            return;
        }

        container.innerHTML = items.map(function (channel) {
            return '<a href="channels/' + escapeHtml(channel.slug) + '" class="mini-item">' +
                '<div class="mini-icon-box">#</div>' +
                '<div class="mini-info">' +
                '<span class="mini-name">' + escapeHtml(channel.name) + '</span>' +
                '<span class="mini-preview">' + escapeHtml(channel.preview) + '</span>' +
                '</div></a>';
        }).join('');
    }

    function renderActivityList(items) {
        var container = document.getElementById('homeSidebarActivity');
        if (!container) return;

        if (!items || !items.length) {
            container.innerHTML = '<div class="mini-item no-hover"><div class="mini-info">' +
                '<span class="mini-name">No recent activity</span>' +
                '<span class="mini-preview">Updates will appear here</span></div></div>';
            return;
        }

        container.innerHTML = items.map(function (activity) {
            var preview = activity.preview || '';
            if (activity.time) {
                preview = preview + ' · ' + activity.time;
            }
            return '<div class="mini-item no-hover">' +
                '<div class="mini-symbol ' + escapeHtml(activity.symbol || 'bell') + '"></div>' +
                '<div class="mini-info">' +
                '<span class="mini-name">' + escapeHtml(activity.name) + '</span>' +
                '<span class="mini-preview">' + escapeHtml(preview) + '</span>' +
                '</div></div>';
        }).join('');
    }

    function renderAnnouncements(items) {
        var grid = document.getElementById('homeAnnouncementsGrid');
        if (!grid) return;

        if (!items || !items.length) {
            grid.innerHTML = [
                '<div class="ann-card ann-card--empty" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 24px; text-align: center; border: 1.5px dashed var(--border-color, #e2e8f0); background: #f8fafc; border-radius: 16px; min-height: 180px; width: 100%; gap: 12px; box-sizing: border-box; grid-column: 1 / -1;">',
                '    <div style="background: var(--indigo-50, rgba(99, 102, 241, 0.06)); color: var(--indigo-600, #4f46e5); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 4px;">',
                '        <i data-lucide="megaphone-off" size="24"></i>',
                '    </div>',
                '    <p style="margin: 0; color: var(--text-primary, #0f172a); font-size: 15px; font-weight: 600; font-family: inherit;">No Active Announcements</p>',
                '    <span style="color: var(--text-muted, #64748b); font-size: 13px; font-family: inherit;">Important updates or company events will be displayed here.</span>',
                '</div>'
            ].join('\n');
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
            return;
        }

        grid.innerHTML = items.map(function (ann) {
            return '<div class="ann-card" data-announcement-id="' + escapeHtml(String(ann.id || '')) + '">'
                + '<div class="ann-card-top">'
                + '<span class="ann-card-icon" aria-hidden="true">' + (ann.icon || '📢') + '</span>'
                + '<span class="tag ' + escapeHtml(ann.tag_class || 'update') + '">' + escapeHtml(ann.tag || 'UPDATE') + '</span>'
                + '</div>'
                + '<h3>' + escapeHtml(ann.title || '') + '</h3>'
                + '<p>' + escapeHtml(ann.body || '') + '</p>'
                + '<div class="ann-footer">'
                + '<span class="ann-date">' + escapeHtml(ann.date || '') + '</span>'
                + '<button type="button" class="details-btn js-ann-details"'
                + ' data-title="' + escapeHtml(ann.title || '') + '"'
                + ' data-body="' + escapeHtml(ann.body || '') + '"'
                + ' data-tag="' + escapeHtml(ann.tag || 'UPDATE') + '"'
                + ' data-tag-class="' + escapeHtml(ann.tag_class || 'update') + '"'
                + ' data-posted-by="' + escapeHtml(ann.posted_by || 'Workspace Admin') + '"'
                + ' data-posted-at="' + escapeHtml(ann.posted_at || ann.date || '') + '">Details</button>'
                + '</div></div>';
        }).join('');

        grid.querySelectorAll('.js-ann-details').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (typeof openAnnouncementModal === 'function') {
                    openAnnouncementModal(btn);
                }
            });
        });
    }

    function formatHomeDateLabel() {
        try {
            return new Date().toLocaleDateString(undefined, {
                weekday: 'long',
                month: 'long',
                day: 'numeric'
            });
        } catch (e) {
            return '';
        }
    }

    function updateHomeDateLabel() {
        var dateEl = document.getElementById('homeDateLabel');
        if (!dateEl) return;
        var label = formatHomeDateLabel();
        if (label) dateEl.textContent = label;
    }

    setInterval(updateHomeDateLabel, 60000);

    function applyHomeSummary(summary) {
        if (!summary) return;

        var unreadEl = document.getElementById('homeUnreadCount');
        if (unreadEl) unreadEl.textContent = String(summary.unread_count || 0);

        var onlineEl = document.getElementById('homeOnlineCount');
        if (onlineEl) onlineEl.textContent = String(summary.online_count || 0);

        var onlineFooter = document.getElementById('homeOnlineFooter');
        if (onlineFooter) {
            onlineFooter.textContent = 'of ' + String(summary.total_members || 0) + ' members';
        }

        if (summary.inbox) {
            var badge = document.getElementById('homeInboxBadge');
            var title = document.getElementById('homeInboxTitle');
            var progressText = document.getElementById('homeInboxProgressText');
            var progressFill = document.getElementById('homeInboxProgressFill');
            var progress = Math.max(0, Math.min(100, parseInt(summary.inbox.progress, 10) || 0));
            var progressLabel = summary.inbox.progress_label || 'Inbox clear';

            if (badge) badge.textContent = summary.inbox.badge || 'LIVE';
            if (title) title.textContent = summary.inbox.title || '';
            if (progressText) progressText.textContent = progressLabel + ' ' + progress + '%';
            if (progressFill) progressFill.style.width = progress + '%';
        }

        renderDmList(summary.sidebar_dms);
        renderChannelList(summary.sidebar_channels);
        renderActivityList(summary.sidebar_activity);
        renderAnnouncements(summary.announcements);

        if (summary.date_label) {
            var dateEl = document.getElementById('homeDateLabel');
            if (dateEl) dateEl.textContent = summary.date_label;
        }

        if (summary.nav_badges) {
            setNavBadge('dms', summary.nav_badges.dms);
            setNavBadge('channels', summary.nav_badges.channels);
            setNavBadge('activity', summary.nav_badges.activity);
        }
    }

    function refreshHomeSummary() {
        if (!isHomeVisible()) return;

        var base = (global.CHATROX && global.CHATROX.baseUrl) ? global.CHATROX.baseUrl : '';
        if (inflight) inflight.abort();
        inflight = new AbortController();

        fetch((global.CHATROX.apiUrl || '') + '/home/summary', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: inflight.signal
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (data && data.success && data.summary) {
                applyHomeSummary(data.summary);
            }
        }).catch(function (err) {
            if (err && err.name === 'AbortError') return;
            console.warn('[ChatRoxHomeLive]', err);
        }).finally(function () {
            inflight = null;
        });
    }

    [
        'chatrox:new_message',
        'chatrox:message_reaction',
        'chatrox:conversation_opened',
        'chatrox:conversation_read',
        'chatrox:presence_change',
        'chatrox:ws_connected'
    ].forEach(function (eventName) {
        document.addEventListener(eventName, scheduleRefresh);
    });

    document.addEventListener('chatrox:page_load', function (e) {
        var detail = e.detail || {};
        if (detail.active_tab === 'home') {
            scheduleRefresh();
        }
    });

    global.ChatRoxHomeLive = {
        refresh: refreshHomeSummary,
        scheduleRefresh: scheduleRefresh,
        apply: applyHomeSummary
    };
})(typeof window !== 'undefined' ? window : this);
