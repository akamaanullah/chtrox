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

        fetch(window.CHATROX.baseUrl + '/api/channels/join', {
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
                span.className = 'btn-joined';
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

});