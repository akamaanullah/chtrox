/**
 * Jump to a specific chat message (from global search, links, etc.)
 */
(function (global) {
    'use strict';

    var STORAGE_KEY = 'chatrox_message_focus';

    function escapeRegex(s) {
        return String(s).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function getMessageTextElements(bubble) {
        if (!bubble) return [];
        var nodes = [];
        bubble.querySelectorAll('p').forEach(function (p) {
            if (!p.closest('.dm-msg-reply-wrap')) nodes.push(p);
        });
        if (!nodes.length) {
            bubble.querySelectorAll(':scope > div').forEach(function (div) {
                if (div.classList.contains('dm-msg-reply-wrap') ||
                    div.classList.contains('dm-msg-forward-label') ||
                    div.classList.contains('dm-msg-images') ||
                    div.classList.contains('dm-msg-files')) {
                    return;
                }
                nodes.push(div);
            });
        }
        return nodes;
    }

    function highlightTextInElement(el, q) {
        var origHtml = el.getAttribute('data-original-html');
        if (origHtml === null) {
            origHtml = el.innerHTML;
            el.setAttribute('data-original-html', origHtml);
        } else {
            el.innerHTML = origHtml;
        }
        if (!q) return;

        var re = new RegExp('(' + escapeRegex(q) + ')', 'gi');
        var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                var parent = node.parentElement;
                if (parent && parent.closest('.dm-search-highlight, .dm-msg-reply-wrap')) {
                    return NodeFilter.FILTER_REJECT;
                }
                return node.textContent ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            }
        });
        var textNodes = [];
        while (walker.nextNode()) {
            textNodes.push(walker.currentNode);
        }
        textNodes.forEach(function (textNode) {
            var text = textNode.textContent;
            if (!re.test(text)) {
                re.lastIndex = 0;
                return;
            }
            re.lastIndex = 0;
            var frag = document.createDocumentFragment();
            var lastIndex = 0;
            var match;
            var localRe = new RegExp('(' + escapeRegex(q) + ')', 'gi');
            while ((match = localRe.exec(text)) !== null) {
                if (match.index > lastIndex) {
                    frag.appendChild(document.createTextNode(text.slice(lastIndex, match.index)));
                }
                var span = document.createElement('span');
                span.className = 'dm-search-highlight';
                span.textContent = match[1];
                frag.appendChild(span);
                lastIndex = localRe.lastIndex;
            }
            if (lastIndex < text.length) {
                frag.appendChild(document.createTextNode(text.slice(lastIndex)));
            }
            textNode.parentNode.replaceChild(frag, textNode);
        });
    }

    function revealMessage(msgEl) {
        if (!msgEl) return;
        msgEl.classList.remove('dm-chat-msg--hidden');
        msgEl.classList.remove('dm-chat-msg--search-nomatch');
        if (msgEl.getAttribute('data-initially-hidden') === '1') {
            msgEl.removeAttribute('data-initially-hidden');
        }
    }

    function highlightQueryInMessage(msgEl, query) {
        if (!msgEl || !query) return;
        var bubbleEl = msgEl.querySelector('.dm-msg-bubble');
        if (!bubbleEl) return;
        getMessageTextElements(bubbleEl).forEach(function (el) {
            highlightTextInElement(el, query);
        });
    }

    function scrollAndHighlight(messageId, query, options) {
        options = options || {};
        var container = options.container || document.getElementById('dmChatMessages');
        if (!messageId || !container) return false;

        var msg = document.getElementById('dm-msg-' + messageId);
        if (!msg) return false;

        revealMessage(msg);
        msg.scrollIntoView({ behavior: 'smooth', block: 'center' });
        msg.classList.add('dm-chat-msg--highlight');
        setTimeout(function () {
            msg.classList.remove('dm-chat-msg--highlight');
        }, 2000);

        if (query) {
            highlightQueryInMessage(msg, query);
        }
        return true;
    }

    function setPending(payload) {
        if (!payload || !payload.message_id) return;
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                message_id: parseInt(payload.message_id, 10),
                conversation_id: parseInt(payload.conversation_id, 10) || 0,
                query: String(payload.query || '').trim()
            }));
        } catch (e) {
            /* ignore */
        }
    }

    function consumePending() {
        try {
            var raw = sessionStorage.getItem(STORAGE_KEY);
            if (!raw) return null;
            sessionStorage.removeItem(STORAGE_KEY);
            var data = JSON.parse(raw);
            if (!data || !data.message_id) return null;
            return {
                message_id: parseInt(data.message_id, 10),
                conversation_id: parseInt(data.conversation_id, 10) || 0,
                query: String(data.query || '').trim()
            };
        } catch (e) {
            return null;
        }
    }

    function peekPending() {
        try {
            var raw = sessionStorage.getItem(STORAGE_KEY);
            if (!raw) return null;
            var data = JSON.parse(raw);
            if (!data || !data.message_id) return null;
            return {
                message_id: parseInt(data.message_id, 10),
                conversation_id: parseInt(data.conversation_id, 10) || 0,
                query: String(data.query || '').trim()
            };
        } catch (e) {
            return null;
        }
    }

    global.ChatRoxMessageFocus = {
        setPending: setPending,
        consumePending: consumePending,
        peekPending: peekPending,
        revealMessage: revealMessage,
        highlightQueryInMessage: highlightQueryInMessage,
        scrollAndHighlight: scrollAndHighlight
    };
})(typeof window !== 'undefined' ? window : this);
