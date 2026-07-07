/**
 * ChatRox App Router — client-side navigation without full page reload.
 * Fetches HTML fragments from /api/app/page and swaps sub-nav + main content.
 */
(function (global) {
    'use strict';

    var NAV_HEADER = 'X-ChatRox-Navigate';
    var LOADING_CLASS = 'app-shell--loading';
    var currentPath = normalizePath(global.location.pathname);
    var inflight = null;
    var loadedScripts = Object.create(null);
    var loadingDepth = 0;

    function getBaseUrl() {
        var base = (global.CHATROX && global.CHATROX.baseUrl) ? String(global.CHATROX.baseUrl) : '';
        return base.replace(/\/+$/, '');
    }

    function getBasePath() {
        if (global.CHATROX && global.CHATROX.basePath) {
            return String(global.CHATROX.basePath).replace(/\/+$/, '');
        }

        var base = getBaseUrl();
        if (!base) return '';

        try {
            if (base.indexOf('://') !== -1) {
                return new URL(base).pathname.replace(/\/+$/, '') || '';
            }
        } catch (err) {
            return '';
        }

        return base.charAt(0) === '/' ? base : '';
    }

    function stripBasePath(pathname) {
        var basePath = getBasePath();
        var path = pathname || '/';

        while (basePath) {
            if (path === basePath) {
                path = '/';
                break;
            }
            if (path.indexOf(basePath + '/') === 0) {
                path = path.slice(basePath.length) || '/';
                continue;
            }
            break;
        }

        return path;
    }

    function normalizePath(input) {
        var path = input || '/';

        if (String(path).indexOf('://') !== -1) {
            try {
                path = new URL(path).pathname;
            } catch (err) {
                path = '/';
            }
        }

        path = stripBasePath(path);
        path = path.replace(/^\/+/, '').replace(/\/+$/, '');
        if (path === '') return 'home';
        return path;
    }

    function buildUrl(relativePath) {
        relativePath = String(relativePath || '');
        if (relativePath.indexOf('://') !== -1 || relativePath.indexOf('//') === 0) {
            return relativePath;
        }
        relativePath = relativePath.replace(/^\/+/, '');
        if (relativePath === 'home') relativePath = '';

        var base = getBaseUrl();
        if (!relativePath) {
            return base + '/';
        }
        return base + '/' + relativePath;
    }

    function isSpaLink(link) {
        if (!link || link.tagName !== 'A') return false;
        if (link.hasAttribute('download')) return false;
        if (link.target && link.target !== '_self') return false;
        if (link.hasAttribute('data-no-spa')) return false;
        if (link.classList.contains('js-open-profile-panel')) return false;
        if (link.getAttribute('href') === '#') return false;

        var href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#') return false;

        var url;
        try {
            url = new URL(link.href, global.location.href);
        } catch (err) {
            return false;
        }

        if (url.origin !== global.location.origin) return false;
        if (url.pathname.indexOf('/login') !== -1) return false;
        if (url.pathname.indexOf('/register') !== -1) return false;
        if (url.pathname.indexOf('/logout') !== -1) return false;
        if (url.pathname.indexOf('/admin') !== -1) return false;
        if (url.pathname.indexOf('/files/download/') !== -1) return false;

        var app = document.getElementById('app');
        return !!(app && app.getAttribute('data-spa') === '1');
    }

    function setLoading(on) {
        if (on) {
            loadingDepth += 1;
        } else {
            loadingDepth = Math.max(0, loadingDepth - 1);
        }

        var show = loadingDepth > 0;
        var shell = document.getElementById('app-shell');
        var loader = document.getElementById('app-nav-loader');
        if (shell) shell.classList.toggle(LOADING_CLASS, show);
        if (loader) {
            loader.hidden = !show;
            loader.setAttribute('aria-busy', show ? 'true' : 'false');
        }
    }

    function resetLoading() {
        loadingDepth = 0;
        var shell = document.getElementById('app-shell');
        var loader = document.getElementById('app-nav-loader');
        if (shell) shell.classList.remove(LOADING_CLASS);
        if (loader) {
            loader.hidden = true;
            loader.setAttribute('aria-busy', 'false');
        }
    }

    function updateSidebarActive(activeTab) {
        var isMoreTab = ['files', 'browse-channels', 'settings'].indexOf(activeTab) !== -1;

        document.querySelectorAll('.sidebar .nav-item[data-nav-tab]').forEach(function (item) {
            var tab = item.getAttribute('data-nav-tab');
            var isActive = (tab === activeTab) || (tab === 'more' && isMoreTab);
            item.classList.toggle('active', isActive);
            var bar = item.querySelector('.active-bar');
            if (isActive && !bar) {
                var div = document.createElement('div');
                div.className = 'active-bar';
                item.insertBefore(div, item.firstChild);
            } else if (!isActive && bar) {
                bar.remove();
            }
        });

        document.querySelectorAll('.more-option').forEach(function (opt) {
            var href = opt.getAttribute('href') || '';
            var isFiles = activeTab === 'files' && href.indexOf('files') !== -1;
            var isBrowse = activeTab === 'browse-channels' && href.indexOf('browse-channels') !== -1;
            var isSettings = activeTab === 'settings' && href.indexOf('settings') !== -1;
            opt.classList.toggle('active', isFiles || isBrowse || isSettings);
        });
    }

    function loadScript(src) {
        if (loadedScripts[src]) {
            return Promise.resolve();
        }

        return new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = src;
            script.defer = true;
            script.setAttribute('data-chatrox-tab', '1');
            script.onload = function () {
                loadedScripts[src] = true;
                resolve();
            };
            script.onerror = function () {
                reject(new Error('Failed to load ' + src));
            };
            document.body.appendChild(script);
        });
    }

    function loadTabScripts(scripts) {
        var list = scripts || [];
        var chain = Promise.resolve();
        list.forEach(function (rel) {
            chain = chain.then(function () {
                return loadScript(buildUrl(rel));
            });
        });
        return chain;
    }

    function mountFragmentHtml(payload) {
        var subNavWrap = document.getElementById('app-sub-nav');
        var main = document.getElementById('app-main');

        var scrollPositions = {};
        if (subNavWrap) {
            scrollPositions['sub-nav-root'] = subNavWrap.scrollTop;
            var scrollable = subNavWrap.querySelectorAll('.dm-list, .dir-list');
            scrollable.forEach(function (el, index) {
                scrollPositions[index] = el.scrollTop;
            });
        }

        if (subNavWrap) {
            if (payload.sub_nav_html) {
                subNavWrap.innerHTML = payload.sub_nav_html;
                subNavWrap.hidden = false;
                subNavWrap.classList.add('page-enter');

                if (scrollPositions['sub-nav-root'] !== undefined) {
                    subNavWrap.scrollTop = scrollPositions['sub-nav-root'];
                }
                var newScrollable = subNavWrap.querySelectorAll('.dm-list, .dir-list');
                newScrollable.forEach(function (el, index) {
                    if (scrollPositions[index] !== undefined) {
                        el.scrollTop = scrollPositions[index];
                    }
                });
            } else {
                subNavWrap.innerHTML = '';
                subNavWrap.hidden = true;
            }
        }

        if (main) {
            main.innerHTML = payload.main_html || '';
            main.classList.add('page-enter');
        }

        // Trigger reflow to apply the initial transition state, then remove the enter class in next frame
        if (typeof requestAnimationFrame !== 'undefined') {
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    if (subNavWrap) subNavWrap.classList.remove('page-enter');
                    if (main) main.classList.remove('page-enter');
                });
            });
        } else {
            setTimeout(function () {
                if (subNavWrap) subNavWrap.classList.remove('page-enter');
                if (main) main.classList.remove('page-enter');
            }, 20);
        }

        if (global.ChatRoxLucide) {
            var scope = [];
            if (subNavWrap) scope.push(subNavWrap);
            if (main) scope.push(main);
            global.ChatRoxLucide.refresh(scope.length ? scope : null);
        } else if (global.lucide && typeof global.lucide.createIcons === 'function') {
            global.lucide.createIcons();
        }
    }

    function detectInitialMeta() {
        var meta = {};
        var activeItem = document.querySelector('.sidebar .nav-item.active[data-nav-tab]');
        meta.active_tab = activeItem ? activeItem.getAttribute('data-nav-tab') : 'home';

        var dmChat = document.querySelector('.dm-chat-screen');
        if (dmChat) {
            meta.with_username = dmChat.dataset.withUsername || null;
            meta.conversation_id = dmChat.dataset.conversationId || null;
            meta.channel_id = dmChat.dataset.channelId || null;
        }

        return meta;
    }

    function dispatchPageLoad(payload) {
        document.dispatchEvent(new CustomEvent('chatrox:page_load', {
            detail: payload || {}
        }));
    }

    function fetchPage(path, signal) {
        var url = buildUrl('api/v1/app/page') + '?path=' + encodeURIComponent(path);
        return fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-ChatRox-Navigate': '1'
            },
            credentials: 'same-origin',
            signal: signal
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok || !data.success) {
                    throw new Error((data && data.error) || 'Navigation failed');
                }
                return data;
            });
        });
    }

    function beginPageTransition() {
        document.dispatchEvent(new CustomEvent('chatrox:page_unload', {
            detail: { path: currentPath }
        }));
    }

    function navigate(path, options) {
        options = options || {};
        path = normalizePath('/' + String(path || '').replace(/^\/+/, ''));

        if (!options.force && path === currentPath && !options.forceReload) {
            return Promise.resolve(null);
        }

        if (inflight) {
            inflight.abort();
        }

        var controller = new AbortController();
        inflight = controller;

        setLoading(true);

        return fetchPage(path, controller.signal).then(function (payload) {
            if (controller.signal.aborted) return null;

            beginPageTransition();
            mountFragmentHtml(payload);
            updateSidebarActive(payload.active_tab || 'home');

            return loadTabScripts(payload.scripts || []).then(function () {
                if (controller.signal.aborted) return null;

                currentPath = payload.path || path;

                if (!options.replace) {
                    global.history.pushState({ chatroxPath: currentPath }, '', buildUrl(currentPath));
                }

                if (payload.title) {
                    document.title = payload.title;
                }

                dispatchPageLoad(payload);
                return payload;
            });
        }).catch(function (err) {
            if (err && err.name === 'AbortError') {
                return null;
            }
            console.error('[ChatRoxRouter]', err);
            global.location.href = buildUrl(path);
            return null;
        }).finally(function () {
            if (inflight === controller) {
                inflight = null;
            }
            setLoading(false);
        });
    }

    function onLinkClick(e) {
        var link = e.target.closest('a');
        if (!isSpaLink(link)) return;

        e.preventDefault();
        var path = normalizePath(new URL(link.href, global.location.href).pathname);
        navigate(path);
    }

    function markInitialScriptsLoaded() {
        document.querySelectorAll('script[data-chatrox-tab]').forEach(function (node) {
            if (node.src) {
                loadedScripts[node.src] = true;
            }
        });
    }

    function bootCurrentPage() {
        markInitialScriptsLoaded();
        dispatchPageLoad({
            success: true,
            path: currentPath,
            active_tab: detectInitialMeta().active_tab,
            meta: detectInitialMeta(),
            initial: true
        });
    }

    function init() {
        var app = document.getElementById('app');
        if (!app || app.getAttribute('data-spa') !== '1') return;

        global.history.replaceState({ chatroxPath: currentPath }, '', buildUrl(currentPath));

        document.addEventListener('click', onLinkClick);

        global.addEventListener('popstate', function (e) {
            var path = (e.state && e.state.chatroxPath)
                ? e.state.chatroxPath
                : normalizePath(global.location.pathname);
            navigate(path, { replace: true, force: true });
        });

        // Defer boot so deferred tab scripts (chat.js, sidebar.js, …) register
        // their chatrox:page_load listeners first. Without this, a full page refresh
        // on /dms/... fires page_load before chat.js loads and init never runs.
        setTimeout(function () {
            bootCurrentPage();
            resetLoading();
        }, 0);
    }

    global.ChatRoxRouter = {
        navigate: navigate,
        normalizePath: normalizePath,
        currentPath: function () { return currentPath; }
    };

    global.ChatRoxOnReady = function (fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(typeof window !== 'undefined' ? window : this);
