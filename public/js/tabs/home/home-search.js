/**
 * Home dashboard search — global workspace search, sidebar filter, quick tags.
 */
(function (global) {
    'use strict';

    var searchAbort = null;
    var searchTimer = null;
    var sessionAbort = null;
    var lastGlobalSearchQuery = '';

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function baseUrl() {
        return (global.CHATROX && global.CHATROX.baseUrl) ? global.CHATROX.baseUrl : '';
    }

    function buildAppUrl(path) {
        var base = baseUrl().replace(/\/+$/, '');
        path = String(path || '').replace(/^\/+/, '');
        return base + (path ? '/' + path : '/');
    }

    function navigateTo(path) {
        if (global.ChatRoxRouter && typeof global.ChatRoxRouter.navigate === 'function') {
            global.ChatRoxRouter.navigate(path);
            return;
        }
        global.location.href = buildAppUrl(path);
    }

    function renderResultSection(title, items, query) {
        if (!items || !items.length) return '';
        var html = '<div class="home-search-section"><div class="home-search-section-title">' + escapeHtml(title) + '</div>';
        items.forEach(function (item) {
            var url = item.url || '#';
            var isExternalFile = url.indexOf('files/download/') !== -1;
            var isMessage = item.type === 'message';

            if (isMessage) {
                html += '<button type="button" class="home-search-result home-search-result--message"'
                    + ' data-message-id="' + escapeHtml(String(item.message_id || item.id || '')) + '"'
                    + ' data-conversation-id="' + escapeHtml(String(item.conversation_id || '')) + '"'
                    + ' data-spa-path="' + escapeHtml(url) + '">'
                    + '<span class="home-search-result-title">' + escapeHtml(item.title) + '</span>'
                    + '<span class="home-search-result-sub">' + escapeHtml(item.subtitle || '') + '</span>'
                    + (item.time ? '<span class="home-search-result-time">' + escapeHtml(item.time) + '</span>' : '')
                    + '</button>';
                return;
            }

            html += '<a href="' + escapeHtml(buildAppUrl(isExternalFile ? url : url)) + '" class="home-search-result' + (isExternalFile ? ' home-search-result--file' : '') + '"'
                + (isExternalFile ? ' target="_blank" rel="noopener"' : ' data-spa-path="' + escapeHtml(url) + '"')
                + '>'
                + '<span class="home-search-result-title">' + escapeHtml(item.title) + '</span>'
                + '<span class="home-search-result-sub">' + escapeHtml(item.subtitle || '') + '</span>'
                + (item.time ? '<span class="home-search-result-time">' + escapeHtml(item.time) + '</span>' : '')
                + '</a>';
        });
        html += '</div>';
        return html;
    }

    function renderSearchResults(container, results, query) {
        if (!container) return;

        var total = (results.people || []).length
            + (results.channels || []).length
            + (results.messages || []).length
            + (results.files || []).length;

        if (total === 0) {
            container.innerHTML = '<div class="home-search-empty">No results for "' + escapeHtml(query) + '"</div>';
            container.hidden = false;
            return;
        }

        container.innerHTML =
            renderResultSection('People', results.people, query)
            + renderResultSection('Channels', results.channels, query)
            + renderResultSection('Messages', results.messages, query)
            + renderResultSection('Files', results.files, query);
        container.hidden = false;

        container.querySelectorAll('.home-search-result--message').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var messageId = parseInt(btn.getAttribute('data-message-id') || '0', 10);
                var conversationId = parseInt(btn.getAttribute('data-conversation-id') || '0', 10);
                var path = btn.getAttribute('data-spa-path');
                if (!messageId || !path) return;

                if (global.ChatRoxMessageFocus) {
                    global.ChatRoxMessageFocus.setPending({
                        message_id: messageId,
                        conversation_id: conversationId,
                        query: query
                    });
                }

                var currentPath = global.ChatRoxRouter ? global.ChatRoxRouter.currentPath() : '';
                var targetPath = global.ChatRoxRouter ? global.ChatRoxRouter.normalizePath(path) : '';

                if (currentPath && currentPath === targetPath) {
                    document.dispatchEvent(new CustomEvent('chatrox:apply_message_focus'));
                } else {
                    navigateTo(path);
                }

                container.hidden = true;
                container.innerHTML = '';
                var globalInput = document.getElementById('homeGlobalSearchInput');
                if (globalInput) globalInput.blur();
            });
        });

        container.querySelectorAll('a[data-spa-path]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                if (!global.ChatRoxRouter) return;
                e.preventDefault();
                var path = link.getAttribute('data-spa-path');
                if (path) navigateTo(path);
            });
        });
    }

    function runGlobalSearch(query) {
        var container = document.getElementById('homeGlobalSearchResults');
        if (!container) return;

        query = String(query || '').trim();
        lastGlobalSearchQuery = query;
        if (query.length < 2) {
            container.hidden = true;
            container.innerHTML = '';
            return;
        }

        if (searchAbort) searchAbort.abort();
        searchAbort = new AbortController();

        container.hidden = false;
        container.innerHTML = '<div class="home-search-loading">Searching...</div>';

        fetch((window.CHATROX.apiUrl || '') + '/search?q=' + encodeURIComponent(query) + '&limit=6', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: searchAbort.signal
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (!data || !data.success) {
                container.innerHTML = '<div class="home-search-empty">Search failed. Try again.</div>';
                return;
            }
            renderSearchResults(container, data.results || {}, query);
        }).catch(function (err) {
            if (err && err.name === 'AbortError') return;
            container.innerHTML = '<div class="home-search-empty">Search failed. Try again.</div>';
        });
    }

    function filterHomeSubNav(query) {
        var q = String(query || '').trim().toLowerCase();
        var content = document.getElementById('homeSubNavContent');
        if (!content) return;

        var groups = content.querySelectorAll('[data-home-group]');
        groups.forEach(function (group) {
            if (group.id === 'homeInboxCard') {
                var inboxText = [
                    document.getElementById('homeInboxTitle'),
                    document.getElementById('homeInboxBadge')
                ].map(function (el) { return el ? el.textContent : ''; }).join(' ').toLowerCase();
                group.classList.toggle('home-subnav-hidden', q !== '' && inboxText.indexOf(q) === -1);
                return;
            }

            var items = group.querySelectorAll('.mini-item');
            var visible = 0;
            items.forEach(function (item) {
                var text = item.textContent.toLowerCase();
                var match = !q || text.indexOf(q) !== -1;
                item.classList.toggle('home-subnav-hidden', !match);
                if (match) visible++;
            });
            group.classList.toggle('home-subnav-hidden', q !== '' && visible === 0);
        });
    }

    function handleQuickTag(tagBtn) {
        var type = tagBtn.getAttribute('data-tag-type') || '';
        var query = tagBtn.getAttribute('data-tag-query') || '';
        var slug = tagBtn.getAttribute('data-tag-slug') || '';
        var username = tagBtn.getAttribute('data-tag-username') || '';
        var globalInput = document.getElementById('homeGlobalSearchInput');

        if (type === 'person' && username) {
            navigateTo('dms/' + username);
            return;
        }

        if (type === 'channel' && slug) {
            var joined = tagBtn.getAttribute('data-tag-joined') === '1';
            navigateTo(joined ? 'channels/' + slug : 'browse-channels');
            return;
        }

        if (globalInput) {
            globalInput.value = query;
            runGlobalSearch(query);
            globalInput.focus();
        }
    }

    function initHomeSearch() {
        if (sessionAbort) sessionAbort.abort();
        sessionAbort = new AbortController();
        var signal = sessionAbort.signal;

        var globalInput = document.getElementById('homeGlobalSearchInput');
        var globalSubmit = document.getElementById('homeGlobalSearchSubmit');
        var subNavInput = document.getElementById('homeSubNavSearch');
        var results = document.getElementById('homeGlobalSearchResults');

        if (globalInput) {
            globalInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                var q = globalInput.value;
                searchTimer = setTimeout(function () {
                    runGlobalSearch(q);
                }, 300);
            }, { signal: signal });

            globalInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    runGlobalSearch(globalInput.value);
                }
                if (e.key === 'Escape') {
                    globalInput.value = '';
                    if (results) {
                        results.hidden = true;
                        results.innerHTML = '';
                    }
                }
            }, { signal: signal });
        }

        if (globalSubmit && globalInput) {
            globalSubmit.addEventListener('click', function () {
                runGlobalSearch(globalInput.value);
            }, { signal: signal });
        }

        if (subNavInput) {
            subNavInput.addEventListener('input', function () {
                filterHomeSubNav(subNavInput.value);
            }, { signal: signal });

            subNavInput.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    subNavInput.value = '';
                    filterHomeSubNav('');
                    subNavInput.blur();
                }
            }, { signal: signal });
        }

        document.querySelectorAll('.js-home-quick-tag').forEach(function (btn) {
            btn.addEventListener('click', function () {
                handleQuickTag(btn);
            }, { signal: signal });
        });

        document.querySelectorAll('.js-ann-details').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (typeof openAnnouncementModal === 'function') {
                    openAnnouncementModal(btn);
                }
            }, { signal: signal });
        });

        document.addEventListener('click', function (e) {
            if (!results || results.hidden) return;
            var wrap = document.getElementById('homeGlobalSearch');
            if (wrap && !wrap.contains(e.target)) {
                results.hidden = true;
            }
        }, { signal: signal });
    }

    document.addEventListener('chatrox:page_load', function (e) {
        if (!e.detail || e.detail.active_tab !== 'home') return;
        initHomeSearch();
    });

    document.addEventListener('chatrox:page_unload', function () {
        if (sessionAbort) sessionAbort.abort();
    });

    global.ChatRoxHomeSearch = {
        run: runGlobalSearch,
        filterSubNav: filterHomeSubNav
    };
})(typeof window !== 'undefined' ? window : this);
