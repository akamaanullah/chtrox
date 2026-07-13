/** Message HTML → plain text helpers (sidebar preview, reply snippet). */
(function () {
    var STRUCTURAL_BUBBLE_SEL = '.dm-msg-images, .dm-msg-files, .dm-msg-reply-wrap, .dm-msg-forward-label, .dm-msg-voice';

    function toPlain(html, singleLine) {
        if (!html) return '';
        var normalized = String(html)
            .replace(/<br\s*\/?>/gi, '\n')
            .replace(/<\/div>/gi, '\n')
            .replace(/<\/p>/gi, '\n')
            .replace(/<\/li>/gi, '\n');
        var tmp = document.createElement('div');
        tmp.innerHTML = normalized;
        var text = (tmp.textContent || tmp.innerText || '').replace(/\r/g, '');
        text = text.replace(/\n{3,}/g, '\n\n').trim();
        if (singleLine) {
            text = text.replace(/\s+/g, ' ').trim();
        }
        return text;
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function isTextBlockEl(el) {
        if (!el || el.nodeType !== 1) return false;
        if (el.matches(STRUCTURAL_BUBBLE_SEL)) return false;
        var tag = el.tagName.toLowerCase();
        return tag === 'div' || tag === 'p';
    }

    function plainLinesToHtml(text) {
        return text
            .split('\n')
            .map(function (line) { return escapeHtml(line); })
            .join('<br>');
    }

    function getCsrfToken() {
        return window.CHATROX && window.CHATROX.csrfToken ? window.CHATROX.csrfToken : '';
    }

    function normalizeFetchInit(input, init) {
        init = init || {};

        var method = 'GET';
        if (init.method) {
            method = init.method.toString().toUpperCase();
        } else if (input && input.method) {
            method = input.method.toString().toUpperCase();
        }

        var headers = init.headers;
        if (!headers && input && typeof Request !== 'undefined' && input instanceof Request) {
            headers = new Headers(input.headers || {});
        }
        headers = headers || {};

        if (method !== 'GET' && getCsrfToken()) {
            if (typeof Headers !== 'undefined' && headers instanceof Headers) {
                headers.set('X-CSRF-Token', getCsrfToken());
            } else if (Array.isArray(headers)) {
                var tokenHeader = ['X-CSRF-Token', getCsrfToken()];
                var filtered = headers.filter(function (pair) {
                    return String(pair[0]).toLowerCase() !== 'x-csrf-token';
                });
                filtered.push(tokenHeader);
                headers = filtered;
            } else {
                var normalizedHeaders = {};
                for (var key in headers) {
                    if (Object.prototype.hasOwnProperty.call(headers, key)) {
                        normalizedHeaders[key] = headers[key];
                    }
                }
                normalizedHeaders['X-CSRF-Token'] = getCsrfToken();
                headers = normalizedHeaders;
            }
        }

        init.headers = headers;
        if (typeof init.credentials === 'undefined') {
            init.credentials = 'same-origin';
        }

        return init;
    }

    function fetchWithCsrf(input, init) {
        var p = window.originalFetch ? window.originalFetch(input, normalizeFetchInit(input, init)) : fetch(input, normalizeFetchInit(input, init));
        return p.then(function (response) {
            var newToken = response.headers.get('X-CSRF-Token');
            if (newToken && window.CHATROX) {
                window.CHATROX.csrfToken = newToken;
            }
            return response;
        });
    }

    if (window.fetch && !window.originalFetch) {
        window.originalFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            return window.originalFetch(input, normalizeFetchInit(input, init)).then(function (response) {
                var newToken = response.headers.get('X-CSRF-Token');
                if (newToken && window.CHATROX) {
                    window.CHATROX.csrfToken = newToken;
                }
                return response;
            });
        };
    }

    window.ChatRoxText = {
        toPlain: toPlain,
        toSidebarPreview: function (html, maxLen) {
            var text = toPlain(html, true);
            if (!text) return 'Attachment';
            maxLen = maxLen || 30;
            return text.length > maxLen ? text.substring(0, maxLen) + '...' : text;
        },
        bubbleToPlain: function (bubble, singleLine) {
            if (!bubble) return '';
            var clone = bubble.cloneNode(true);
            clone.querySelectorAll('.dm-msg-reply-wrap, .dm-msg-reactions, .dm-msg-files, .dm-msg-images, .dm-msg-forward-label, .dm-msg-voice').forEach(function (n) {
                n.remove();
            });
            return toPlain(clone.innerHTML, singleLine);
        },
        /** Merge contenteditable div-per-line markup into a single <p> with <br>. */
        normalizeBubble: function (bubble) {
            if (!bubble) return;

            // Mark current user's own mentions
            var currentMemberId = window.CHATROX && window.CHATROX.user ? window.CHATROX.user.workspace_member_id : null;
            if (currentMemberId) {
                bubble.querySelectorAll('.dm-mention-chip').forEach(function (chip) {
                    if (String(chip.getAttribute('data-member-id')) === String(currentMemberId)) {
                        chip.classList.add('dm-mention-chip--me');
                    }
                });
            }

            if (bubble.querySelector('.dm-msg-voice')) return;
            var htmlParts = [];
            var toRemove = [];

            Array.from(bubble.children).forEach(function (node) {
                if (!isTextBlockEl(node)) return;
                var part = (node.innerHTML || '').trim();
                htmlParts.push(part);
                toRemove.push(node);
            });

            if (!htmlParts.length) return;

            toRemove.forEach(function (node) { node.remove(); });

            var merged = htmlParts.join('<br>');
            if (!merged) return;

            var p = document.createElement('p');
            p.innerHTML = merged;

            var mediaAnchor = bubble.querySelector('.dm-msg-images, .dm-msg-files');
            if (mediaAnchor) {
                bubble.insertBefore(p, mediaAnchor);
            } else {
                var meta = bubble.querySelector('.dm-msg-reply-wrap, .dm-msg-forward-label');
                if (meta) {
                    meta.insertAdjacentElement('afterend', p);
                } else {
                    bubble.insertBefore(p, bubble.firstChild);
                }
            }
        },
        pinnedPreview: function (bubble, maxLen, maxLines) {
            maxLen = maxLen || 240;
            maxLines = maxLines || 8;
            if (!bubble) return 'Message';

            if (bubble.querySelector('.dm-msg-voice')) return 'Voice message';

            if (bubble.querySelector('.dm-msg-images') && !bubble.querySelector('.dm-msg-files')) {
                var onlyP = bubble.querySelector('p');
                if (!onlyP || !(onlyP.textContent || '').trim()) return 'Photo';
            }

            var file = bubble.querySelector('.dm-file-name');
            if (file && file.textContent) return file.textContent.trim();

            var text = this.bubbleToPlain(bubble, false);
            if (!text) return 'Message';

            var lines = text.split('\n');
            if (lines.length > maxLines) {
                text = lines.slice(0, maxLines).join('\n') + '\n…';
            } else if (text.length > maxLen) {
                text = text.substring(0, maxLen) + '…';
            }
            return text;
        }
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();

    // Initialize theme system from shared themes-shared.js
    if (window.ChatroxTheme) {
        ChatroxTheme.init();
    }

    // Handle interactive selection for list items (dir-item, aq-item)
    document.addEventListener('click', (e) => {
        const item = e.target.closest('.dir-item');
        if (item) {
            const list = item.parentElement;
            list.querySelectorAll('.dir-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
        }
    });

    /* More menu – tap on mobile (bottom nav has no hover) */
    const mobileNavMq = window.matchMedia('(max-width: 992px)');

    document.querySelectorAll('.more-trigger').forEach(function (trigger) {
        var moreBtn = trigger.querySelector('.nav-item.no-link');
        if (!moreBtn) return;

        moreBtn.addEventListener('click', function (e) {
            if (!mobileNavMq.matches) return;
            e.preventDefault();
            e.stopPropagation();
            var isOpen = trigger.classList.contains('more-open');
            document.querySelectorAll('.more-trigger.more-open').forEach(function (t) {
                t.classList.remove('more-open');
            });
            if (!isOpen) trigger.classList.add('more-open');
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.more-trigger')) {
            document.querySelectorAll('.more-trigger.more-open').forEach(function (t) {
                t.classList.remove('more-open');
            });
        }
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-join');
        if (!btn) {
            return;
        }

        var row = btn.closest('.channel-row');
        if (!row) {
            return;
        }

        var channelId = row.getAttribute('data-channel-id');
        if (!channelId) {
            return;
        }

        btn.disabled = true;

        fetch(window.CHATROX.apiUrl + '/channels/join', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ channel_id: channelId })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    window.ChatRoxDialog.alert(data.error, 'Error');
                    btn.disabled = false;
                    return;
                }

                var label = data.requested ? 'Requested' : 'Joined';
                var span = document.createElement('span');
                span.className = 'btn-joined' + (data.requested ? ' btn-requested' : '');
                span.textContent = label;
                btn.replaceWith(span);

                if (data.requested && window.ChatRoxWS && Array.isArray(data.admins) && data.admins.length > 0) {
                    window.ChatRoxWS.notifyMembers(data.admins, 'channel_join_request', {
                        channel_id: data.channel_id,
                        channel_name: data.channel_name,
                        display_name: data.display_name
                    });
                }
            })
            .catch(function () {
                window.ChatRoxDialog.alert('Unable to send your request. Please try again.', 'Error');
                btn.disabled = false;
            });
    });

    document.addEventListener('input', function (e) {
        var searchInput = e.target.closest('.js-search-browse-channels');
        if (!searchInput) return;

        var q = searchInput.value.toLowerCase().trim();
        var rows = document.querySelectorAll('#allChannelsList .channel-row');
        var visibleCount = 0;

        rows.forEach(function (row) {
            var nameEl = row.querySelector('.channel-row-info h3');
            var name = nameEl ? nameEl.textContent.toLowerCase() : '';
            var descEl = row.querySelector('.channel-row-info .channel-meta');
            var desc = descEl ? descEl.textContent.toLowerCase() : '';

            var match = name.indexOf(q) !== -1 || desc.indexOf(q) !== -1;
            row.style.display = match ? 'flex' : 'none';
            if (match) {
                visibleCount++;
            }
        });

        var emptyState = document.getElementById('allChannelsEmpty');
        if (emptyState) {
            emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    });

    // --- Profile Picture Viewer Modal ---
    var avatarModal = document.getElementById('avatarViewerModal');
    var avatarImgEl = document.getElementById('avatarViewerImg');
    var avatarNameEl = document.getElementById('avatarViewerName');
    var avatarCloseBtn = document.getElementById('avatarViewerClose');

    function openAvatarViewer(src, name) {
        if (!avatarModal || !avatarImgEl) {
            avatarModal = document.getElementById('avatarViewerModal');
            avatarImgEl = document.getElementById('avatarViewerImg');
            avatarNameEl = document.getElementById('avatarViewerName');
            avatarCloseBtn = document.getElementById('avatarViewerClose');
        }
        if (avatarModal && avatarImgEl) {
            avatarImgEl.src = src;
            if (avatarNameEl) {
                avatarNameEl.textContent = name || 'Profile Picture';
            }
            avatarModal.style.display = 'flex';
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons({ nodes: [avatarModal] });
            }
        }
    }

    function closeAvatarViewer() {
        if (avatarModal) {
            avatarModal.style.display = 'none';
        }
        if (avatarImgEl) {
            avatarImgEl.src = '';
        }
    }

    document.addEventListener('click', function (e) {
        var avatarImg = e.target.closest('.cc-avatar img, .cms-avatar, .avatar-sm img, .dm-chat-header-avatar img, .dm-welcome-card__avatar img, .mini-avatar img, .sidebar-user-avatar, .dm-details-avatar, .cc-member-avatar');
        if (avatarImg) {
            var src = avatarImg.tagName.toUpperCase() === 'IMG' ? avatarImg.src : avatarImg.querySelector('img')?.src;
            if (src) {
                e.preventDefault();
                e.stopPropagation();
                var altText = avatarImg.alt || 'Profile Picture';
                openAvatarViewer(src, altText);
            }
        }

        var closeBtn = e.target.closest('#avatarViewerClose');
        if (closeBtn) {
            closeAvatarViewer();
        }
        
        var modalViewer = e.target.closest('#avatarViewerModal');
        if (modalViewer && e.target === modalViewer) {
            closeAvatarViewer();
        }
    });

});