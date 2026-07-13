/**
 * DMs Chat screen – message send, scroll, etc.
 * Loaded on all pages; runs only when chat elements exist.
 */
(function () {
    var chatSessionAbort = null;
    var activeConversationId = null;
    var activeTypingTimer = null;

    function initChat(pageDetail) {
        var chatScreen = document.querySelector('.dm-chat-screen');
        if (!chatScreen || !chatScreen.dataset.withUsername) {
            return;
        }

        if (chatSessionAbort) {
            chatSessionAbort.abort();
        }
        chatSessionAbort = new AbortController();
        var listen = function (target, type, handler, options) {
            var opts = options && typeof options === 'object' ? Object.assign({}, options) : {};
            opts.signal = chatSessionAbort.signal;
            target.addEventListener(type, handler, opts);
        };

        var dmChatForm = document.getElementById('dmChatForm');
        var dmChatInput = document.getElementById('dmChatInput');
        var dmChatMessages = document.getElementById('dmChatMessages');
        var dmChatFileInput = document.getElementById('dmChatFileInput');
        var dmChatAttachedWrap = document.getElementById('dmChatAttachedWrap');
        var dmReplyPreview = document.getElementById('dmReplyPreview');
        var dmReplyPreviewText = document.getElementById('dmReplyPreviewText');

        function refreshIcons(root) {
            var scope = root || dmChatMessages;
            if (window.ChatRoxLucide) {
                window.ChatRoxLucide.refresh(scope);
                return;
            }
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                if (scope && scope.nodeType === 1) {
                    window.lucide.createIcons({ nodes: [scope] });
                } else {
                    window.lucide.createIcons();
                }
            }
        }
        var attachedFiles = [];
        var currentReplyMsg = null;
        var conversationId = chatScreen ? chatScreen.dataset.conversationId : null;
        var hasOlderMessages = !!(chatScreen && chatScreen.dataset.hasOlder === '1');
        var oldestMessageId = chatScreen ? parseInt(chatScreen.dataset.oldestMessageId || '0', 10) : 0;
        var hasNewerMessages = false;
        var newestMessageId = 0;
        if (!dmChatForm || !dmChatInput || !dmChatMessages) return;

        listen(dmChatMessages, 'scroll', function () {
            if (Math.abs(dmChatMessages.scrollTop) < 10) {
                var divider = dmChatMessages.querySelector('.dm-unread-divider');
                if (divider) {
                    divider.remove();
                }
                if (hasUnread) {
                    markAsRead();
                }
            }
        });

        listen(dmChatInput, 'focus', function () {
            var divider = dmChatMessages.querySelector('.dm-unread-divider');
            if (divider) {
                divider.remove();
            }
            if (hasUnread) {
                markAsRead();
            }
        });

        function getReplySnippet(msg) {
            var bubble = msg ? msg.querySelector('.dm-msg-bubble') : null;
            if (!bubble) return '';
            if (bubble.querySelector('.dm-msg-voice')) return 'Voice message';
            if (bubble.querySelector('.dm-msg-images') && !bubble.querySelector('.dm-msg-files')) {
                var onlyP = bubble.querySelector('p');
                if (!onlyP || !(onlyP.textContent || '').trim()) return 'Photo';
            }
            var file = bubble.querySelector('.dm-file-name');
            if (file && file.textContent) return file.textContent.trim();
            if (window.ChatRoxText && window.ChatRoxText.bubbleToPlain) {
                return window.ChatRoxText.bubbleToPlain(bubble, false) || 'Message';
            }
            return (bubble.textContent || '').trim() || 'Message';
        }

        function getPinnedSnippet(msg) {
            var bubble = msg ? msg.querySelector('.dm-msg-bubble') : null;
            if (window.ChatRoxText && window.ChatRoxText.pinnedPreview) {
                return window.ChatRoxText.pinnedPreview(bubble, 140);
            }
            return getReplySnippet(msg) || 'Message';
        }

        function forwardLabelHtml() {
            return '<div class="dm-msg-forward-label" aria-label="Forwarded message"><i data-lucide="forward" size="12"></i><span>Forwarded</span></div>';
        }

        function getFirstImageSrc(msg) {
            if (!msg) return null;
            var container = msg.querySelector('.dm-msg-images[data-lightbox-srcs]');
            if (container) {
                try {
                    var raw = container.getAttribute('data-lightbox-srcs');
                    if (raw) {
                        var arr = JSON.parse(raw);
                        if (arr && arr[0]) return arr[0];
                    }
                } catch (err) { }
            }
            var img = msg.querySelector('.dm-msg-images .dm-msg-img');
            return img && img.src ? img.src : null;
        }

        function getReplyImageSrcs(msg) {
            if (!msg) return [];
            var container = msg.querySelector('.dm-msg-images[data-lightbox-srcs]');
            if (container) {
                try {
                    var raw = container.getAttribute('data-lightbox-srcs');
                    if (raw) {
                        var arr = JSON.parse(raw);
                        return Array.isArray(arr) ? arr : [];
                    }
                } catch (err) { }
            }
            var imgs = msg.querySelectorAll('.dm-msg-images .dm-msg-img');
            var srcs = [];
            imgs.forEach(function (im) { if (im.src) srcs.push(im.src); });
            return srcs;
        }

        function setReplyingTo(msg) {
            currentReplyMsg = msg;
            var thumbWrap = document.getElementById('dmReplyPreviewThumbWrap');
            var thumbImg = document.getElementById('dmReplyPreviewThumb');
            var imgSrc = getFirstImageSrc(msg);
            if (dmReplyPreview && dmReplyPreviewText) {
                if (imgSrc && thumbWrap && thumbImg) {
                    thumbImg.src = imgSrc;
                    thumbWrap.removeAttribute('hidden');
                    var n = getReplyImageSrcs(msg).length;
                    dmReplyPreviewText.textContent = n > 1 ? n + ' photos' : 'Photo';
                } else {
                    if (thumbWrap) thumbWrap.setAttribute('hidden', '');
                    dmReplyPreviewText.textContent = getReplySnippet(msg) || 'Message';
                }
                dmReplyPreview.removeAttribute('hidden');
                refreshIcons();
            }
            dmChatInput.focus();
        }

        function clearReply() {
            currentReplyMsg = null;
            if (dmReplyPreview) dmReplyPreview.setAttribute('hidden', '');
            var thumbWrap = document.getElementById('dmReplyPreviewThumbWrap');
            var thumbImg = document.getElementById('dmReplyPreviewThumb');
            if (thumbWrap) thumbWrap.setAttribute('hidden', '');
            if (thumbImg) thumbImg.removeAttribute('src');
        }

        function positionFloater(trigger, floater) {
            var rect = trigger.getBoundingClientRect();
            var gap = 8;
            floater.removeAttribute('hidden');

            function place() {
                var pw = floater.offsetWidth;
                var ph = floater.offsetHeight;
                var minPh = parseFloat(window.getComputedStyle(floater).minHeight) || 0;
                if (ph < minPh) ph = minPh;
                var vw = window.innerWidth;
                var vh = window.innerHeight;
                var left = rect.left;
                var top = rect.top - ph - gap;
                if (top < gap) top = rect.bottom + gap;
                if (top + ph > vh - gap) top = vh - ph - gap;
                if (left + pw > vw - gap) left = vw - pw - gap;
                if (left < gap) left = gap;
                floater.style.left = left + 'px';
                floater.style.top = top + 'px';
            }

            requestAnimationFrame(function () {
                requestAnimationFrame(place);
            });
        }

        function readReceiptHtml(status) {
            status = status || 'sent';
            var icon = status === 'sent' ? 'check' : 'check-check';
            var label = status === 'read' ? 'Seen' : (status === 'delivered' ? 'Delivered' : 'Sent');
            return '<span class="dm-read-receipt dm-read-receipt--' + status + '" data-read-status="' + status + '" title="' + label + '" aria-label="' + label + '">' +
                '<i data-lucide="' + icon + '"></i>' +
                '<span class="dm-read-receipt-label">' + label + '</span></span>';
        }

        function setReadReceipt(msgEl, status) {
            if (!msgEl) return;
            var receipt = msgEl.querySelector('.dm-read-receipt');
            if (!receipt) return;
            var icon = status === 'sent' ? 'check' : 'check-check';
            var label = status === 'read' ? 'Seen' : (status === 'delivered' ? 'Delivered' : 'Sent');
            receipt.className = 'dm-read-receipt dm-read-receipt--' + status;
            receipt.setAttribute('data-read-status', status);
            receipt.setAttribute('title', label);
            receipt.setAttribute('aria-label', label);
            receipt.innerHTML = '<i data-lucide="' + icon + '"></i><span class="dm-read-receipt-label">' + label + '</span>';
            refreshIcons();
        }

        function updateSidebarItem(username, text, time, side, readStatus) {
            var chatScreen = document.querySelector('.dm-chat-screen');
            var activeWithUsername = chatScreen ? chatScreen.dataset.withUsername : null;
            var isCurrentlyViewing = (activeWithUsername === username);

            if (window.ChatRoxDmSidebar && typeof window.ChatRoxDmSidebar.updateItem === 'function') {
                window.ChatRoxDmSidebar.updateItem(
                    username,
                    text,
                    time,
                    side === 'them',
                    isCurrentlyViewing,
                    side === 'me' ? (readStatus || 'sent') : null
                );
                return;
            }

            var sidebarItem = document.querySelector('.dm-list a[data-dm-username="' + username + '"]');
            if (!sidebarItem) {
                var links = document.querySelectorAll('.dm-list a');
                for (var i = 0; i < links.length; i++) {
                    var href = links[i].getAttribute('href') || '';
                    if (href === 'dms/' + username || href === '/dms/' + username || href.endsWith('/dms/' + username) || href.endsWith('dms/' + username)) {
                        sidebarItem = links[i];
                        break;
                    }
                }
            }
            if (!sidebarItem) return;

            // 1. Update preview text
            var previewEl = sidebarItem.querySelector('.dm-msg');
            if (previewEl) {
                var display = (window.ChatRoxText && window.ChatRoxText.toSidebarPreview)
                    ? window.ChatRoxText.toSidebarPreview(text, 30)
                    : (text.replace(/<[^>]*>/g, '').substring(0, 30) + (text.length > 30 ? '...' : '') || 'Attachment');
                previewEl.textContent = display;
                sidebarItem.dataset.lastPreview = display;
                sidebarItem.classList.remove('dm-item--typing');
                delete sidebarItem.dataset.originalPreview;
            }

            // 2. Update time
            var timeEl = sidebarItem.querySelector('.time');
            if (timeEl) {
                timeEl.textContent = time;
            }

            // 3. Update unread count if we are not actively viewing this user
            var chatScreen = document.querySelector('.dm-chat-screen');
            var activeWithUsername = chatScreen ? chatScreen.dataset.withUsername : null;
            if (side === 'them' && activeWithUsername !== username) {
                var unreadEl = sidebarItem.querySelector('.unread-count');
                if (unreadEl) {
                    var count = parseInt(unreadEl.textContent, 10) || 0;
                    unreadEl.textContent = count + 1;
                } else {
                    var rightEl = sidebarItem.querySelector('.dm-right');
                    if (rightEl) {
                        var badge = document.createElement('span');
                        badge.className = 'unread-count';
                        badge.textContent = '1';
                        rightEl.appendChild(badge);
                    }
                }
            }

            // 4. Move to top
            var dmList = document.querySelector('.dm-list');
            if (dmList && sidebarItem) {
                dmList.insertBefore(sidebarItem, dmList.firstChild);
            }
        }

        function reconcileChatDateDividers() {
            if (window.ChatRoxDateDivider && typeof window.ChatRoxDateDivider.reconcileDateDividers === 'function') {
                window.ChatRoxDateDivider.reconcileDateDividers(dmChatMessages);
            }
        }

        function renderMessage(id, content, classes, time, side, readStatus, createdAt) {
            side = side || 'me';
            var sideClass = side === 'me' ? 'dm-chat-msg--me' : 'dm-chat-msg--them';
            var createdAttr = createdAt ? ' data-created-at="' + escapeHtml(createdAt) + '"' : '';
            var actionsBar = '<div class="dm-msg-actions" aria-label="Message actions">' +
                '<button type="button" class="dm-msg-action js-msg-react" title="Reaction" aria-label="Reaction"><i data-lucide="smile-plus" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-reply" title="Reply" aria-label="Reply"><i data-lucide="reply" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-pin" title="Pin" aria-label="Pin"><i data-lucide="pin" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-forward" title="Forward" aria-label="Forward"><i data-lucide="forward" size="16"></i></button>';
            if (side === 'me') {
                actionsBar += '<span class="dm-msg-actions-sep" aria-hidden="true"></span>' +
                    '<button type="button" class="dm-msg-action js-msg-edit" title="Edit Message" aria-label="Edit Message"><i data-lucide="edit-2" size="16"></i></button>';
            }
            actionsBar += '<span class="dm-msg-actions-sep" aria-hidden="true"></span>' +
                '<button type="button" class="dm-msg-action dm-msg-action--delete js-msg-delete" title="Delete" aria-label="Delete"><i data-lucide="trash-2" size="16"></i></button>' +
                '</div>';
            var receiptHtml = (side === 'me') ? readReceiptHtml(readStatus || 'sent') : '';
            var body = '<div class="dm-msg-body"><div class="' + classes + '">' + content + '</div><div class="dm-msg-reactions"></div><span class="dm-msg-time">' + time + receiptHtml + '</span></div>';
            var msgHtml = '<div class="dm-chat-msg ' + sideClass + '" id="' + id + '" data-msg-index=""' + createdAttr + '>' + body + actionsBar + '</div>';
            dmChatMessages.insertAdjacentHTML('afterbegin', msgHtml);
            reconcileChatDateDividers();
            refreshIcons();
            initVoicePlayers(dmChatMessages);
        }

        function updateMessageReactions(messageId, emoji, action, count, prevEmoji, prevCount, actorMemberId) {
            var msgEl = document.getElementById('dm-msg-' + messageId);
            if (!msgEl) return;
            var reactionsEl = msgEl.querySelector('.dm-msg-reactions');
            if (!reactionsEl) return;

            var currentMemberId = window.CHATROX && window.CHATROX.user ? parseInt(window.CHATROX.user.workspace_member_id, 10) : 0;
            var isMe = !actorMemberId || parseInt(actorMemberId, 10) === currentMemberId;

            function findBubbleByEmoji(key) {
                var found = null;
                reactionsEl.querySelectorAll('.dm-reaction-bubble').forEach(function (b) {
                    if (b.getAttribute('data-emoji') === key) found = b;
                });
                return found;
            }

            function updateBubble(key, value, forceActive) {
                if (!key) return;
                var bubble = findBubbleByEmoji(key);
                if (value <= 0) {
                    if (bubble) bubble.remove();
                    return;
                }
                if (bubble) {
                    bubble.querySelector('.dm-reaction-count').textContent = value;
                    if (forceActive === true) {
                        bubble.classList.add('dm-reaction-bubble--active');
                    } else if (forceActive === false) {
                        bubble.classList.remove('dm-reaction-bubble--active');
                    }
                    return;
                }
                var newBubble = document.createElement('span');
                newBubble.className = 'dm-reaction-bubble' + (forceActive === true ? ' dm-reaction-bubble--active' : '');
                newBubble.setAttribute('data-emoji', key);
                newBubble.setAttribute('title', 'View reactions');
                newBubble.innerHTML = '<span class="dm-reaction-emoji">' + escapeHtml(key) + '</span> <span class="dm-reaction-count">' + value + '</span>';
                reactionsEl.appendChild(newBubble);
            }

            if (action === 'removed') {
                updateBubble(emoji, count, isMe ? false : null);
            } else {
                updateBubble(emoji, count, isMe ? true : null);
                if (prevEmoji && prevEmoji !== emoji) {
                    updateBubble(prevEmoji, typeof prevCount === 'number' ? prevCount : 0, isMe ? false : null);
                }
            }
        }

        var allowedTags = { b: 1, i: 1, s: 1, u: 1, strong: 1, em: 1, ul: 1, ol: 1, li: 1, p: 1, br: 1, span: 1, div: 1, a: 1, pre: 1, code: 1, img: 1 };
        function cleanStyleAttribute(node) {
            if (!node.getAttribute || !node.getAttribute('style')) return;
            node.style.removeProperty('background');
            node.style.removeProperty('background-color');
            node.style.removeProperty('background-image');
            node.style.removeProperty('background-position');
            node.style.removeProperty('background-repeat');
            node.style.removeProperty('background-size');
            var remaining = node.getAttribute('style');
            if (!remaining || !remaining.trim()) node.removeAttribute('style');
        }
        function sanitizeHtml(html) {
            var el = document.createElement('div');
            el.innerHTML = html;
            function sanitizeNode(node) {
                if (node.nodeType === 3) return;
                if (node.nodeType === 1) {
                    var tag = node.tagName.toLowerCase();
                    for (var c = node.childNodes.length - 1; c >= 0; c--) sanitizeNode(node.childNodes[c]);
                    if (!allowedTags[tag]) {
                        while (node.firstChild) node.parentNode.insertBefore(node.firstChild, node);
                        node.parentNode.removeChild(node);
                        return;
                    }
                    for (var i = node.attributes.length - 1; i >= 0; i--) {
                        var name = node.attributes[i].name;
                        var isAllowedAttr = false;
                        if (name === 'style' || name === 'align') {
                            isAllowedAttr = true;
                        } else if ((tag === 'span' || tag === 'code') && (name === 'class' || name === 'contenteditable' || name === 'data-member-id' || name === 'data-username')) {
                            isAllowedAttr = true;
                        } else if (tag === 'a' && (name === 'class' || name === 'contenteditable' || name === 'href' || name === 'target' || name === 'download')) {
                            isAllowedAttr = true;
                        } else if (tag === 'i' && (name === 'class' || name === 'data-lucide' || name === 'size')) {
                            isAllowedAttr = true;
                        } else if (tag === 'img' && (name === 'src' || name === 'alt' || name === 'class' || name === 'style' || name === 'width' || name === 'height')) {
                            if (name === 'src') {
                                var val = node.getAttribute('src');
                                if (val && (val.indexOf('data:image/') === 0 || val.indexOf('http://') === 0 || val.indexOf('https://') === 0 || val.indexOf('/') === 0)) {
                                    isAllowedAttr = true;
                                }
                            } else {
                                isAllowedAttr = true;
                            }
                        }
                        if (!isAllowedAttr) node.removeAttribute(name);
                    }
                    if (node.hasAttribute('bgcolor')) node.removeAttribute('bgcolor');
                    cleanStyleAttribute(node);
                }
            }
            sanitizeNode(el);
            return el.innerHTML;
        }
        function escapeHtml(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        /** Render message body with line breaks (HTML or plain \\n). */
        function formatMessageBodyHtml(body) {
            if (!body) return '';
            if (/<[a-z][^>]*>/i.test(body)) {
                return sanitizeHtml(body);
            }
            return escapeHtml(body).replace(/\n/g, '<br>');
        }

        function setMessageParagraphContent(p, body) {
            if (!p) return;
            p.innerHTML = formatMessageBodyHtml(body);
            var bubble = p.closest('.dm-msg-bubble');
            if (bubble && window.ChatRoxText && window.ChatRoxText.normalizeBubble) {
                window.ChatRoxText.normalizeBubble(bubble);
            }
        }

        function normalizeAllMessageBubbles() {
            if (!window.ChatRoxText || !window.ChatRoxText.normalizeBubble) return;
            dmChatMessages.querySelectorAll('.dm-msg-bubble').forEach(function (bubble) {
                window.ChatRoxText.normalizeBubble(bubble);
            });
        }
        normalizeAllMessageBubbles();

        function getFileExtInfo(name, mimeType) {
            if (window.ChatRoxFileType && window.ChatRoxFileType.getFileExtInfo) {
                return window.ChatRoxFileType.getFileExtInfo(name, mimeType);
            }
            return {
                extLabel: 'FILE',
                extSlug: 'file',
                typeLabel: 'File',
                lucideIcon: 'file',
                iconCategory: 'default'
            };
        }

        function buildFileCardHtml(name, sizeLabel, href, mimeType) {
            var info = getFileExtInfo(name, mimeType);
            var safeName = escapeHtml(name || 'File');
            var downloadTag = href
                ? '<a href="' + href + '" download="' + safeName + '" class="dm-file-download" aria-label="Download ' + safeName + '">'
                : '<button type="button" class="dm-file-download" aria-label="Download ' + safeName + '">';
            var downloadClose = href ? '</a>' : '</button>';
            return '' +
                '<div class="dm-file-card">' +
                '<div class="dm-file-icon dm-file-icon--cat-' + escapeHtml(info.iconCategory || 'default') + ' dm-file-icon--' + escapeHtml(info.extSlug) + '" aria-hidden="true">' +
                '<i data-lucide="' + escapeHtml(info.lucideIcon) + '" size="18"></i>' +
                '<span class="dm-file-ext">' + escapeHtml(info.extLabel) + '</span>' +
                '</div>' +
                '<div class="dm-file-body">' +
                '<span class="dm-file-name" title="' + safeName + '">' + safeName + '</span>' +
                '<span class="dm-file-meta">' +
                '<span class="dm-file-size">' + escapeHtml(sizeLabel) + '</span>' +
                '<span class="dm-file-sep" aria-hidden="true">·</span>' +
                '<span class="dm-file-type">' + escapeHtml(info.typeLabel) + '</span>' +
                '</span>' +
                '</div>' +
                downloadTag +
                '<i data-lucide="download" size="16"></i>' +
                downloadClose +
                '</div>';
        }

        function formatVoiceDuration(seconds) {
            seconds = Math.max(0, parseInt(seconds, 10) || 0);
            return Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
        }

        function buildVoiceBarsHtml(seed, count) {
            count = count || 36;
            seed = parseInt(seed, 10) || 1;
            var html = '';
            for (var i = 0; i < count; i++) {
                var h = 4 + ((i * 11 + seed * 7) % 17);
                html += '<span class="dm-voice-bar" style="height:' + h + 'px"></span>';
            }
            return html;
        }

        function getVoiceTotalSeconds(audio) {
            if (!audio) return 0;
            var stored = parseInt(audio.getAttribute('data-duration') || '0', 10);
            if (audio.duration && isFinite(audio.duration) && audio.duration > 0) {
                return Math.floor(audio.duration);
            }
            return stored > 0 ? stored : 0;
        }

        function updateVoiceProgress(wrap, audio, pctOverride) {
            var total = getVoiceTotalSeconds(audio);
            if (!total) return;
            var pct = typeof pctOverride === 'number'
                ? pctOverride
                : Math.min(100, (audio.currentTime / total) * 100);
            var clip = wrap.querySelector('.js-voice-progress-clip');
            var scrubber = wrap.querySelector('.js-voice-scrubber');
            var wave = wrap.querySelector('.dm-voice-wave');
            if (clip) clip.style.width = pct + '%';
            if (scrubber) scrubber.style.left = pct + '%';
            if (wave && wave.hasAttribute('aria-valuenow')) {
                wave.setAttribute('aria-valuenow', String(Math.round(pct)));
            }
        }

        function resetVoiceProgress(wrap) {
            if (!wrap) return;
            updateVoiceProgress(wrap, wrap.querySelector('.dm-voice-audio'), 0);
        }

        function setVoicePlayIcon(playBtn, isPlaying) {
            if (!playBtn) return;
            var icon = playBtn.querySelector('[data-lucide]');
            if (icon) icon.setAttribute('data-lucide', isPlaying ? 'pause' : 'play');
            playBtn.setAttribute('aria-label', isPlaying ? 'Pause voice message' : 'Play voice message');
            playBtn.classList.toggle('dm-voice-play--active', isPlaying);
            refreshIcons(playBtn);
        }

        function pauseOtherVoicePlayers(exceptAudio) {
            dmChatMessages.querySelectorAll('.dm-voice-audio').forEach(function (other) {
                if (other === exceptAudio || other.paused) return;
                other.pause();
                other.currentTime = 0;
                var otherWrap = other.closest('.dm-msg-voice');
                if (!otherWrap) return;
                resetVoiceProgress(otherWrap);
                setVoicePlayIcon(otherWrap.querySelector('.js-voice-play'), false);
            });
        }

        function getWaveSeekPercent(wave, clientX) {
            var rect = wave.getBoundingClientRect();
            if (!rect.width) return 0;
            return Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
        }

        function seekVoiceToPercent(wrap, audio, pct) {
            var total = getVoiceTotalSeconds(audio);
            if (!total) return;
            audio.currentTime = (pct / 100) * total;
            updateVoiceProgress(wrap, audio, pct);
        }

        function loadVoiceMediaBlob(media) {
            var sourceUrl = media.getAttribute('data-src') || media.getAttribute('src') || media.src;
            if (!sourceUrl || media.dataset.blobLoading === '1') {
                return Promise.reject(new Error('missing_source'));
            }

            media.dataset.blobLoading = '1';

            return fetch(sourceUrl, { credentials: 'same-origin' })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.blob();
                })
                .then(function (blob) {
                    if (media._blobUrl) {
                        URL.revokeObjectURL(media._blobUrl);
                    }
                    media._blobUrl = URL.createObjectURL(blob);
                    media.src = media._blobUrl;
                    media.load();
                    return media;
                })
                .finally(function () {
                    delete media.dataset.blobLoading;
                });
        }

        function playVoiceMedia(wrap, media, playBtn) {
            if (!media) return;

            media.muted = false;

            function attemptPlay() {
                var playPromise = media.play();
                if (!playPromise || typeof playPromise.catch !== 'function') {
                    return;
                }

                playPromise.catch(function (err) {
                    console.warn('[ChatRox] Voice playback failed, retrying via blob:', err);
                    loadVoiceMediaBlob(media)
                        .then(function () {
                            return media.play();
                        })
                        .catch(function (retryErr) {
                            console.warn('[ChatRox] Voice blob playback failed:', retryErr);
                            if (window.ChatRoxToast && typeof window.ChatRoxToast.error === 'function') {
                                window.ChatRoxToast.error('Unable to play the voice message. Please try again.');
                            }
                            setVoicePlayIcon(playBtn, false);
                        });
                });
            }

            if (media.readyState === 0) {
                media.load();
            }

            attemptPlay();
        }

        function bindVoicePlayerControls(wrap) {
            if (!wrap || wrap.dataset.voiceControlsBound === '1') return;

            var audio = wrap.querySelector('.dm-voice-audio');
            var playBtn = wrap.querySelector('.js-voice-play');
            var wave = wrap.querySelector('.dm-voice-wave');
            var scrubber = wrap.querySelector('.js-voice-scrubber');
            var durationEl = wrap.querySelector('.dm-voice-duration');
            if (!audio || !playBtn || !wave) return;

            wrap.dataset.voiceControlsBound = '1';

            var isDragging = false;
            var dragMoved = false;
            var dragStartX = 0;

            audio.addEventListener('play', function () {
                pauseOtherVoicePlayers(audio);
                setVoicePlayIcon(playBtn, true);
            });

            audio.addEventListener('pause', function () {
                if (!audio.ended) setVoicePlayIcon(playBtn, false);
            });

            audio.addEventListener('ended', function () {
                setVoicePlayIcon(playBtn, false);
                resetVoiceProgress(wrap);
                audio.currentTime = 0;
                var total = getVoiceTotalSeconds(audio);
                if (durationEl && total > 0) {
                    durationEl.textContent = formatVoiceDuration(total);
                }
            });

            audio.addEventListener('timeupdate', function () {
                if (!isDragging) updateVoiceProgress(wrap, audio);
            });

            audio.addEventListener('error', function () {
                var code = audio.error ? audio.error.code : 0;
                if (code && !audio.dataset.blobRetried) {
                    audio.dataset.blobRetried = '1';
                    loadVoiceMediaBlob(audio).catch(function () { /* ignore */ });
                }
            });

            function applySeek(clientX) {
                seekVoiceToPercent(wrap, audio, getWaveSeekPercent(wave, clientX));
            }

            wave.addEventListener('click', function (e) {
                if (dragMoved) {
                    dragMoved = false;
                    return;
                }
                e.preventDefault();
                applySeek(e.clientX);
            });

            wave.addEventListener('pointerdown', function (e) {
                if (e.button !== 0) return;
                isDragging = true;
                dragMoved = false;
                dragStartX = e.clientX;
                wave.classList.add('dm-voice-wave--dragging');
                if (scrubber) scrubber.classList.add('dm-voice-scrubber--dragging');
                wave.setPointerCapture(e.pointerId);
                applySeek(e.clientX);
            });

            wave.addEventListener('pointermove', function (e) {
                if (!isDragging) return;
                if (Math.abs(e.clientX - dragStartX) > 2) dragMoved = true;
                applySeek(e.clientX);
            });

            function endWaveDrag(e) {
                if (!isDragging) return;
                isDragging = false;
                wave.classList.remove('dm-voice-wave--dragging');
                if (scrubber) scrubber.classList.remove('dm-voice-scrubber--dragging');
                try { wave.releasePointerCapture(e.pointerId); } catch (err) { /* ignore */ }
            }

            wave.addEventListener('pointerup', endWaveDrag);
            wave.addEventListener('pointercancel', endWaveDrag);
        }

        function buildVoicePlayerHtml(url, durationLabel, seed, durationSeconds) {
            durationLabel = durationLabel || '0:00';
            seed = parseInt(seed, 10) || 1;
            durationSeconds = parseInt(durationSeconds, 10) || 0;
            var durationAttr = durationSeconds > 0 ? ' data-duration="' + durationSeconds + '"' : '';
            var bars = buildVoiceBarsHtml(seed, 36);
            return '' +
                '<div class="dm-msg-voice">' +
                '<button type="button" class="dm-voice-play js-voice-play" aria-label="Play voice message">' +
                '<i data-lucide="play" size="14"></i>' +
                '</button>' +
                '<div class="dm-voice-main">' +
                '<div class="dm-voice-wave js-voice-wave" role="slider" aria-label="Voice message progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">' +
                '<div class="dm-voice-bars dm-voice-bars--base">' + bars + '</div>' +
                '<div class="dm-voice-progress-clip js-voice-progress-clip" style="width:0%">' +
                '<div class="dm-voice-bars dm-voice-bars--active">' + bars + '</div>' +
                '</div>' +
                '<span class="dm-voice-scrubber js-voice-scrubber" style="left:0%"></span>' +
                '</div>' +
                '<span class="dm-voice-duration">' + escapeHtml(durationLabel) + '</span>' +
                '</div>' +
                '<video class="dm-voice-audio" src="' + escapeHtml(url) + '" data-src="' + escapeHtml(url) + '" preload="metadata" playsinline' + durationAttr + '></video>' +
                '</div>';
        }

        function resolveVoiceDuration(audio, durationEl) {
            if (!audio || !durationEl) return;

            var stored = parseInt(audio.getAttribute('data-duration') || '0', 10);
            if (stored > 0) {
                durationEl.textContent = formatVoiceDuration(stored);
            }

            function applyFromAudio() {
                if (audio.duration && isFinite(audio.duration) && audio.duration > 0) {
                    durationEl.textContent = formatVoiceDuration(Math.floor(audio.duration));
                    return true;
                }
                return false;
            }

            if (applyFromAudio()) return;

            audio.addEventListener('loadedmetadata', function () {
                if (!applyFromAudio() && audio.duration === Infinity) {
                    audio.currentTime = 1e101;
                }
            });

            audio.addEventListener('durationchange', applyFromAudio);

            audio.addEventListener('timeupdate', function seekDuration() {
                if (!audio.duration || !isFinite(audio.duration) || audio.duration <= 0) return;
                audio.removeEventListener('timeupdate', seekDuration);
                var realDuration = Math.floor(audio.duration);
                audio.currentTime = 0;
                durationEl.textContent = formatVoiceDuration(realDuration);
            });
        }

        function layoutVoiceWaves(scope) {
            var root = scope || document;
            root.querySelectorAll('.dm-voice-wave').forEach(function (wave) {
                var width = wave.clientWidth;
                if (!width) return;
                var activeBars = wave.querySelector('.dm-voice-progress-clip .dm-voice-bars');
                if (activeBars) activeBars.style.width = width + 'px';
            });
        }

        function initVoicePlayers(root) {
            var scope = root || document;
            scope.querySelectorAll('.dm-msg-voice').forEach(function (wrap) {
                if (wrap.dataset.voiceControlsBound === '1') {
                    return;
                }

                var audio = wrap.querySelector('.dm-voice-audio');
                var durationEl = wrap.querySelector('.dm-voice-duration');
                if (!audio || !durationEl) {
                    return;
                }

                if (audio.dataset.voiceInit !== '1') {
                    audio.dataset.voiceInit = '1';
                    resolveVoiceDuration(audio, durationEl);
                }

                bindVoicePlayerControls(wrap);
            });

            requestAnimationFrame(function () {
                layoutVoiceWaves(scope);
            });
        }

        function resolveMsgType(msg) {
            if (window.ChatRoxGiphy && typeof window.ChatRoxGiphy.resolveMessageType === 'function') {
                return window.ChatRoxGiphy.resolveMessageType(msg.message_type, msg.body);
            }
            return msg.message_type;
        }

        function buildMessageBubbleContent(msg) {
            if (!msg.body && msg.text) msg.body = msg.text;
            var bubbleContent = '';
            if (msg.is_forwarded) {
                bubbleContent += forwardLabelHtml();
            }
            if (msg.reply_to_id) {
                var replySnippet = msg.reply_snippet || 'Replying...';
                if (!msg.reply_snippet) {
                    var targetEl = document.getElementById('dm-msg-' + msg.reply_to_id);
                    if (targetEl) {
                        replySnippet = getReplySnippet(targetEl);
                        if (replySnippet.length > 80) replySnippet = replySnippet.substring(0, 80) + '…';
                    }
                }
                bubbleContent += '<div class="dm-msg-reply-wrap" data-reply-to-id="dm-msg-' + msg.reply_to_id + '"><div class="dm-msg-reply-preview">' + escapeHtml(replySnippet) + '</div></div>';
            }

            if (resolveMsgType(msg) === 'gif') {
                bubbleContent += '<div class="dm-msg-images dm-msg-images--single"><img src="' + escapeHtml(msg.body || '') + '" alt="" class="dm-msg-img js-msg-img" loading="lazy"></div>';
            } else if (msg.message_type === 'voice' && msg.attachments && msg.attachments.length) {
                var voiceFile = msg.attachments.find(function (a) {
                    return a.category === 'audio' || a.category === 'video';
                }) || msg.attachments.find(function (a) {
                    return a.category !== 'image';
                }) || msg.attachments[0];
                var voiceSeconds = parseInt(msg.voice_duration_seconds, 10) || 0;
                var voiceLabel = voiceSeconds > 0 ? formatVoiceDuration(voiceSeconds) : '0:00';
                bubbleContent += buildVoicePlayerHtml(voiceFile.url, voiceLabel, voiceFile.id || msg.id, voiceSeconds);
            } else if (msg.body) {
                if (msg.body.indexOf('<pre>') !== -1 || msg.body.indexOf('<p>') === 0) {
                    bubbleContent += msg.body;
                } else {
                    bubbleContent += '<p>' + msg.body + '</p>';
                }
            }

            if (msg.attachments && msg.attachments.length) {
                var images = msg.attachments.filter(function (a) { return a.category === 'image'; });
                var audioFiles = msg.attachments.filter(function (a) { return a.category === 'audio'; });
                var docs = msg.attachments.filter(function (a) { return a.category !== 'image' && a.category !== 'audio'; });

                if (msg.message_type !== 'voice' && images.length) {
                    bubbleContent += (window.ChatRoxMedia && window.ChatRoxMedia.buildImageGridHtml)
                        ? window.ChatRoxMedia.buildImageGridHtml(images)
                        : '';
                }

                if (msg.message_type !== 'voice' && docs.length) {
                    bubbleContent += '<div class="dm-msg-files">';
                    docs.forEach(function (d) {
                        var sizeLabel = (window.ChatRoxMedia && window.ChatRoxMedia.formatFileSize)
                            ? window.ChatRoxMedia.formatFileSize(d.size_bytes)
                            : ((d.size_bytes / 1024).toFixed(1) + ' KB');
                        bubbleContent += buildFileCardHtml(d.original_name, sizeLabel, d.url, d.mime_type);
                    });
                    bubbleContent += '</div>';
                }

                if (msg.message_type !== 'voice' && audioFiles.length) {
                    audioFiles.forEach(function (a) {
                        bubbleContent += buildVoicePlayerHtml(a.url, '0:00', a.id || msg.id);
                    });
                }
            }

            return bubbleContent;
        }

        function getMessageBubbleClasses(msg) {
            var classes = 'dm-msg-bubble';
            if ((msg.attachments && msg.attachments.length) || resolveMsgType(msg) === 'gif' || msg.message_type === 'voice') {
                classes += ' dm-msg-bubble--media';
            }
            return classes;
        }

        function readReceiptHtmlFromStatus(status) {
            return readReceiptHtml(status || 'sent');
        }

        function buildHistoryMessageHtml(msg) {
            var side = msg.side || 'them';
            var sideClass = side === 'me' ? 'dm-chat-msg--me' : 'dm-chat-msg--them';
            var extraClass = '';
            if (msg.deleted_for_everyone) extraClass += ' dm-chat-msg--deleted-everyone';
            if (msg.is_pinned) extraClass += ' dm-chat-msg--pinned';
            var attrs = 'id="dm-msg-' + msg.id + '" data-msg-index="' + msg.id + '"';
            if (msg.created_at) {
                attrs += ' data-created-at="' + escapeHtml(msg.created_at) + '"';
            }
            if (msg.deleted_for_everyone) attrs += ' data-deleted-everyone="1"';
            if (msg.is_pinned) attrs += ' data-pinned="1"';

            var bubbleInner = msg.deleted_for_everyone
                ? '<div class="dm-msg-bubble"><p class="dm-msg-deleted-text">This message was deleted</p></div>'
                : '<div class="' + getMessageBubbleClasses(msg) + '">' + buildMessageBubbleContent(msg) + '</div>';

            var reactionsHtml = '';
            if (msg.reactions && msg.reactions.length) {
                msg.reactions.forEach(function (r) {
                    reactionsHtml += '<span class="dm-reaction-bubble' + (r.reacted ? ' dm-reaction-bubble--active' : '') + '" data-emoji="' + escapeHtml(r.emoji) + '"><span class="dm-reaction-emoji">' + escapeHtml(r.emoji) + '</span> <span class="dm-reaction-count">' + escapeHtml(String(r.count)) + '</span></span>';
                });
            }

            var editedHtml = msg.edited ? '<span class="dm-msg-edited-label" style="font-size: 11px; margin-right: 4px;">(Edited)</span>' : '';
            var receiptHtml = side === 'me' ? readReceiptHtmlFromStatus(msg.read_status) : '';

            var actionsBar = '<div class="dm-msg-actions" aria-label="Message actions">' +
                '<button type="button" class="dm-msg-action js-msg-react" title="Reaction" aria-label="Reaction"><i data-lucide="smile-plus" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-reply" title="Reply" aria-label="Reply"><i data-lucide="reply" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-pin" title="Pin" aria-label="Pin"><i data-lucide="pin" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-forward" title="Forward" aria-label="Forward"><i data-lucide="forward" size="16"></i></button>';
            if (side === 'me' && !msg.deleted_for_everyone) {
                actionsBar += '<span class="dm-msg-actions-sep" aria-hidden="true"></span>' +
                    '<button type="button" class="dm-msg-action js-msg-edit" title="Edit Message" aria-label="Edit Message"><i data-lucide="edit-2" size="16"></i></button>';
            }
            actionsBar += '<span class="dm-msg-actions-sep" aria-hidden="true"></span>' +
                '<button type="button" class="dm-msg-action dm-msg-action--delete js-msg-delete" title="Delete" aria-label="Delete"><i data-lucide="trash-2" size="16"></i></button></div>';

            return '<div class="dm-chat-msg ' + sideClass + extraClass + '" ' + attrs + '>' +
                '<div class="dm-msg-body">' + bubbleInner +
                '<div class="dm-msg-reactions">' + reactionsHtml + '</div>' +
                '<span class="dm-msg-time">' + editedHtml + escapeHtml(msg.time || '') + receiptHtml + '</span></div>' +
                actionsBar + '</div>';
        }

        function prependHistoryMessages(messages) {
            if (!messages || !messages.length) return;
            var loadMoreWrap = document.getElementById('dmLoadMoreWrap');
            messages.forEach(function (msg) {
                if (document.getElementById('dm-msg-' + msg.id)) return;
                var html = buildHistoryMessageHtml(msg);
                if (loadMoreWrap) {
                    loadMoreWrap.insertAdjacentHTML('beforebegin', html);
                } else {
                    dmChatMessages.insertAdjacentHTML('beforeend', html);
                }
            });
            refreshIcons();
            dmChatMessages.querySelectorAll('.dm-msg-bubble').forEach(function (bubble) {
                if (window.ChatRoxText && window.ChatRoxText.normalizeBubble) window.ChatRoxText.normalizeBubble(bubble);
            });
            initVoicePlayers(dmChatMessages);
            reconcileChatDateDividers();
        }

        dmChatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                var event = new Event('submit', { cancelable: true });
                dmChatForm.dispatchEvent(event);
            }
        });

        dmChatInput.addEventListener('paste', function (e) {
            var clipboard = e.clipboardData || window.clipboardData;
            if (!clipboard) return;

            var files = clipboard.files;
            if (files && files.length) {
                e.preventDefault();
                handleFiles(files);
                return;
            }

            e.preventDefault();
            var text = clipboard.getData('text/plain') || '';
            if (text) {
                document.execCommand('insertText', false, text);
            }
        });


        function getTimeLabelNow() {
            var now = new Date();
            var hours = now.getHours();
            var minutes = String(now.getMinutes()).padStart(2, '0');
            if (hours === 0) return '12:' + minutes + ' AM';
            if (hours < 12) return hours + ':' + minutes + ' AM';
            if (hours === 12) return '12:' + minutes + ' PM';
            return (hours - 12) + ':' + minutes + ' PM';
        }

        function buildPendingFilesHtml(files, progress) {
            if (!files || !files.length) return '';
            var html = '<div class="dm-msg-files dm-msg-files--pending">';
            files.forEach(function (f) {
                var info = getFileExtInfo(f.name, '');
                var sizeLabel = (window.ChatRoxMedia && window.ChatRoxMedia.formatFileSize)
                    ? window.ChatRoxMedia.formatFileSize(f.size)
                    : (window.ChatRoxUpload ? window.ChatRoxUpload.formatSize(f.size) : ((f.size / 1024).toFixed(1) + ' KB'));
                html += '<div class="dm-file-card dm-file-card--pending">' +
                    '<div class="dm-file-icon dm-file-icon--cat-' + escapeHtml(info.iconCategory || 'default') + ' dm-file-icon--' + escapeHtml(info.extSlug) + '">' +
                    '<i data-lucide="' + escapeHtml(info.lucideIcon) + '" size="18"></i>' +
                    '<span class="dm-file-ext">' + escapeHtml(info.extLabel) + '</span>' +
                    '</div>' +
                    '<div class="dm-file-body">' +
                    '<span class="dm-file-name" title="' + escapeHtml(f.name) + '">' + escapeHtml(f.name) + '</span>' +
                    '<span class="dm-file-meta"><span class="dm-file-size">' + escapeHtml(sizeLabel) + '</span></span>' +
                    (window.ChatRoxUpload ? window.ChatRoxUpload.buildProgressBarHtml(progress) : '') +
                    '</div></div>';
            });
            html += '</div>';
            return html;
        }

        function renderPendingMessage(pendingId, text, rawHtml, files, replyMsgCopy) {
            var bubbleContent = '';
            if (replyMsgCopy) {
                var replySnippet = getReplySnippet(replyMsgCopy);
                if (replySnippet.length > 80) replySnippet = replySnippet.substring(0, 80) + '…';
                bubbleContent += '<div class="dm-msg-reply-wrap"><div class="dm-msg-reply-preview">' + escapeHtml(replySnippet) + '</div></div>';
            }
            if (text) {
                var cleanedHtml = sanitizeHtml(rawHtml);
                if (cleanedHtml.indexOf('<pre>') !== -1 || cleanedHtml.indexOf('<p>') === 0) {
                    bubbleContent += cleanedHtml;
                } else {
                    bubbleContent += '<p>' + cleanedHtml + '</p>';
                }
            }
            if (files && files.length) {
                bubbleContent += buildPendingFilesHtml(files, 0);
            } else if (!text) {
                bubbleContent += '<p class="dm-msg-sending-label">Sending…</p>';
            }
            var msgHtml = '<div class="dm-chat-msg dm-chat-msg--me dm-chat-msg--pending" id="' + pendingId + '" data-pending="1">' +
                '<div class="dm-msg-body"><div class="dm-msg-bubble dm-msg-bubble--pending' + ((files && files.length) ? ' dm-msg-bubble--media' : '') + '">' + bubbleContent + '</div>' +
                '<span class="dm-msg-time">' + getTimeLabelNow() + '<span class="dm-msg-status-label">Sending…</span></span></div></div>';
            dmChatMessages.insertAdjacentHTML('afterbegin', msgHtml);
            refreshIcons();
        }

        function updatePendingProgress(pendingId, percent) {
            var el = document.getElementById(pendingId);
            if (!el) return;
            var fill = el.querySelector('.js-upload-progress-fill');
            var label = el.querySelector('.js-upload-progress-label');
            if (fill) fill.style.width = percent + '%';
            if (label) {
                label.textContent = percent >= 100 ? 'Sending message…' : ('Uploading… ' + percent + '%');
            }
        }

        function removePendingMessage(pendingId) {
            var el = document.getElementById(pendingId);
            if (el) el.remove();
        }

        function markPendingFailed(pendingId, message) {
            var el = document.getElementById(pendingId);
            if (!el) return;
            el.classList.remove('dm-chat-msg--pending');
            el.classList.add('dm-chat-msg--failed');
            var statusLabel = el.querySelector('.dm-msg-status-label');
            if (statusLabel) statusLabel.textContent = 'Failed';
            var bubble = el.querySelector('.dm-msg-bubble');
            if (!bubble) return;
            var cleanMsg = message || 'Failed to send';
            if (cleanMsg.length > 100) {
                cleanMsg = cleanMsg.substring(0, 100) + '...';
            }
            var failHtml = '<p class="dm-msg-failed-text">' + escapeHtml(cleanMsg) + '</p>';
            var progress = bubble.querySelector('.dm-upload-progress');
            if (progress) {
                progress.outerHTML = failHtml;
            } else {
                bubble.insertAdjacentHTML('beforeend', failHtml);
            }
        }

        function appendAttachmentsToDetails(msg) {
            if (!msg.attachments || !msg.attachments.length) return;

            var detailsMediaGrid = document.getElementById('dmDetailsMediaGrid');
            var detailsMediaEmpty = document.getElementById('dmDetailsMediaEmpty');
            var detailsFilesList = document.getElementById('dmDetailsFilesList');
            var detailsFilesEmpty = document.getElementById('dmDetailsFilesEmpty');

            msg.attachments.forEach(function (att) {
                if (att.category === 'image') {
                    if (detailsMediaGrid) {
                        if (detailsMediaGrid.querySelector('[data-message-id="' + msg.id + '"]')) return;

                        var btnHtml = '<button type="button" class="dm-details-media-thumb-btn js-details-media-jump"' +
                            ' data-message-id="' + parseInt(msg.id, 10) + '"' +
                            ' aria-label="' + escapeHtml(att.original_name) + '">' +
                            '<img src="' + escapeHtml(att.url) + '" alt="' + escapeHtml(att.original_name) + '"' +
                            ' class="dm-details-media-thumb" loading="lazy">' +
                            '</button>';
                        
                        detailsMediaGrid.insertAdjacentHTML('afterbegin', btnHtml);
                        if (detailsMediaEmpty) {
                            detailsMediaEmpty.hidden = true;
                            detailsMediaEmpty.classList.remove('dm-details-empty--show');
                        }
                    }
                } else if (att.category !== 'audio') {
                    if (detailsFilesList) {
                        if (detailsFilesList.querySelector('[href*="/files/download/' + att.id + '"]')) return;

                        var sizeLabel = (window.ChatRoxMedia && window.ChatRoxMedia.formatFileSize)
                            ? window.ChatRoxMedia.formatFileSize(att.size_bytes)
                            : ((att.size_bytes / 1024).toFixed(1) + ' KB');

                        var fileHtml = '<div class="dm-details-file-row">' +
                            '<a href="' + escapeHtml(att.url) + '" target="_blank" class="dm-details-file-link" title="View file">' +
                            '<span class="dm-details-file-icon"><i data-lucide="file-text" size="18"></i></span>' +
                            '<div class="dm-details-file-info">' +
                            '<span class="dm-details-file-name">' + escapeHtml(att.original_name) + '</span>' +
                            '<span class="dm-details-file-size">' + escapeHtml(sizeLabel) + '</span>' +
                            '</div>' +
                            '</a>' +
                            '<div class="dm-details-file-actions">' +
                            '<a href="' + escapeHtml(att.url) + '" target="_blank" class="dm-details-file-action" title="View"><i data-lucide="eye" size="14"></i></a>' +
                            '<a href="' + escapeHtml(att.url) + '" download class="dm-details-file-action" title="Download"><i data-lucide="download" size="14"></i></a>' +
                            '</div>' +
                            '</div>';

                        detailsFilesList.insertAdjacentHTML('afterbegin', fileHtml);
                        if (detailsFilesEmpty) {
                            detailsFilesEmpty.hidden = true;
                            detailsFilesEmpty.classList.remove('dm-details-empty--show');
                        }

                        if (window.ChatRoxLucide) {
                            window.ChatRoxLucide.refresh(detailsFilesList);
                        } else if (window.lucide && typeof window.lucide.createIcons === 'function') {
                            window.lucide.createIcons({ nodes: [detailsFilesList] });
                        }
                    }
                }
            });
        }

        function finalizeOutgoingMessage(msg) {
            var bubbleContent = buildMessageBubbleContent(msg);
            var bubbleClasses = getMessageBubbleClasses(msg);
            renderMessage('dm-msg-' + msg.id, bubbleContent, bubbleClasses, msg.time_label, 'me', msg.read_status, msg.created_at);
            dmChatMessages.scrollTop = 0;
            var activeWithUsername = chatScreen ? chatScreen.dataset.withUsername : null;
            var sidebarPreview = msg.message_type === 'voice' ? 'Voice message' : (msg.body || (msg.attachments && msg.attachments.length ? 'Sent an attachment' : ''));
            if (activeWithUsername) {
                updateSidebarItem(activeWithUsername, sidebarPreview, msg.time_label, 'me', msg.read_status);
            }
            appendAttachmentsToDetails(msg);
            if (window.ChatRoxWS) {
                window.ChatRoxWS.broadcast(conversationId, 'new_message', msg);
            }
            if (window.highlightCodeBlocks) {
                window.highlightCodeBlocks(dmChatMessages);
            }
        }

        dmChatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var raw = dmChatInput.innerHTML;
            var text = (dmChatInput.textContent || '').trim();
            if (!text && (!attachedFiles || attachedFiles.length === 0)) return;

            var activeConversationId = document.querySelector('.dm-chat-screen').dataset.conversationId;

            var attachedFilesCopy = [...attachedFiles];
            var currentReplyMsgCopy = currentReplyMsg;
            var pendingId = 'dm-pending-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
            var hasFiles = attachedFilesCopy && attachedFilesCopy.length > 0;

            attachedFiles = [];
            if (dmChatFileInput) dmChatFileInput.value = '';
            if (dmChatAttachedWrap) {
                dmChatAttachedWrap.innerHTML = '';
                dmChatAttachedWrap.setAttribute('hidden', '');
            }
            dmChatInput.innerHTML = '';
            ensurePlaceholder();
            clearReply();

            renderPendingMessage(pendingId, text, raw, hasFiles ? attachedFilesCopy : [], currentReplyMsgCopy);

            var uploadPromise;
            if (hasFiles && window.ChatRoxUpload) {
                uploadPromise = window.ChatRoxUpload.upload(attachedFilesCopy, {
                    onProgress: function (pct) {
                        updatePendingProgress(pendingId, pct);
                    }
                }).then(function (resData) {
                    if (resData.partial && resData.errors && resData.errors.length > 0) {
                        if (window.ChatRoxToast) {
                            var errMsg = resData.errors.map(function (err) {
                                return '"' + err.name + '": ' + err.message;
                            });
                            window.ChatRoxToast.error(errMsg, 'Some uploads failed');
                        }
                    }
                    return (resData.files || []).map(function (f) { return f.id; });
                });
            } else if (hasFiles) {
                uploadPromise = Promise.reject(new Error('Upload helper is not available.'));
            } else {
                uploadPromise = Promise.resolve([]);
            }

            uploadPromise.then(function (fileIds) {
                if (hasFiles) {
                    updatePendingProgress(pendingId, 100);
                }

                var replyToId = null;
                if (currentReplyMsgCopy) {
                    var matches = (currentReplyMsgCopy.id || '').match(/dm-msg-(\d+)/);
                    if (matches) {
                        replyToId = parseInt(matches[1], 10);
                    }
                }

                return fetch(window.CHATROX.apiUrl + '/messages', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversation_id: activeConversationId,
                        body: text ? raw : '',
                        file_ids: fileIds,
                        reply_to_id: replyToId
                    })
                });
            })
            .then(function (res) { return res.json(); })
            .then(function (resData) {
                if (resData.success && resData.message) {
                    removePendingMessage(pendingId);
                    if (hasNewerMessages && window.ChatRoxRouter) {
                        window.ChatRoxRouter.navigate(window.ChatRoxRouter.currentPath(), { forceReload: true });
                    } else {
                        finalizeOutgoingMessage(resData.message);
                    }
                    return;
                }
                markPendingFailed(pendingId, resData.message || resData.error || 'Failed to send message.');
            })
            .catch(function (err) {
                console.error('Failed to send message:', err);
                markPendingFailed(pendingId, err.message || 'Upload failed.');
            });
        });

        function ensurePlaceholder() {
            if (!(dmChatInput.textContent || '').trim()) {
                dmChatInput.innerHTML = '';
            }
        }

        dmChatInput.addEventListener('blur', ensurePlaceholder);

        document.querySelectorAll('.dm-chat-tool-btn[data-cmd]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                dmChatInput.focus();
                document.execCommand(btn.getAttribute('data-cmd'), false, null);
            });
        });
        var emojiPicker = document.getElementById('dmEmojiPicker');
        var emojiToggle = document.querySelector('.js-emoji-toggle');
        if (emojiPicker && emojiToggle) {
            emojiToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var isOpen = !emojiPicker.hasAttribute('hidden');
                if (!isOpen) {
                    positionFloater(emojiToggle, emojiPicker);
                    emojiToggle.setAttribute('aria-expanded', 'true');
                } else {
                    emojiPicker.setAttribute('hidden', '');
                    emojiToggle.setAttribute('aria-expanded', 'false');
                }
            });
            emojiPicker.querySelectorAll('.dm-emoji-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var emoji = btn.getAttribute('data-emoji') || btn.textContent;
                    dmChatInput.focus();
                    document.execCommand('insertText', false, emoji);
                    emojiPicker.setAttribute('hidden', '');
                    emojiToggle.setAttribute('aria-expanded', 'false');
                });
            });
            listen(document, 'click', function (e) {
                if (emojiPicker.hasAttribute('hidden')) return;
                if (!emojiPicker.contains(e.target) && !emojiToggle.contains(e.target)) {
                    emojiPicker.setAttribute('hidden', '');
                    emojiToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // Giphy API Integration (Using verified public web key)
        var gifPicker = document.getElementById('dmGifPicker');
        var gifToggle = document.querySelector('.js-gif-toggle');
        var gifResults = document.getElementById('dmGifPickerResults');
        var gifSearchInput = gifPicker ? gifPicker.querySelector('.js-gif-search') : null;
        var gifSearchTimeout = null;

        if (gifPicker && gifToggle && gifResults) {
            function repositionGifPicker() {
                if (!gifPicker.hasAttribute('hidden')) {
                    positionFloater(gifToggle, gifPicker);
                }
            }

            function showGifError(message) {
                gifResults.innerHTML = '<div class="dm-gif-error">' + escapeHtml(message || 'Failed to load GIFs.') + '</div>';
                repositionGifPicker();
            }

            function fetchGIFs(query) {
                if (!window.ChatRoxGiphy || typeof window.ChatRoxGiphy.fetch !== 'function') {
                    showGifError('GIF picker is unavailable.');
                    return;
                }

                gifResults.innerHTML = '<div class="dm-gif-loading">Loading...</div>';

                window.ChatRoxGiphy.fetch(query, 20)
                    .then(function (gifs) {
                        gifResults.innerHTML = '';
                        if (!gifs.length) {
                            gifResults.innerHTML = '<div class="dm-gif-no-results">No GIFs found.</div>';
                            repositionGifPicker();
                            return;
                        }
                        gifs.forEach(function (item) {
                            var gifUrl = item.url;
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'dm-gif-btn';
                            btn.setAttribute('data-gif', gifUrl);
                            btn.innerHTML = '<img src="' + escapeHtml(item.preview || gifUrl) + '" alt="GIF" loading="lazy">';
                            btn.addEventListener('click', function (e) {
                                e.preventDefault();
                                sendGifMessage(gifUrl);
                                gifPicker.setAttribute('hidden', '');
                                gifToggle.setAttribute('aria-expanded', 'false');
                            });
                            gifResults.appendChild(btn);
                        });
                        repositionGifPicker();
                    })
                    .catch(function (err) {
                        if (err && err.code === 'giphy_not_configured') {
                            showGifError('GIF search is not configured. Set GIPHY_API_KEY in .env.');
                            return;
                        }
                        showGifError(err && err.message ? err.message : 'Failed to load GIFs.');
                    });
            }

            gifToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var isOpen = !gifPicker.hasAttribute('hidden');
                if (!isOpen) {
                    positionFloater(gifToggle, gifPicker);
                    gifToggle.setAttribute('aria-expanded', 'true');
                    if (emojiPicker && !emojiPicker.hasAttribute('hidden')) {
                        emojiPicker.setAttribute('hidden', '');
                        emojiToggle.setAttribute('aria-expanded', 'false');
                    }
                    if (gifSearchInput) gifSearchInput.value = '';
                    fetchGIFs(); // Load trending by default
                } else {
                    gifPicker.setAttribute('hidden', '');
                    gifToggle.setAttribute('aria-expanded', 'false');
                }
            });

            if (gifSearchInput) {
                gifSearchInput.addEventListener('input', function () {
                    var q = this.value.trim();
                    clearTimeout(gifSearchTimeout);
                    gifSearchTimeout = setTimeout(function () {
                        fetchGIFs(q);
                    }, 500);
                });
            }

            listen(document, 'click', function (e) {
                if (gifPicker.hasAttribute('hidden')) return;
                if (!gifPicker.contains(e.target) && !gifToggle.contains(e.target)) {
                    gifPicker.setAttribute('hidden', '');
                    gifToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        function sendGifMessage(src) {
            if (!src) return;
            
            fetch(window.CHATROX.apiUrl + '/messages', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    body: src,
                    message_type: 'gif',
                    file_ids: []
                })
            })
            .then(function (res) { return res.json(); })
            .then(function (resData) {
                if (resData.success && resData.message) {
                    var msg = resData.message;
                    
                    var bubbleContent = '<div class="dm-msg-images dm-msg-images--single"><img src="' + escapeHtml(msg.body) + '" alt="" class="dm-msg-img js-msg-img" loading="lazy"></div>';
                    var bubbleClasses = 'dm-msg-bubble dm-msg-bubble--media';

                    renderMessage('dm-msg-' + msg.id, bubbleContent, bubbleClasses, msg.time_label, 'me', msg.read_status, msg.created_at);
                    dmChatMessages.scrollTop = 0;
                    
                    var chatScreen = document.querySelector('.dm-chat-screen');
                    var activeWithUsername = chatScreen ? chatScreen.dataset.withUsername : null;
                    if (activeWithUsername) {
                        updateSidebarItem(activeWithUsername, 'Sent a GIF', msg.time_label, 'me', msg.read_status);
                    }
                    
                    if (window.ChatRoxWS) {
                        window.ChatRoxWS.broadcast(conversationId, 'new_message', msg);
                    }
                }
            })
            .catch(function (err) {
                console.error('Failed to send GIF message:', err);
            });
        }


        function buildAttachedFileChipHtml(name, size, index) {
            var info = getFileExtInfo(name, '');
            var sizeLabel = size
                ? ((window.ChatRoxMedia && window.ChatRoxMedia.formatFileSize)
                    ? window.ChatRoxMedia.formatFileSize(size)
                    : (window.ChatRoxUpload ? window.ChatRoxUpload.formatSize(size) : ((size / 1024).toFixed(1) + ' KB')))
                : '';
            return '<span class="dm-chat-attached-chip">' +
                '<span class="dm-chat-attached-icon dm-file-icon dm-file-icon--cat-' + escapeHtml(info.iconCategory || 'default') + ' dm-file-icon--' + escapeHtml(info.extSlug) + '">' +
                '<i data-lucide="' + escapeHtml(info.lucideIcon) + '" size="14"></i>' +
                '<span class="dm-file-ext">' + escapeHtml(info.extLabel) + '</span>' +
                '</span>' +
                '<span class="dm-chat-attached-chip-name" title="' + escapeHtml(name) + '">' + escapeHtml(name) + '</span>' +
                (sizeLabel ? '<span class="dm-chat-attached-chip-size">' + escapeHtml(sizeLabel) + '</span>' : '') +
                '<button type="button" class="dm-chat-attached-item-remove" data-index="' + index + '" title="Remove file">×</button>' +
                '</span>';
        }

        if (dmChatFileInput && dmChatAttachedWrap) {
            var chatScreen = document.querySelector('.dm-chat-screen');
            var dragCounter = 0;

            function handleFiles(files) {
                if (!files || !files.length) return;
                if (window.ChatRoxUpload) {
                    var validation = window.ChatRoxUpload.validateFiles(files);
                    if (validation.errors && validation.errors.length > 0) {
                        if (window.ChatRoxToast) {
                            window.ChatRoxToast.error(validation.errors, 'Could not attach some files');
                        }
                    }
                    if (validation.files.length === 0) {
                        return;
                    }
                    attachedFiles = validation.files;
                } else {
                    attachedFiles = [];
                    for (var i = 0; i < files.length; i++) {
                        attachedFiles.push({ name: files[i].name, size: files[i].size, file: files[i] });
                    }
                }
                updateAttachedUI();
            }

            function updateAttachedUI() {
                if (!attachedFiles.length) {
                    dmChatAttachedWrap.innerHTML = '';
                    dmChatAttachedWrap.setAttribute('hidden', '');
                    return;
                }
                var html = '<span class="dm-chat-attached-label">Attached:</span>';
                for (var j = 0; j < attachedFiles.length; j++) {
                    html += buildAttachedFileChipHtml(attachedFiles[j].name, attachedFiles[j].size, j);
                }
                html += '<button type="button" class="dm-chat-attached-clear js-clear-attached" title="Remove all">×</button>';
                dmChatAttachedWrap.innerHTML = html;
                dmChatAttachedWrap.removeAttribute('hidden');
                refreshIcons();

                dmChatAttachedWrap.querySelector('.js-clear-attached').addEventListener('click', function () {
                    attachedFiles = [];
                    if (dmChatFileInput) dmChatFileInput.value = '';
                    updateAttachedUI();
                });

                dmChatAttachedWrap.querySelectorAll('.dm-chat-attached-item-remove').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        var index = parseInt(this.getAttribute('data-index'), 10);
                        attachedFiles.splice(index, 1);
                        updateAttachedUI();
                    });
                });
            }

            if (chatScreen) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    chatScreen.addEventListener(eventName, e => {
                        e.preventDefault();
                        e.stopPropagation();
                    }, false);
                });

                chatScreen.addEventListener('dragenter', () => {
                    dragCounter++;
                    chatScreen.classList.add('drag-active');
                });

                chatScreen.addEventListener('dragleave', () => {
                    dragCounter--;
                    if (dragCounter === 0) {
                        chatScreen.classList.remove('drag-active');
                    }
                });

                chatScreen.addEventListener('drop', (e) => {
                    dragCounter = 0;
                    chatScreen.classList.remove('drag-active');

                    var dt = e.dataTransfer;
                    var files = dt.files;
                    if (files && files.length) {
                        handleFiles(files);
                    }
                });
            }

            dmChatFileInput.addEventListener('change', function () {
                if (this.files.length) {
                    handleFiles(this.files);
                }
            });
        }

        document.querySelectorAll('.dm-chat-tool-btn[data-action]').forEach(function (btn) {
            if (btn.getAttribute('data-action') === 'emoji') return;
            listen(btn, 'click', function (e) {
                e.preventDefault();
                var action = btn.getAttribute('data-action');
                if (action === 'attach' && dmChatFileInput) {
                    dmChatFileInput.click();
                } else if (action === 'gif') { /* HANDLED BY JS-GIF-TOGGLE */ }
                else if (action === 'voice') {
                    toggleVoiceRecording();
                }
            });
        });

        var voiceRecorderEl = document.getElementById('dmVoiceRecorder');
        var voiceRecordingTimeEl = document.getElementById('dmVoiceRecordingTime');
        var voiceWaveformCanvas = document.getElementById('dmVoiceWaveform');
        var voicePermissionNotice = document.getElementById('dmVoicePermissionNotice');
        var voicePermissionMessage = document.getElementById('dmVoicePermissionMessage');
        var voiceToggleBtn = document.querySelector('.js-voice-toggle');
        var dmChatSendBtn = document.getElementById('dmChatSend');
        var voiceState = {
            mediaRecorder: null,
            stream: null,
            chunks: [],
            timer: null,
            startedAt: 0,
            isRecording: false,
            isRequesting: false,
            audioContext: null,
            analyser: null,
            waveformRaf: null
        };

        function formatRecordingTime(ms) {
            var totalSeconds = Math.floor(ms / 1000);
            return formatVoiceDuration(totalSeconds);
        }

        function hideVoicePermissionNotice() {
            if (!voicePermissionNotice) return;
            voicePermissionNotice.setAttribute('hidden', '');
            voicePermissionNotice.style.display = 'none';
        }

        function showVoicePermissionNotice(message) {
            if (!voicePermissionNotice) return;
            if (voicePermissionMessage) voicePermissionMessage.textContent = message;
            voicePermissionNotice.removeAttribute('hidden');
            voicePermissionNotice.style.display = 'flex';
            refreshIcons();
        }

        function getMicrophoneErrorMessage(err) {
            var name = err && err.name ? err.name : '';
            var message = err && err.message ? String(err.message) : '';
            if (/permissions policy|not allowed in this document/i.test(message)) {
                return 'Microphone is blocked by browser security policy on this page. Hard refresh the page (Ctrl+Shift+R) and try again.';
            }
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                return 'Microphone permission was denied. Click Allow microphone below — your browser should ask to allow access.';
            }
            if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                return 'No microphone was found on this device. Connect a mic and try again.';
            }
            if (name === 'NotReadableError' || name === 'TrackStartError') {
                return 'Your microphone is being used by another app. Close it and try again.';
            }
            if (name === 'SecurityError') {
                return 'Microphone access needs a secure connection. Use https:// or http://localhost.';
            }
            return 'Could not access the microphone. Click Allow microphone to try again.';
        }

        function stopWaveform() {
            if (voiceState.waveformRaf) {
                cancelAnimationFrame(voiceState.waveformRaf);
                voiceState.waveformRaf = null;
            }
            if (voiceState.audioContext) {
                voiceState.audioContext.close().catch(function () { /* ignore */ });
                voiceState.audioContext = null;
            }
            voiceState.analyser = null;
            if (voiceWaveformCanvas) {
                var ctx = voiceWaveformCanvas.getContext('2d');
                if (ctx) {
                    ctx.clearRect(0, 0, voiceWaveformCanvas.width, voiceWaveformCanvas.height);
                }
            }
        }

        function startWaveform(stream) {
            if (!voiceWaveformCanvas || !(window.AudioContext || window.webkitAudioContext)) return;

            stopWaveform();
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            voiceState.audioContext = new AudioCtx();
            voiceState.analyser = voiceState.audioContext.createAnalyser();
            voiceState.analyser.fftSize = 256;
            voiceState.analyser.smoothingTimeConstant = 0.82;
            var source = voiceState.audioContext.createMediaStreamSource(stream);
            source.connect(voiceState.analyser);

            var canvas = voiceWaveformCanvas;
            var ctx = canvas.getContext('2d');
            var dataArray = new Uint8Array(voiceState.analyser.frequencyBinCount);

            function drawWaveform() {
                if (!voiceState.isRecording || !voiceState.analyser) return;
                voiceState.waveformRaf = requestAnimationFrame(drawWaveform);

                voiceState.analyser.getByteFrequencyData(dataArray);
                var dpr = window.devicePixelRatio || 1;
                var rect = canvas.getBoundingClientRect();
                if (canvas.width !== Math.floor(rect.width * dpr) || canvas.height !== Math.floor(rect.height * dpr)) {
                    canvas.width = Math.floor(rect.width * dpr);
                    canvas.height = Math.floor(rect.height * dpr);
                    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                }

                var w = rect.width;
                var h = rect.height;
                ctx.clearRect(0, 0, w, h);

                var bars = 36;
                var gap = 2;
                var barWidth = Math.max(2, (w - gap * (bars - 1)) / bars);
                for (var i = 0; i < bars; i++) {
                    var idx = Math.floor(i * dataArray.length / bars);
                    var level = dataArray[idx] / 255;
                    var barH = Math.max(3, level * h * 0.92);
                    var x = i * (barWidth + gap);
                    var y = (h - barH) / 2;
                    var radius = Math.min(barWidth / 2, 3);
                    ctx.fillStyle = level > 0.08 ? '#ef4444' : '#fca5a5';
                    ctx.beginPath();
                    if (typeof ctx.roundRect === 'function') {
                        ctx.roundRect(x, y, barWidth, barH, radius);
                    } else {
                        ctx.rect(x, y, barWidth, barH);
                    }
                    ctx.fill();
                }
            }

            drawWaveform();
        }

        function showVoiceRecorderUi() {
            if (dmChatInput) {
                dmChatInput.setAttribute('hidden', '');
            }
            if (voiceRecorderEl) {
                voiceRecorderEl.removeAttribute('hidden');
            }
            if (dmChatForm) {
                dmChatForm.classList.add('dm-chat-form--recording');
            }
            if (dmChatSendBtn) {
                dmChatSendBtn.setAttribute('title', 'Send voice message');
                dmChatSendBtn.setAttribute('aria-label', 'Send voice message');
            }
            refreshIcons();
        }

        function hideVoiceRecorderUi() {
            if (voiceRecorderEl) {
                voiceRecorderEl.setAttribute('hidden', '');
            }
            if (dmChatInput) {
                dmChatInput.removeAttribute('hidden');
            }
            if (dmChatForm) {
                dmChatForm.classList.remove('dm-chat-form--recording');
            }
            if (dmChatSendBtn) {
                dmChatSendBtn.setAttribute('title', 'Send');
                dmChatSendBtn.setAttribute('aria-label', 'Send');
            }
        }

        function beginVoiceRecording(stream) {
            voiceState.stream = stream;
            voiceState.chunks = [];
            hideVoicePermissionNotice();

            var mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                ? 'audio/webm;codecs=opus'
                : (MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/ogg');
            voiceState.mediaRecorder = new MediaRecorder(stream, { mimeType: mimeType });
            voiceState.mediaRecorder.ondataavailable = function (ev) {
                if (ev.data && ev.data.size > 0) voiceState.chunks.push(ev.data);
            };
            voiceState.mediaRecorder.start(250);
            voiceState.isRecording = true;
            voiceState.startedAt = Date.now();
            showVoiceRecorderUi();
            startWaveform(stream);
            if (voiceToggleBtn) {
                voiceToggleBtn.classList.add('dm-chat-tool-btn--recording');
                voiceToggleBtn.setAttribute('aria-pressed', 'true');
            }
            voiceState.timer = setInterval(function () {
                if (voiceRecordingTimeEl) {
                    voiceRecordingTimeEl.textContent = formatRecordingTime(Date.now() - voiceState.startedAt);
                }
            }, 250);
        }

        function requestMicrophoneAccess() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                showVoicePermissionNotice('Voice recording is not supported in this browser.');
                return Promise.reject(new Error('unsupported'));
            }
            if (voiceState.isRequesting || voiceState.isRecording) {
                return Promise.reject(new Error('busy'));
            }

            voiceState.isRequesting = true;
            if (voiceToggleBtn) voiceToggleBtn.classList.add('dm-chat-tool-btn--requesting');

            return navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            }).then(function (stream) {
                voiceState.isRequesting = false;
                if (voiceToggleBtn) voiceToggleBtn.classList.remove('dm-chat-tool-btn--requesting');
                return stream;
            }).catch(function (err) {
                voiceState.isRequesting = false;
                if (voiceToggleBtn) voiceToggleBtn.classList.remove('dm-chat-tool-btn--requesting');
                showVoicePermissionNotice(getMicrophoneErrorMessage(err));
                return Promise.reject(err);
            });
        }

        function cleanupVoiceRecording() {
            if (voiceState.timer) {
                clearInterval(voiceState.timer);
                voiceState.timer = null;
            }
            stopWaveform();
            if (voiceState.mediaRecorder && voiceState.mediaRecorder.state !== 'inactive') {
                try {
                    voiceState.mediaRecorder.onstop = null;
                    voiceState.mediaRecorder.stop();
                } catch (err) { /* ignore */ }
            }
            if (voiceState.stream) {
                voiceState.stream.getTracks().forEach(function (track) { track.stop(); });
                voiceState.stream = null;
            }
            voiceState.mediaRecorder = null;
            voiceState.chunks = [];
            voiceState.isRecording = false;
            hideVoiceRecorderUi();
            if (voiceToggleBtn) {
                voiceToggleBtn.classList.remove('dm-chat-tool-btn--recording');
                voiceToggleBtn.setAttribute('aria-pressed', 'false');
            }
            if (voiceRecordingTimeEl) voiceRecordingTimeEl.textContent = '0:00';
        }

        cleanupVoiceRecording();
        hideVoicePermissionNotice();

        function startVoiceRecording() {
            if (voiceState.isRecording) return;

            requestMicrophoneAccess().then(function (stream) {
                beginVoiceRecording(stream);
            }).catch(function () {
                /* notice already shown */
            });
        }

        function stopVoiceRecording(sendAfterStop) {
            if (!voiceState.isRecording || !voiceState.mediaRecorder) {
                cleanupVoiceRecording();
                return;
            }

            voiceState.mediaRecorder.onstop = function () {
                var durationSeconds = Math.max(1, Math.round((Date.now() - voiceState.startedAt) / 1000));
                var blob = new Blob(voiceState.chunks, { type: voiceState.mediaRecorder.mimeType || 'audio/webm' });
                cleanupVoiceRecording();
                if (sendAfterStop && blob.size > 0) {
                    sendVoiceMessage(blob, durationSeconds);
                }
            };
            try {
                voiceState.mediaRecorder.stop();
            } catch (err) {
                cleanupVoiceRecording();
            }
        }

        function toggleVoiceRecording() {
            if (voiceState.isRecording) {
                stopVoiceRecording(false);
            } else {
                startVoiceRecording();
            }
        }

        function sendVoiceMessage(blob, durationSeconds) {
            if (!conversationId) return;
            durationSeconds = Math.max(1, parseInt(durationSeconds, 10) || 1);
            var ext = blob.type.indexOf('ogg') !== -1 ? 'ogg' : 'webm';
            var file = new File([blob], 'voice-note-' + Date.now() + '.' + ext, { type: blob.type || 'audio/webm' });
            var formData = new FormData();
            formData.append('files[]', file);

            fetch(window.CHATROX.apiUrl + '/files/upload', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (resData) {
                if (!resData.success || !resData.files || !resData.files.length) {
                    throw new Error('Upload failed');
                }
                var fileIds = resData.files.map(function (f) { return f.id; });
                return fetch(window.CHATROX.apiUrl + '/messages', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversation_id: conversationId,
                        body: '',
                        file_ids: fileIds,
                        message_type: 'voice',
                        voice_duration_seconds: durationSeconds
                    })
                });
            })
            .then(function (res) { return res.json(); })
            .then(function (resData) {
                if (!resData.success || !resData.message) return;
                var msg = resData.message;
                renderMessage('dm-msg-' + msg.id, buildMessageBubbleContent(msg), getMessageBubbleClasses(msg), msg.time_label, 'me', msg.read_status, msg.created_at);
                var activeWithUsername = chatScreen ? chatScreen.dataset.withUsername : null;
                if (activeWithUsername) {
                    updateSidebarItem(activeWithUsername, 'Voice message', msg.time_label, 'me', msg.read_status);
                }
                if (window.ChatRoxWS) {
                    window.ChatRoxWS.broadcast(conversationId, 'new_message', msg);
                }
                refreshIcons();
            })
            .catch(function (err) {
                console.error('Failed to send voice message:', err);
                if (window.ChatRoxToast) {
                    window.ChatRoxToast.error('Could not send voice message. Please try again.', 'Voice message failed');
                }
            });
        }

        if (document.querySelector('.js-voice-recording-cancel')) {
            document.querySelector('.js-voice-recording-cancel').addEventListener('click', function (e) {
                e.preventDefault();
                if (voiceState.isRecording) stopVoiceRecording(false);
                else cleanupVoiceRecording();
            });
        }
        dmChatForm.addEventListener('submit', function (e) {
            if (!voiceState.isRecording) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            stopVoiceRecording(true);
        }, true);
        if (document.querySelector('.js-voice-permission-retry')) {
            document.querySelector('.js-voice-permission-retry').addEventListener('click', function (e) {
                e.preventDefault();
                startVoiceRecording();
            });
        }
        if (document.querySelector('.js-voice-permission-dismiss')) {
            document.querySelector('.js-voice-permission-dismiss').addEventListener('click', function (e) {
                e.preventDefault();
                hideVoicePermissionNotice();
            });
        }

        var loadMoreBtn = document.getElementById('dmLoadMore');
        var loadMoreWrap = document.getElementById('dmLoadMoreWrap');
        var loadNewerBtn = document.getElementById('dmLoadNewer');
        var loadNewerWrap = document.getElementById('dmLoadNewerWrap');
        var loadCount = 20;
        var isLoadingOlder = false;
        var isLoadingNewer = false;

        function updateLoadMoreVisibility() {
            if (loadMoreWrap) {
                var hidden = dmChatMessages.querySelectorAll('.dm-chat-msg--hidden');
                if (hidden.length > 0 || hasOlderMessages) {
                    loadMoreWrap.classList.remove('dm-load-more-wrap--hidden');
                } else {
                    loadMoreWrap.classList.add('dm-load-more-wrap--hidden');
                }
            }
            if (loadNewerWrap) {
                if (hasNewerMessages) {
                    loadNewerWrap.classList.remove('dm-load-more-wrap--hidden');
                } else {
                    loadNewerWrap.classList.add('dm-load-more-wrap--hidden');
                }
            }
        }

        function fetchOlderMessagesFromApi() {
            if (!hasOlderMessages || oldestMessageId <= 0 || isLoadingOlder) return;
            isLoadingOlder = true;
            if (loadMoreBtn) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = 'Loading...';
            }

            fetch(window.CHATROX.apiUrl + '/messages/history?conversation_id=' + encodeURIComponent(conversationId) +
                '&before_id=' + encodeURIComponent(oldestMessageId) + '&limit=30')
                .then(function (res) { return res.json(); })
                .then(function (resData) {
                    if (!resData.success) throw new Error(resData.error || 'Failed to load messages');
                    prependHistoryMessages(resData.messages || []);
                    oldestMessageId = parseInt(resData.oldest_message_id || oldestMessageId, 10);
                    hasOlderMessages = !!resData.has_more;
                    if (chatScreen) {
                        chatScreen.dataset.oldestMessageId = String(oldestMessageId);
                        chatScreen.dataset.hasOlder = hasOlderMessages ? '1' : '0';
                    }
                    updateLoadMoreVisibility();
                })
                .catch(function (err) {
                    console.error('Failed to load older messages:', err);
                })
                .finally(function () {
                    isLoadingOlder = false;
                    if (loadMoreBtn) {
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.textContent = 'Load older messages';
                    }
                });
        }

        function appendNewerHistoryMessages(messages) {
            if (!messages || !messages.length) return;
            var loadNewerWrap = document.getElementById('dmLoadNewerWrap');
            messages.forEach(function (msg) {
                if (document.getElementById('dm-msg-' + msg.id)) return;
                var html = buildHistoryMessageHtml(msg);
                if (loadNewerWrap) {
                    loadNewerWrap.insertAdjacentHTML('afterend', html);
                } else {
                    dmChatMessages.insertAdjacentHTML('afterbegin', html);
                }
            });
            refreshIcons();
            dmChatMessages.querySelectorAll('.dm-msg-bubble').forEach(function (bubble) {
                if (window.ChatRoxText && window.ChatRoxText.normalizeBubble) window.ChatRoxText.normalizeBubble(bubble);
            });
            initVoicePlayers(dmChatMessages);
            reconcileChatDateDividers();
        }

        function fetchNewerMessagesFromApi() {
            if (!hasNewerMessages || newestMessageId <= 0 || isLoadingNewer) return;
            isLoadingNewer = true;
            if (loadNewerBtn) {
                loadNewerBtn.disabled = true;
                loadNewerBtn.textContent = 'Loading...';
            }

            fetch(window.CHATROX.apiUrl + '/messages/history?conversation_id=' + encodeURIComponent(conversationId) +
                '&after_id=' + encodeURIComponent(newestMessageId) + '&limit=30')
                .then(function (res) { return res.json(); })
                .then(function (resData) {
                    if (!resData.success) throw new Error(resData.error || 'Failed to load messages');
                    appendNewerHistoryMessages(resData.messages || []);
                    newestMessageId = parseInt(resData.newest_message_id || newestMessageId, 10);
                    hasNewerMessages = !!resData.has_more;
                    updateLoadMoreVisibility();
                })
                .catch(function (err) {
                    console.error('Failed to load newer messages:', err);
                })
                .finally(function () {
                    isLoadingNewer = false;
                    if (loadNewerBtn) {
                        loadNewerBtn.disabled = false;
                        loadNewerBtn.textContent = 'Load newer messages';
                    }
                });
        }

        if (loadMoreBtn && loadMoreWrap) {
            listen(loadMoreBtn, 'click', function () {
                var hidden = dmChatMessages.querySelectorAll('.dm-chat-msg--hidden');
                if (hidden.length) {
                    var toShow = Math.min(loadCount, hidden.length);
                    for (var i = 0; i < toShow; i++) {
                        hidden[i].classList.remove('dm-chat-msg--hidden');
                        hidden[i].removeAttribute('data-initially-hidden');
                    }
                    reconcileChatDateDividers();
                    if (hidden.length <= loadCount && hasOlderMessages) {
                        fetchOlderMessagesFromApi();
                    } else {
                        updateLoadMoreVisibility();
                    }
                    return;
                }
                fetchOlderMessagesFromApi();
            });
        }

        if (loadNewerBtn && loadNewerWrap) {
            listen(loadNewerBtn, 'click', function () {
                fetchNewerMessagesFromApi();
            });
        }
        updateLoadMoreVisibility();

        var searchInput = document.getElementById('dmChatSearch');
        if (searchInput) {
            function escapeRegex(s) {
                return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
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
            function getBubbleSearchText(bubble) {
                return getMessageTextElements(bubble).map(function (el) {
                    var origHtml = el.getAttribute('data-original-html');
                    if (origHtml !== null) {
                        var tmp = document.createElement('div');
                        tmp.innerHTML = origHtml;
                        return tmp.textContent || '';
                    }
                    return el.textContent || '';
                }).join(' ').trim();
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
            function applyHighlight(el, q, hasSearch) {
                if (!el) return;
                if (el.getAttribute('data-original-html') === null) {
                    el.setAttribute('data-original-html', el.innerHTML);
                }
                if (!hasSearch || !q) {
                    var origHtml = el.getAttribute('data-original-html');
                    if (origHtml !== null) el.innerHTML = origHtml;
                    el.removeAttribute('data-original-html');
                    return;
                }
                highlightTextInElement(el, q);
            }

            var searchWrap = searchInput.closest('.dm-chat-search');
            var resultsDropdown = document.createElement('div');
            resultsDropdown.className = 'dm-chat-search-results';
            resultsDropdown.hidden = true;
            if (searchWrap) {
                searchWrap.appendChild(resultsDropdown);
            }

            function focusMessageInChat(messageId, query) {
                function tryFocus() {
                    return window.ChatRoxMessageFocus.scrollAndHighlight(messageId, query, { container: dmChatMessages });
                }

                dmChatMessages.querySelectorAll('.dm-chat-msg--hidden').forEach(function (msg) {
                    window.ChatRoxMessageFocus.revealMessage(msg);
                });

                if (tryFocus()) return;

                fetch(window.CHATROX.apiUrl + '/messages/context?conversation_id=' + encodeURIComponent(conversationId) +
                    '&message_id=' + encodeURIComponent(messageId) + '&limit=30')
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data || !data.success || !data.messages || !data.messages.length) return;
                        replaceChatWithContextMessages(data);
                        requestAnimationFrame(function () {
                            tryFocus();
                        });
                    })
                    .catch(function (err) {
                        console.warn('[ChatRoxMessageFocus]', err);
                    });
            }

            listen(document, 'click', function (e) {
                if (resultsDropdown && !resultsDropdown.hidden && !searchWrap.contains(e.target)) {
                    resultsDropdown.hidden = true;
                }
            });

            var searchAbort = null;

            function runMessageSearch(q) {
                var qLower = q.toLowerCase();
                var hasSearch = q.length > 0;
                var allMsgs = dmChatMessages.querySelectorAll('.dm-chat-msg');
                var matchCount = 0;
                allMsgs.forEach(function (msg) {
                    var bubbleEl = msg.querySelector('.dm-msg-bubble');
                    var textEls = getMessageTextElements(bubbleEl);
                    var text = getBubbleSearchText(bubbleEl);
                    var isDeleted = msg.getAttribute('data-deleted-everyone') === '1' || msg.classList.contains('dm-chat-msg--deleted-everyone');
                    var match = !hasSearch || (!isDeleted && text.toLowerCase().indexOf(qLower) !== -1);
                    if (match) {
                        msg.classList.remove('dm-chat-msg--search-nomatch');
                        if (hasSearch) {
                            msg.classList.remove('dm-chat-msg--hidden');
                            matchCount++;
                        } else if (msg.getAttribute('data-initially-hidden') === '1') {
                            msg.classList.add('dm-chat-msg--hidden');
                        }
                        textEls.forEach(function (el) { applyHighlight(el, q, hasSearch); });
                    } else {
                        msg.classList.add('dm-chat-msg--search-nomatch');
                        textEls.forEach(function (el) { applyHighlight(el, '', false); });
                    }
                });

                // Toggle date dividers visibility based on visible messages
                dmChatMessages.querySelectorAll('.dm-date-divider').forEach(function (divider) {
                    var dateKey = divider.getAttribute('data-date-key');
                    var hasVisibleMsg = false;
                    for (var i = 0; i < allMsgs.length; i++) {
                        var msg = allMsgs[i];
                        if (!msg.classList.contains('dm-chat-msg--search-nomatch') && !msg.classList.contains('dm-chat-msg--hidden')) {
                            var createdAt = msg.getAttribute('data-created-at') || '';
                            if (window.ChatRoxDateDivider && window.ChatRoxDateDivider.dayKey(createdAt) === dateKey) {
                                hasVisibleMsg = true;
                                break;
                            }
                        }
                    }
                    if (hasVisibleMsg) {
                        divider.style.display = '';
                    } else {
                        divider.style.display = 'none';
                    }
                });

                var emptyStateEl = document.getElementById('dmChatSearchEmptyState');
                if (emptyStateEl) {
                    if (hasSearch && matchCount === 0) {
                        emptyStateEl.style.display = 'flex';
                    } else {
                        emptyStateEl.style.display = 'none';
                    }
                }

                if (hasSearch) {
                    if (loadMoreWrap) loadMoreWrap.classList.add('dm-load-more-wrap--hidden');
                    if (loadNewerWrap) loadNewerWrap.classList.add('dm-load-more-wrap--hidden');
                } else {
                    updateLoadMoreVisibility();
                }

                if (searchAbort) searchAbort.abort();
                if (q.length < 2) {
                    resultsDropdown.hidden = true;
                    resultsDropdown.innerHTML = '';
                    return;
                }

                searchAbort = new AbortController();
                resultsDropdown.innerHTML = '<div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 13px;">Searching history...</div>';
                resultsDropdown.hidden = false;

                var url = window.CHATROX.apiUrl + '/search?q=' + encodeURIComponent(q) + 
                          '&conversation_id=' + encodeURIComponent(conversationId) + '&limit=10';
                
                fetch(url, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: searchAbort.signal
                })
                .then(function (res) { return res.json(); })
                .then(function (resData) {
                    if (!resData.success || !resData.results || !resData.results.messages) {
                        resultsDropdown.innerHTML = '<div class="dm-chat-search-results-empty">No historical matches</div>';
                        return;
                    }
                    var items = resData.results.messages;
                    if (items.length === 0) {
                        resultsDropdown.innerHTML = '<div class="dm-chat-search-results-empty">No historical matches</div>';
                        return;
                    }
                    resultsDropdown.innerHTML = '';
                    items.forEach(function (item) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'dm-chat-search-result-item';
                        btn.setAttribute('data-message-id', item.message_id);

                        var snippet = item.subtitle || '';
                        var cleanSnippet = snippet;
                        var senderName = 'Someone';
                        var separatorIdx = snippet.indexOf(': ');
                        if (separatorIdx !== -1) {
                            senderName = snippet.substring(0, separatorIdx);
                            cleanSnippet = snippet.substring(separatorIdx + 2);
                        }

                        var snippetHtml = escapeHtml(cleanSnippet);
                        if (q) {
                            var re = new RegExp('(' + escapeRegex(q) + ')', 'gi');
                            snippetHtml = snippetHtml.replace(re, '<span class="dm-search-highlight">$1</span>');
                        }

                        btn.innerHTML = 
                            '<div class="dm-chat-search-result-text">' + snippetHtml + '</div>' +
                            '<div class="dm-chat-search-result-meta">' +
                                '<span class="dm-chat-search-result-sender">' + escapeHtml(senderName) + '</span>' +
                                '<span>' + escapeHtml(item.time) + '</span>' +
                            '</div>';
                        
                        btn.addEventListener('click', function () {
                            resultsDropdown.hidden = true;
                            resultsDropdown.innerHTML = '';
                            searchInput.value = '';
                            runMessageSearch('');
                            focusMessageInChat(item.message_id, q);
                        });

                        resultsDropdown.appendChild(btn);
                    });
                })
                .catch(function (err) {
                    if (err.name === 'AbortError') return;
                    console.error('[InChatSearch]', err);
                    resultsDropdown.innerHTML = '<div class="dm-chat-search-results-empty">Search failed</div>';
                });
            }

            var searchDebounceTimer = null;
            searchInput.addEventListener('input', function () {
                var q = this.value.trim();
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function () {
                    runMessageSearch(q);
                }, 250);
            });
        }

        /* Quick reaction picker – WhatsApp style (Heart, Thumb, Emoji) */
        var reactionPicker = document.getElementById('dmReactionPicker');
        var currentReactMessage = null;

        function closeReactionPicker() {
            if (!reactionPicker) return;
            reactionPicker.setAttribute('hidden', '');
            if (dmChatMessages) {
                dmChatMessages.querySelectorAll('.dm-chat-msg--picker-open').forEach(function (m) {
                    m.classList.remove('dm-chat-msg--picker-open');
                });
            }
            currentReactMessage = null;
        }

        if (reactionPicker && dmChatMessages) {
            function positionReactionPicker(btn) {
                var actionsBar = btn.closest('.dm-msg-actions');
                var anchor = actionsBar || btn;
                var rect = anchor.getBoundingClientRect();
                reactionPicker.removeAttribute('hidden');
                var pickerW = reactionPicker.offsetWidth || 260;
                var pickerH = reactionPicker.offsetHeight || 44;
                var gap = 10;
                /* Emoji bar — action bar ke bilkul upar */
                var top = rect.top - pickerH - gap;
                var left = rect.left + (rect.width / 2) - (pickerW / 2);
                if (top < gap) {
                    top = rect.bottom + gap;
                }
                if (left < gap) {
                    left = gap;
                }
                if (left + pickerW > window.innerWidth - gap) {
                    left = window.innerWidth - pickerW - gap;
                }
                reactionPicker.style.left = left + 'px';
                reactionPicker.style.top = top + 'px';
            }
            dmChatMessages.addEventListener('click', function (e) {
                var reactBtn = e.target.closest('.js-msg-react');
                if (!reactBtn) return;
                e.preventDefault();
                e.stopPropagation();
                var msg = reactBtn.closest('.dm-chat-msg');
                if (!msg) return;
                if (currentReactMessage === msg && !reactionPicker.hasAttribute('hidden')) {
                    closeReactionPicker();
                    return;
                }
                closeDeleteMenu();
                closeReactionPicker();
                currentReactMessage = msg;
                msg.classList.add('dm-chat-msg--picker-open');
                positionReactionPicker(reactBtn);
            });
            reactionPicker.querySelectorAll('.dm-reaction-option').forEach(function (btn) {
                listen(btn, 'click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var emoji = btn.getAttribute('data-emoji') || btn.textContent.trim();
                    if (!emoji || !currentReactMessage) {
                        closeReactionPicker();
                        return;
                    }
                    
                    var matches = (currentReactMessage.id || '').match(/dm-msg-(\d+)/);
                    if (!matches) {
                        closeReactionPicker();
                        return;
                    }
                    var messageId = parseInt(matches[1], 10);
                    
                    closeReactionPicker();

                    fetch(window.CHATROX.apiUrl + '/messages/react', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message_id: messageId,
                            emoji: emoji
                        })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(resData) {
                        if (resData.success) {
                            updateMessageReactions(resData.message_id, resData.emoji, resData.action, resData.count, resData.prev_emoji, resData.prev_count);
                            
                            if (window.ChatRoxWS) {
                                window.ChatRoxWS.broadcast(conversationId, 'message_reaction', {
                                    conversation_id: conversationId,
                                    message_id: resData.message_id,
                                    emoji: resData.emoji,
                                    action: resData.action,
                                    count: resData.count,
                                    prev_emoji: resData.prev_emoji,
                                    prev_count: resData.prev_count,
                                    actor_member_id: window.CHATROX.user.workspace_member_id,
                                    actor_username: window.CHATROX.user.username,
                                    actor_name: window.CHATROX.user.name || window.CHATROX.user.username,
                                    recipient_member_id: resData.recipient_member_id
                                });
                            }
                        }
                    })
                    .catch(function(err) {
                        console.error('Failed to react to message:', err);
                    });
                });
            });
            listen(document, 'click', function (e) {
                if (!reactionPicker.hasAttribute('hidden') && !reactionPicker.contains(e.target) && !e.target.closest('.js-msg-react')) {
                    closeReactionPicker();
                }
            });
            dmChatMessages.addEventListener('scroll', function () {
                closeReactionPicker();
            }, { passive: true });
        }

        var dmReactionOverlay = document.getElementById('dmReactionOverlay');
        var dmReactionModal = document.getElementById('dmReactionModal');
        var dmReactionModalBody = document.getElementById('dmReactionModalBody');

        function openReactionModal(emoji, reactors, count) {
            if (!dmReactionModal || !dmReactionModalBody || !dmReactionOverlay) return;
            var html = '<div class="reaction-modal-header-row">' +
                '<div class="reaction-modal-title">Reacted with ' + escapeHtml(emoji) + '</div>' +
                '<div class="reaction-modal-count">' + escapeHtml(String(count)) + ' reaction' + (count === 1 ? '' : 's') + '</div>' +
                '</div>';
            if (Array.isArray(reactors) && reactors.length) {
                html += '<div class="reaction-row-list">';
                reactors.forEach(function (reactor) {
                    html += '<div class="reaction-row">' +
                        '<img src="' + escapeHtml(reactor.avatar) + '" alt="' + escapeHtml(reactor.name || reactor.username) + '">' +
                        '<div class="reaction-row-info">' +
                        '<span class="reaction-row-name">' + escapeHtml(reactor.name || reactor.username) + '</span>' +
                        '<span class="reaction-row-meta">' + (reactor.is_you ? 'You' : escapeHtml(reactor.username || '')) + '</span>' +
                        '</div>' +
                        '</div>';
                });
                html += '</div>';
            } else {
                html += '<div class="reaction-empty">No reactions yet.</div>';
            }
            dmReactionModalBody.innerHTML = html;
            dmReactionOverlay.removeAttribute('hidden');
            dmReactionModal.removeAttribute('hidden');
            if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
        }

        function closeReactionModal() {
            if (dmReactionOverlay) dmReactionOverlay.setAttribute('hidden', '');
            if (dmReactionModal) dmReactionModal.setAttribute('hidden', '');
        }

        if (dmReactionOverlay && dmReactionModal && dmReactionModalBody && dmChatMessages) {
            dmChatMessages.addEventListener('click', function (e) {
                var bubble = e.target.closest('.dm-reaction-bubble');
                if (!bubble) return;
                var msg = bubble.closest('.dm-chat-msg');
                if (!msg) return;
                var matches = (msg.id || '').match(/dm-msg-(\d+)/);
                if (!matches) return;
                var messageId = parseInt(matches[1], 10);
                var emoji = bubble.getAttribute('data-emoji') || '';
                if (!messageId || !emoji) return;

                fetch(window.CHATROX.apiUrl + '/messages/reactions?message_id=' + encodeURIComponent(messageId) + '&emoji=' + encodeURIComponent(emoji), {
                    credentials: 'same-origin'
                })
                .then(function (res) { return res.json(); })
                .then(function (resData) {
                    if (resData.success && Array.isArray(resData.reactions) && resData.reactions.length) {
                        openReactionModal(emoji, resData.reactions[0].reactors || [], resData.reactions[0].count || 0);
                    } else {
                        openReactionModal(emoji, [], 0);
                    }
                })
                .catch(function (err) {
                    console.error('Failed to fetch reaction details:', err);
                });
            });

            dmReactionOverlay.addEventListener('click', function () {
                closeReactionModal();
            });
            document.querySelectorAll('.js-dm-reaction-close').forEach(function (btn) {
                btn.addEventListener('click', function () { closeReactionModal(); });
            });
        }

        /* Forward message – search, people, groups, scroll, select count */
        var forwardOverlay = document.getElementById('dmForwardOverlay');
        var forwardModal = document.getElementById('dmForwardModal');
        var forwardSubmit = document.getElementById('dmForwardSubmit');
        var forwardSelectedCountEl = document.getElementById('dmForwardSelectedCount');
        var forwardSearchInput = document.getElementById('dmForwardSearch');
        var currentForwardMessage = null;
        if (forwardOverlay && forwardModal && forwardSubmit && dmChatMessages) {
            function updateForwardSelectedCount() {
                if (!forwardSelectedCountEl) return;
                var checks = forwardModal.querySelectorAll('.dm-forward-check:checked');
                var n = checks.length;
                forwardSelectedCountEl.textContent = n + ' selected';
            }
            function openForwardModal(msg) {
                currentForwardMessage = msg;
                if (forwardSearchInput) forwardSearchInput.value = '';
                forwardModal.querySelectorAll('.js-forward-row').forEach(function (row) {
                    row.classList.remove('dm-forward-row--hidden');
                });
                forwardModal.querySelectorAll('.js-forward-check').forEach(function (cb) { cb.checked = false; });
                updateForwardSelectedCount();
                forwardOverlay.removeAttribute('hidden');
                forwardModal.removeAttribute('hidden');
                forwardOverlay.classList.add('active');
                forwardModal.classList.add('active');
                refreshIcons();
            }
            function closeForwardModal() {
                currentForwardMessage = null;
                forwardOverlay.setAttribute('hidden', '');
                forwardModal.setAttribute('hidden', '');
                forwardOverlay.classList.remove('active');
                forwardModal.classList.remove('active');
                if (forwardSearchInput) forwardSearchInput.value = '';
                forwardModal.querySelectorAll('.js-forward-check').forEach(function (cb) { cb.checked = false; });
                forwardModal.querySelectorAll('.js-forward-row').forEach(function (row) {
                    row.classList.remove('dm-forward-row--hidden');
                });
            }
            dmChatMessages.addEventListener('click', function (e) {
                var fwdBtn = e.target.closest('.js-msg-forward');
                if (!fwdBtn) return;
                e.preventDefault();
                e.stopPropagation();
                var msg = fwdBtn.closest('.dm-chat-msg');
                if (msg) {
                    closeReactionPicker();
                    openForwardModal(msg);
                }
            });
            listen(forwardOverlay, 'click', closeForwardModal);
            document.querySelectorAll('.js-forward-close').forEach(function (btn) {
                listen(btn, 'click', function () { closeForwardModal(); });
            });
            forwardModal.querySelectorAll('.js-forward-check').forEach(function (cb) {
                listen(cb, 'change', updateForwardSelectedCount);
            });
            if (forwardSearchInput) {
                listen(forwardSearchInput, 'input', function () {
                    var q = (this.value || '').trim().toLowerCase();
                    forwardModal.querySelectorAll('.js-forward-row').forEach(function (row) {
                        var search = (row.getAttribute('data-search') || '').toLowerCase();
                        var match = !q || search.indexOf(q) !== -1;
                        row.classList.toggle('dm-forward-row--hidden', !match);
                    });
                });
            }
            listen(forwardSubmit, 'click', function () {
                if (!currentForwardMessage) return;
                var msgIdMatch = (currentForwardMessage.id || '').match(/dm-msg-(\d+)/);
                if (!msgIdMatch) return;

                var targets = [];
                forwardModal.querySelectorAll('.dm-forward-check:checked').forEach(function (cb) {
                    var type = cb.getAttribute('data-type') || 'dm';
                    var id = cb.getAttribute('data-id') || cb.value;
                    if (id) targets.push({ type: type, id: id });
                });
                if (!targets.length) return;

                var messageId = parseInt(msgIdMatch[1], 10);
                forwardSubmit.disabled = true;

                fetch(window.CHATROX.apiUrl + '/messages/forward', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message_id: messageId,
                        targets: targets
                    })
                })
                .then(function (res) { return res.json(); })
                .then(function (resData) {
                    forwardSubmit.disabled = false;
                    if (!resData.success) {
                        console.error('Forward failed:', resData.error || 'Unknown error');
                        return;
                    }
                    closeForwardModal();
                    (resData.messages || []).forEach(function (msg) {
                        if (window.ChatRoxWS) {
                            window.ChatRoxWS.broadcast(msg.conversation_id, 'new_message', msg);
                        }
                        document.dispatchEvent(new CustomEvent('chatrox:new_message', { detail: msg }));
                        if (msg.conversation_type === 'dm' && msg.recipient_username) {
                            var chatScreen = document.querySelector('.dm-chat-screen');
                            var activeWithUsername = chatScreen ? chatScreen.dataset.withUsername : null;
                            if (activeWithUsername !== msg.recipient_username) {
                                var preview = msg.body || (msg.attachments && msg.attachments.length ? 'Sent an attachment' : '');
                                updateSidebarItem(msg.recipient_username, preview, msg.time_label, 'me', msg.read_status);
                            }
                        }
                    });
                })
                .catch(function (err) {
                    forwardSubmit.disabled = false;
                    console.error('Forward failed:', err);
                });
            });
        }

        /* Image lightbox – download, prev/next, all media */
        var msgLightbox = document.getElementById('dmMsgLightbox');
        var msgLightboxImg = document.getElementById('dmMsgLightboxImg');
        var msgLightboxThumbnails = document.getElementById('dmMsgLightboxThumbnails');
        var msgLightboxPrev = document.querySelector('.js-msg-lightbox-prev');
        var msgLightboxNext = document.querySelector('.js-msg-lightbox-next');
        var msgLightboxDownload = document.querySelector('.js-msg-lightbox-download');
        var lightboxSrcs = [];
        var lightboxIndex = 0;
        if (msgLightbox && msgLightboxImg && dmChatMessages) {
            function updateLightboxThumbnailsActive() {
                if (!msgLightboxThumbnails) return;
                var thumbs = msgLightboxThumbnails.querySelectorAll('.dm-msg-lightbox-thumb');
                thumbs.forEach(function (t, i) {
                    if (i === lightboxIndex) t.classList.add('active');
                    else t.classList.remove('active');
                });
            }
            function updateLightboxImage() {
                var src = lightboxSrcs[lightboxIndex];
                if (src) {
                    msgLightboxImg.src = src;
                    if (msgLightboxDownload) {
                        msgLightboxDownload.href = src;
                        msgLightboxDownload.setAttribute('download', 'image-' + (lightboxIndex + 1) + '.jpg');
                        msgLightboxDownload.removeAttribute('hidden');
                    }
                }
                updateLightboxThumbnailsActive();
                if (msgLightboxPrev) {
                    if (lightboxSrcs.length > 1 && lightboxIndex > 0) {
                        msgLightboxPrev.removeAttribute('hidden');
                    } else {
                        msgLightboxPrev.setAttribute('hidden', '');
                    }
                }
                if (msgLightboxNext) {
                    if (lightboxSrcs.length > 1 && lightboxIndex < lightboxSrcs.length - 1) {
                        msgLightboxNext.removeAttribute('hidden');
                    } else {
                        msgLightboxNext.setAttribute('hidden', '');
                    }
                }
            }
            function openImageLightbox(srcs, index) {
                if (!srcs || !srcs.length) return;
                lightboxSrcs = srcs;
                lightboxIndex = index >= 0 && index < srcs.length ? index : 0;
                if (msgLightboxThumbnails) {
                    msgLightboxThumbnails.innerHTML = '';
                    srcs.forEach(function (s, i) {
                        var thumb = document.createElement('img');
                        thumb.className = 'dm-msg-lightbox-thumb' + (i === lightboxIndex ? ' active' : '');
                        thumb.src = s;
                        thumb.alt = 'Image ' + (i + 1);
                        thumb.setAttribute('data-index', String(i));
                        msgLightboxThumbnails.appendChild(thumb);
                    });
                }
                updateLightboxImage();
                msgLightbox.removeAttribute('hidden');
                msgLightbox.classList.add('active');
                refreshIcons();
            }
            function closeImageLightbox() {
                msgLightbox.setAttribute('hidden', '');
                msgLightbox.classList.remove('active');
                msgLightboxImg.src = '';
                lightboxSrcs = [];
                lightboxIndex = 0;
                if (msgLightboxThumbnails) msgLightboxThumbnails.innerHTML = '';
                if (msgLightboxDownload) {
                    msgLightboxDownload.removeAttribute('href');
                    msgLightboxDownload.setAttribute('hidden', '');
                }
            }
            dmChatMessages.addEventListener('click', function (e) {
                var img = e.target.closest('.js-msg-img');
                if (!img) return;
                e.preventDefault();
                e.stopPropagation();
                var msg = img.closest('.dm-chat-msg');
                var container = msg ? msg.querySelector('.dm-msg-images[data-lightbox-srcs]') : null;
                var srcs = [];
                var idx = 0;
                if (container) {
                    try {
                        var raw = container.getAttribute('data-lightbox-srcs');
                        if (raw) srcs = JSON.parse(raw);
                    } catch (err) { }
                    var idxAttr = img.getAttribute('data-index');
                    if (idxAttr !== null && idxAttr !== '') idx = parseInt(idxAttr, 10);
                    if (isNaN(idx) || idx < 0) idx = 0;
                }
                if (!srcs.length) {
                    var allImgs = msg ? msg.querySelectorAll('.dm-msg-img.js-msg-img') : [];
                    allImgs.forEach(function (im, i) {
                        if (im.src) {
                            srcs.push(im.src);
                            if (im === img) idx = srcs.length - 1;
                        }
                    });
                }
                if (srcs.length) openImageLightbox(srcs, idx);
            });
            /* Details panel Media tab – open same lightbox on thumbnail click */
            var detailsMediaGrid = document.getElementById('dmDetailsMediaGrid');
            if (detailsMediaGrid) {
                detailsMediaGrid.addEventListener('click', function (e) {
                    var thumb = e.target.closest('.dm-details-media-thumb');
                    if (!thumb || !thumb.src) return;
                    e.preventDefault();
                    e.stopPropagation();
                    var thumbs = detailsMediaGrid.querySelectorAll('.dm-details-media-thumb');
                    var srcs = [];
                    var idx = 0;
                    thumbs.forEach(function (t, i) {
                        if (t.src) {
                            srcs.push(t.src);
                            if (t === thumb) idx = srcs.length - 1;
                        }
                    });
                    if (srcs.length) openImageLightbox(srcs, idx);
                });
            }
            /* Load more media items inside details panel */
            var mediaContentPanel = document.getElementById('dmDetailsContentMedia');
            var mediaLoadMoreBtn = mediaContentPanel ? mediaContentPanel.querySelector('.js-details-media-load-more') : null;
            if (mediaLoadMoreBtn) {
                mediaLoadMoreBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (!detailsMediaGrid) return;
                    var hiddenButtons = detailsMediaGrid.querySelectorAll('.dm-details-media-thumb--hidden');
                    var batchSize = 21;
                    var shown = 0;
                    for (var i = 0; i < hiddenButtons.length; i++) {
                        if (shown >= batchSize) break;
                        hiddenButtons[i].classList.remove('dm-details-media-thumb--hidden');
                        shown++;
                    }
                    var remaining = detailsMediaGrid.querySelectorAll('.dm-details-media-thumb--hidden');
                    if (remaining.length === 0) {
                        var wrapper = mediaContentPanel ? mediaContentPanel.querySelector('#dmDetailsMediaMoreWrap') : null;
                        if (wrapper) wrapper.style.display = 'none';
                    }
                });
            }
            msgLightbox.addEventListener('click', function (e) {
                if (e.target === msgLightbox || e.target.closest('.js-msg-lightbox-close')) {
                    closeImageLightbox();
                }
            });
            if (msgLightboxThumbnails) {
                msgLightboxThumbnails.addEventListener('click', function (e) {
                    var thumb = e.target.closest('.dm-msg-lightbox-thumb');
                    if (!thumb) return;
                    e.stopPropagation();
                    var idx = parseInt(thumb.getAttribute('data-index'), 10);
                    if (!isNaN(idx) && idx >= 0 && idx < lightboxSrcs.length) {
                        lightboxIndex = idx;
                        updateLightboxImage();
                    }
                });
            }
            if (msgLightboxPrev) {
                msgLightboxPrev.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (lightboxIndex > 0) {
                        lightboxIndex--;
                        updateLightboxImage();
                        refreshIcons();
                    }
                });
            }
            if (msgLightboxNext) {
                msgLightboxNext.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (lightboxIndex < lightboxSrcs.length - 1) {
                        lightboxIndex++;
                        updateLightboxImage();
                        refreshIcons();
                    }
                });
            }
            document.querySelectorAll('.js-msg-lightbox-close').forEach(function (btn) {
                btn.addEventListener('click', closeImageLightbox);
            });
        }

        /* Delete menu – Delete for me (all) / Delete for everyone (own only) */
        var DELETED_MESSAGE_TEXT = 'This message was deleted';
        var deleteMenu = document.getElementById('dmDeleteMenu');
        if (!deleteMenu) {
            deleteMenu = document.createElement('div');
            deleteMenu.id = 'dmDeleteMenu';
            deleteMenu.className = 'dm-delete-menu';
            deleteMenu.setAttribute('role', 'menu');
            deleteMenu.setAttribute('aria-label', 'Delete message options');
            deleteMenu.setAttribute('hidden', '');
            deleteMenu.innerHTML =
                '<button type="button" class="dm-delete-menu-option" data-delete-type="me" role="menuitem">Delete for me</button>' +
                '<button type="button" class="dm-delete-menu-option dm-delete-menu-option--everyone" data-delete-type="everyone" role="menuitem">Delete for everyone</button>';
            document.body.appendChild(deleteMenu);
        }
        var currentDeleteMessage = null;

        function closeDeleteMenu() {
            if (!deleteMenu) return;
            deleteMenu.setAttribute('hidden', '');
            if (dmChatMessages) {
                dmChatMessages.querySelectorAll('.dm-chat-msg--delete-menu-open').forEach(function (m) {
                    m.classList.remove('dm-chat-msg--delete-menu-open');
                });
            }
            currentDeleteMessage = null;
        }

        function applyDeletedForEveryoneUI(msg) {
            if (!msg) return;
            if (msg.getAttribute('data-deleted-everyone') === '1') return;
            var bubble = msg.querySelector('.dm-msg-bubble');
            if (!bubble) return;
            msg.setAttribute('data-deleted-everyone', '1');
            msg.classList.add('dm-chat-msg--deleted-everyone');
            if (msg.getAttribute('data-pinned') === '1') {
                unpinMessage(msg, true);
            }
            var senderEl = msg.querySelector('.dm-msg-sender');
            if (senderEl) senderEl.remove();
            bubble.className = 'dm-msg-bubble';
            bubble.innerHTML = '<p class="dm-msg-deleted-text">' + DELETED_MESSAGE_TEXT + '</p>';
            var reactionsEl = msg.querySelector('.dm-msg-reactions');
            if (reactionsEl) reactionsEl.innerHTML = '';
        }

        function deleteMessageForMe(msg) {
            if (msg) msg.remove();
        }

        function deleteMessageForEveryone(msg) {
            applyDeletedForEveryoneUI(msg);
        }

        function configureDeleteMenu(msg) {
            var everyoneBtn = deleteMenu.querySelector('[data-delete-type="everyone"]');
            if (!everyoneBtn) return;
            var isOwn = msg && msg.classList.contains('dm-chat-msg--me');
            // Use style.display directly — CSS 'display:block' would override the [hidden] attribute
            everyoneBtn.style.display = isOwn ? '' : 'none';
            everyoneBtn.hidden = !isOwn;
        }

        deleteMenu.querySelectorAll('.dm-delete-menu-option').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!currentDeleteMessage) {
                    closeDeleteMenu();
                    return;
                }
                var type = btn.getAttribute('data-delete-type');
                var msg = currentDeleteMessage;
                closeDeleteMenu();
                
                var matches = (msg.id || '').match(/dm-msg-(\d+)/);
                if (!matches) return;
                var messageId = parseInt(matches[1], 10);

                if (type === 'everyone') {
                    fetch(window.CHATROX.apiUrl + '/messages', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message_id: messageId, scope: 'everyone' })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(resData) {
                        if (resData.success) {
                            deleteMessageForEveryone(msg);
                            if (window.ChatRoxWS) {
                                window.ChatRoxWS.broadcast(conversationId, 'message_deleted', {
                                    conversation_id: conversationId,
                                    message_id: messageId
                                });
                            }
                        }
                    })
                    .catch(function(err) {
                        console.error('Failed to delete message:', err);
                    });
                } else {
                    fetch(window.CHATROX.apiUrl + '/messages', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message_id: messageId, scope: 'me' })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(resData) {
                        if (resData.success) {
                            deleteMessageForMe(msg);
                        }
                    })
                    .catch(function(err) {
                        console.error('Failed to delete message locally:', err);
                    });
                }
                if (typeof renderDetailsPinnedList === 'function') renderDetailsPinnedList();
            });
        });

        listen(document, 'click', function (e) {
            if (deleteMenu.hasAttribute('hidden')) return;
            if (!deleteMenu.contains(e.target) && !e.target.closest('.js-msg-delete')) {
                closeDeleteMenu();
            }
        });

        dmChatMessages.addEventListener('scroll', function () {
            closeDeleteMenu();
        }, { passive: true });

        /* Delete + Pin message + Reply-wrap click → jump to original message */
        if (dmChatMessages) {
            dmChatMessages.addEventListener('click', function (e) {
                var chip = e.target.closest('.dm-mention-chip');
                if (chip) {
                    var memberId = chip.getAttribute('data-member-id');
                    var currentMemberId = window.CHATROX && window.CHATROX.user ? window.CHATROX.user.workspace_member_id : null;
                    if (currentMemberId && String(memberId) === String(currentMemberId)) {
                        return;
                    }
                    var username = chip.getAttribute('data-username');
                    var currentUsername = window.CHATROX && window.CHATROX.user ? window.CHATROX.user.username : null;
                    if (currentUsername && username === currentUsername) {
                        return;
                    }
                    if (username && window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                        window.ChatRoxRouter.navigate('dms/' + username);
                    }
                    return;
                }
                var msgAction = e.target.closest('.dm-msg-action');
                if (msgAction && !msgAction.classList.contains('js-msg-react')) {
                    closeReactionPicker();
                    closeDeleteMenu();
                }
                var replyWrap = e.target.closest('.dm-msg-reply-wrap[data-reply-to-id]');
                if (replyWrap) {
                    e.preventDefault();
                    e.stopPropagation();
                    var targetId = replyWrap.getAttribute('data-reply-to-id');
                    if (targetId) {
                        var targetMsg = document.getElementById(targetId);
                        if (targetMsg) {
                            targetMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            targetMsg.classList.add('dm-chat-msg--highlight');
                            setTimeout(function () { targetMsg.classList.remove('dm-chat-msg--highlight'); }, 2000);
                        }
                    }
                    return;
                }
                var deleteBtn = e.target.closest('.js-msg-delete');
                if (deleteBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    var msg = deleteBtn.closest('.dm-chat-msg');
                    if (!msg) return;
                    if (msg.getAttribute('data-deleted-everyone') === '1') return;
                    if (!deleteMenu.hasAttribute('hidden') && currentDeleteMessage === msg) {
                        closeDeleteMenu();
                        return;
                    }
                    closeDeleteMenu();
                    closeReactionPicker();
                    currentDeleteMessage = msg;
                    configureDeleteMenu(msg);
                    msg.classList.add('dm-chat-msg--delete-menu-open');
                    positionFloater(deleteBtn, deleteMenu);
                    deleteMenu.removeAttribute('hidden');
                    return;
                }
                var pinBtn = e.target.closest('.js-msg-pin');
                if (pinBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    var msg = pinBtn.closest('.dm-chat-msg');
                    if (!msg) return;
                    toggleMessagePin(msg);
                    return;
                }
                var replyBtn = e.target.closest('.js-msg-reply');
                if (replyBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    var msg = replyBtn.closest('.dm-chat-msg');
                    if (msg) setReplyingTo(msg);
                    return;
                }
                var editBtn = e.target.closest('.js-msg-edit');
                if (editBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    var msg = editBtn.closest('.dm-chat-msg');
                    if (!msg) return;

                    var p = msg.querySelector('.dm-msg-bubble p');
                    if (!p) return;

                    p.contentEditable = 'true';
                    p.setAttribute('data-original-html', p.innerHTML);
                    p.focus();

                    // Move cursor to end
                    var range = document.createRange();
                    var sel = window.getSelection();
                    range.selectNodeContents(p);
                    range.collapse(false);
                    sel.removeAllRanges();
                    sel.addRange(range);

                    // Save on Enter or Blur
                    var saveEdit = function () {
                        var bubble = p.closest('.dm-msg-bubble');
                        if (bubble && window.ChatRoxText && window.ChatRoxText.normalizeBubble) {
                            window.ChatRoxText.normalizeBubble(bubble);
                        }
                        var newHtml = sanitizeHtml(p.innerHTML);
                        var newText = (p.textContent || '').trim();
                        p.contentEditable = 'false';
                        p.removeAttribute('data-original-html');
                        p.removeEventListener('blur', saveEdit);
                        p.removeEventListener('keydown', handleKeydown);

                        var matches = (msg.id || '').match(/dm-msg-(\d+)/);
                        if (!matches || !newText) return;
                        var messageId = parseInt(matches[1], 10);

                        fetch(window.CHATROX.apiUrl + '/messages', {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                message_id: messageId,
                                body: newHtml
                            })
                        })
                        .then(function (res) { return res.json(); })
                        .then(function (resData) {
                            if (resData.success) {
                                var savedBody = resData.body || newHtml;
                                setMessageParagraphContent(p, savedBody);

                                // Add an (Edited) label near the timestamp
                                var timeSpan = msg.querySelector('.dm-msg-time');
                                if (timeSpan && !msg.querySelector('.dm-msg-edited-label')) {
                                    var editedLabel = document.createElement('span');
                                    editedLabel.className = 'dm-msg-edited-label';
                                    editedLabel.textContent = '(Edited) ';
                                    editedLabel.style.fontSize = '11px';
                                    editedLabel.style.marginRight = '4px';
                                    timeSpan.insertBefore(editedLabel, timeSpan.firstChild);
                                }
                                
                                // Broadcast edit over websocket
                                if (window.ChatRoxWS) {
                                    window.ChatRoxWS.broadcast(conversationId, 'message_edited', {
                                        conversation_id: conversationId,
                                        message_id: messageId,
                                        body: savedBody
                                    });
                                }
                            }
                        })
                        .catch(function (err) {
                            console.error('Failed to edit message:', err);
                        });
                    };

                    var handleKeydown = function (e2) {
                        if (e2.key === 'Enter' && !e2.shiftKey) {
                            e2.preventDefault();
                            saveEdit();
                        } else if (e2.key === 'Escape') {
                            e2.preventDefault();
                            p.innerHTML = p.getAttribute('data-original-html') || p.innerHTML;
                            p.removeAttribute('data-original-html');
                            p.contentEditable = 'false';
                            p.removeEventListener('blur', saveEdit);
                            p.removeEventListener('keydown', handleKeydown);
                        }
                    };

                    p.addEventListener('blur', saveEdit);
                    p.addEventListener('keydown', handleKeydown);
                }
            });
        }

        /* Chat Details panel (Profile, Media, Files, Pinned) */
        var detailsOverlay = document.getElementById('dmDetailsOverlay');
        var detailsPanel = document.getElementById('dmDetailsPanel');
        var detailsSearchWrap = document.getElementById('dmDetailsSearchWrap');
        var detailsSearchInput = document.getElementById('dmDetailsSearch');
        var detailsPinnedList = document.getElementById('dmDetailsPinnedList');
        var detailsPinnedEmpty = document.getElementById('dmDetailsPinnedEmpty');
        var detailsMediaEmpty = document.getElementById('dmDetailsMediaEmpty');
        var detailsFilesEmpty = document.getElementById('dmDetailsFilesEmpty');
        var detailsTabBtns = document.querySelectorAll('.dm-details-tab');
        var currentDetailsTab = 'profile';
        var detailsContents = {
            profile: document.getElementById('dmDetailsContentProfile'),
            media: document.getElementById('dmDetailsContentMedia'),
            files: document.getElementById('dmDetailsContentFiles'),
            pinned: document.getElementById('dmDetailsContentPinned')
        };

        function setMessagePinnedState(msg, pinned) {
            if (!msg) return;
            if (pinned) {
                msg.setAttribute('data-pinned', '1');
                msg.classList.add('dm-chat-msg--pinned');
            } else {
                msg.removeAttribute('data-pinned');
                msg.classList.remove('dm-chat-msg--pinned');
            }
        }

        function getMessageIdFromEl(msg) {
            var matches = (msg && msg.id ? msg.id : '').match(/dm-msg-(\d+)/);
            return matches ? parseInt(matches[1], 10) : 0;
        }

        function scrollToChatMessage(messageId, query) {
            if (!messageId || !dmChatMessages) return;
            if (window.ChatRoxMessageFocus && window.ChatRoxMessageFocus.scrollAndHighlight(messageId, query, { container: dmChatMessages })) {
                return;
            }
            var msg = document.getElementById('dm-msg-' + messageId);
            if (!msg) return;
            if (msg.getAttribute('data-initially-hidden') === '1') {
                msg.classList.remove('dm-chat-msg--hidden');
                msg.removeAttribute('data-initially-hidden');
            }
            msg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            msg.classList.add('dm-chat-msg--highlight');
            setTimeout(function () {
                msg.classList.remove('dm-chat-msg--highlight');
            }, 2000);
        }

        function replaceChatWithContextMessages(data) {
            if (!data || !data.messages || !data.messages.length) return;

            var loadMoreWrap = document.getElementById('dmLoadMoreWrap');
            dmChatMessages.querySelectorAll('.dm-chat-msg, .dm-date-divider').forEach(function (el) {
                el.remove();
            });

            data.messages.slice().reverse().forEach(function (msg) {
                if (document.getElementById('dm-msg-' + msg.id)) return;
                var html = buildHistoryMessageHtml(msg);
                if (loadMoreWrap) {
                    loadMoreWrap.insertAdjacentHTML('beforebegin', html);
                } else {
                    dmChatMessages.insertAdjacentHTML('beforeend', html);
                }
            });

            oldestMessageId = parseInt(data.oldest_message_id || oldestMessageId, 10);
            hasOlderMessages = !!data.has_more_before;
            newestMessageId = parseInt(data.newest_message_id || newestMessageId, 10);
            hasNewerMessages = !!data.has_more_after;
            if (chatScreen) {
                chatScreen.dataset.oldestMessageId = String(oldestMessageId);
                chatScreen.dataset.hasOlder = hasOlderMessages ? '1' : '0';
            }
            updateLoadMoreVisibility();
            refreshIcons();
            normalizeAllMessageBubbles();
            initVoicePlayers(dmChatMessages);
            reconcileChatDateDividers();
        }

        function applyPendingMessageFocus() {
            if (!window.ChatRoxMessageFocus || !conversationId) return;

            var pending = window.ChatRoxMessageFocus.consumePending();
            if (!pending || !pending.message_id) return;
            if (String(pending.conversation_id) !== String(conversationId)) {
                window.ChatRoxMessageFocus.setPending(pending);
                return;
            }

            var messageId = pending.message_id;
            var query = pending.query || '';

            function tryFocus() {
                return window.ChatRoxMessageFocus.scrollAndHighlight(messageId, query, { container: dmChatMessages });
            }

            dmChatMessages.querySelectorAll('.dm-chat-msg--hidden').forEach(function (msg) {
                window.ChatRoxMessageFocus.revealMessage(msg);
            });

            if (tryFocus()) return;

            fetch(window.CHATROX.apiUrl + '/messages/context?conversation_id=' + encodeURIComponent(conversationId) +
                '&message_id=' + encodeURIComponent(messageId) + '&limit=30')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || !data.success || !data.messages || !data.messages.length) return;
                    replaceChatWithContextMessages(data);
                    requestAnimationFrame(function () {
                        tryFocus();
                    });
                })
                .catch(function (err) {
                    console.warn('[ChatRoxMessageFocus]', err);
                });
        }

        function unpinMessage(msg, skipApi) {
            if (!msg || msg.getAttribute('data-deleted-everyone') === '1') return;
            var messageId = getMessageIdFromEl(msg);
            if (!messageId) return;

            if (skipApi) {
                setMessagePinnedState(msg, false);
                if (detailsPanel && !detailsPanel.hasAttribute('hidden')) renderDetailsPinnedList();
                return;
            }

            fetch(window.CHATROX.apiUrl + '/messages/pin', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message_id: messageId, action: 'unpin' })
            })
            .then(function (res) { return res.json(); })
            .then(function (resData) {
                if (resData.success) {
                    setMessagePinnedState(msg, false);
                    if (detailsPanel && !detailsPanel.hasAttribute('hidden')) renderDetailsPinnedList();
                }
            })
            .catch(function (err) {
                console.error('Failed to unpin message:', err);
            });
        }

        function toggleMessagePin(msg) {
            if (!msg || msg.getAttribute('data-deleted-everyone') === '1') return;
            var messageId = getMessageIdFromEl(msg);
            if (!messageId) return;
            var action = msg.getAttribute('data-pinned') === '1' ? 'unpin' : 'pin';

            fetch(window.CHATROX.apiUrl + '/messages/pin', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message_id: messageId, action: action })
            })
            .then(function (res) { return res.json(); })
            .then(function (resData) {
                if (resData.success) {
                    setMessagePinnedState(msg, !!resData.is_pinned);
                    if (detailsPanel && !detailsPanel.hasAttribute('hidden')) renderDetailsPinnedList();
                }
            })
            .catch(function (err) {
                console.error('Failed to pin message:', err);
            });
        }

        function openDetailsPanel() {
            if (!detailsOverlay || !detailsPanel) return;
            detailsOverlay.removeAttribute('hidden');
            detailsPanel.removeAttribute('hidden');
            detailsOverlay.classList.add('dm-details-overlay--open');
            detailsPanel.classList.add('dm-details-panel--open');
            renderDetailsPinnedList();
            refreshIcons();
        }

        function closeDetailsPanel() {
            if (!detailsOverlay || !detailsPanel) return;
            detailsOverlay.classList.remove('dm-details-overlay--open');
            detailsPanel.classList.remove('dm-details-panel--open');
            detailsOverlay.setAttribute('hidden', '');
            detailsPanel.setAttribute('hidden', '');
            if (detailsSearchInput) detailsSearchInput.value = '';
        }

        function filterDetailsContent(q, tabName) {
            q = (q || '').toLowerCase();
            if (tabName === 'media') {
                var items = detailsContents.media ? detailsContents.media.querySelectorAll('.dm-details-media-thumb-btn') : [];
                items.forEach(function (btn) {
                    var img = btn.querySelector('.dm-details-media-thumb');
                    var alt = (img && img.alt ? img.alt : '').toLowerCase();
                    var src = (img && img.src ? img.src : '').toLowerCase();
                    var match = !q || alt.indexOf(q) !== -1 || src.split('/').pop().indexOf(q) !== -1;
                    btn.classList.toggle('dm-details-media-thumb--hidden', !match);
                });
                var visible = detailsContents.media ? detailsContents.media.querySelectorAll('.dm-details-media-thumb-btn:not(.dm-details-media-thumb--hidden)') : [];
                if (detailsMediaEmpty) {
                    detailsMediaEmpty.classList.toggle('dm-details-empty--show', visible.length === 0);
                    if (q && visible.length === 0) detailsMediaEmpty.textContent = 'No results found';
                    else if (!q && visible.length === 0) detailsMediaEmpty.textContent = 'No media shared yet';
                }
            } else if (tabName === 'files') {
                var items = detailsContents.files ? detailsContents.files.querySelectorAll('.dm-details-file-row') : [];
                items.forEach(function (row) {
                    var nameEl = row.querySelector('.dm-details-file-name');
                    var name = (nameEl ? nameEl.textContent : '').toLowerCase();
                    var match = !q || name.indexOf(q) !== -1;
                    row.classList.toggle('dm-details-file-row--hidden', !match);
                    if (nameEl) applyHighlight(nameEl, q, !!q);
                });
                var visibleFiles = detailsContents.files ? detailsContents.files.querySelectorAll('.dm-details-file-row:not(.dm-details-file-row--hidden)') : [];
                if (detailsFilesEmpty) {
                    detailsFilesEmpty.classList.toggle('dm-details-empty--show', visibleFiles.length === 0);
                    if (q && visibleFiles.length === 0) detailsFilesEmpty.textContent = 'No results found';
                    else if (!q && visibleFiles.length === 0) detailsFilesEmpty.textContent = 'No files shared yet';
                }
            } else if (tabName === 'pinned') {
                var items = detailsContents.pinned ? detailsContents.pinned.querySelectorAll('.dm-details-pinned-card') : [];
                items.forEach(function (card) {
                    var textEl = card.querySelector('.dm-details-pinned-text');
                    var text = (textEl ? textEl.textContent : '').toLowerCase();
                    var match = !q || text.indexOf(q) !== -1;
                    card.classList.toggle('dm-details-pinned-card--hidden', !match);
                    if (textEl) applyHighlight(textEl, q, !!q);
                });
                var visible = detailsContents.pinned ? detailsContents.pinned.querySelectorAll('.dm-details-pinned-card:not(.dm-details-pinned-card--hidden)') : [];
                if (detailsPinnedEmpty) {
                    detailsPinnedEmpty.classList.toggle('dm-details-pinned-empty--show', visible.length === 0);
                    if (q && visible.length === 0) detailsPinnedEmpty.textContent = 'No results found';
                    else if (!q && visible.length === 0) detailsPinnedEmpty.textContent = 'No pinned messages';
                }
            }
        }

        function switchDetailsTab(tabName) {
            currentDetailsTab = tabName;
            detailsTabBtns.forEach(function (btn) {
                var isActive = btn.getAttribute('data-tab') === tabName;
                btn.classList.toggle('dm-details-tab--active', isActive);
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            Object.keys(detailsContents).forEach(function (key) {
                var panel = detailsContents[key];
                if (!panel) return;
                var show = key === tabName;
                panel.classList.toggle('dm-details-content--hidden', !show);
                if (show) panel.removeAttribute('hidden'); else panel.setAttribute('hidden', '');
            });

            if (detailsSearchWrap) {
                var showSearch = ['media', 'files', 'pinned'].indexOf(tabName) !== -1;
                detailsSearchWrap.classList.toggle('dm-details-search-wrap--hidden', !showSearch);
                if (detailsSearchInput) {
                    detailsSearchInput.value = '';
                    filterDetailsContent('', tabName);
                }
            }

            if (tabName === 'pinned') renderDetailsPinnedList();
            refreshIcons();
        }

        function renderDetailsPinnedList() {
            if (!detailsPinnedList || !detailsPinnedEmpty) return;
            var pinned = dmChatMessages ? dmChatMessages.querySelectorAll('.dm-chat-msg[data-pinned="1"]') : [];
            detailsPinnedList.innerHTML = '';
            pinned.forEach(function (msg) {
                var messageId = getMessageIdFromEl(msg);
                var text = getPinnedSnippet(msg);
                var card = document.createElement('div');
                card.className = 'dm-details-pinned-card';
                card.setAttribute('data-message-id', String(messageId));
                card.innerHTML =
                    '<div class="dm-details-pinned-card-header">' +
                    '<span class="dm-details-pinned-label"><i data-lucide="pin" size="12"></i> PINNED MESSAGE</span>' +
                    '<button type="button" class="dm-details-pinned-unpin js-details-unpin" aria-label="Unpin"><i data-lucide="x" size="14"></i></button>' +
                    '</div>' +
                    '<p class="dm-details-pinned-text"></p>';
                detailsPinnedList.appendChild(card);
                var textEl = card.querySelector('.dm-details-pinned-text');
                if (textEl) textEl.textContent = text;

                card.addEventListener('click', function (e) {
                    if (e.target.closest('.js-details-unpin')) return;
                    scrollToChatMessage(messageId);
                    closeDetailsPanel();
                });

                var unpinBtn = card.querySelector('.js-details-unpin');
                if (unpinBtn) {
                    unpinBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        unpinMessage(msg);
                    });
                }
            });
            detailsPinnedEmpty.classList.toggle('dm-details-pinned-empty--show', pinned.length === 0);
            refreshIcons();
        }

        document.querySelectorAll('.js-chat-details-open').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openDetailsPanel();
            });
        });
        if (detailsOverlay) {
            detailsOverlay.addEventListener('click', function () { closeDetailsPanel(); });
        }
        if (document.querySelector('.js-details-close')) {
            document.querySelector('.js-details-close').addEventListener('click', function (e) {
                e.preventDefault();
                closeDetailsPanel();
            });
        }
        detailsTabBtns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var tab = btn.getAttribute('data-tab');
                if (tab) switchDetailsTab(tab);
            });
        });

        if (detailsSearchInput) {
            detailsSearchInput.addEventListener('input', function () {
                filterDetailsContent(this.value, currentDetailsTab);
            });
        }

        document.querySelectorAll('.js-details-media-jump').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var messageId = parseInt(btn.getAttribute('data-message-id') || '0', 10);
                if (!messageId) return;
                scrollToChatMessage(messageId);
                closeDetailsPanel();
            });
        });

        if (document.querySelector('.js-reply-cancel')) {
            document.querySelector('.js-reply-cancel').addEventListener('click', function (e) {
                e.preventDefault();
                clearReply();
            });
        }

        // --- WEBSOCKET EVENT HANDLERS ---
        var chatScreen = document.querySelector('.dm-chat-screen');
        var conversationId = chatScreen ? chatScreen.dataset.conversationId : null;

        var hasUnread = false;

        function markAsRead(force) {
            if (!conversationId) return;
            if (!force && (document.hidden || !document.hasFocus())) {
                hasUnread = true;
                return;
            }
            hasUnread = false;
            fetch(window.CHATROX.apiUrl + '/messages/read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ conversation_id: conversationId })
            })
            .then(function (res) { return res.json(); })
            .then(function (resData) {
                if (resData.success && resData.last_read_message_id) {
                    if (window.ChatRoxWS) {
                        window.ChatRoxWS.broadcast(conversationId, 'conversation_read', {
                            conversation_id: conversationId,
                            workspace_member_id: window.CHATROX.user.workspace_member_id,
                            last_read_message_id: resData.last_read_message_id
                        });
                    }
                    if (window.ChatRoxHomeLive) {
                        window.ChatRoxHomeLive.scheduleRefresh();
                    }
                }
            })
            .catch(function(err) {
                console.error('Failed to mark conversation read:', err);
            });
        }

        // Listen for visibility/focus change
        listen(document, 'visibilitychange', function () {
            if (!document.hidden && document.hasFocus() && hasUnread) {
                markAsRead();
            }
        });
        listen(window, 'focus', function () {
            if (hasUnread) {
                markAsRead();
            }
        });

        function handleSubscribe() {
            if (!conversationId || !window.ChatRoxWS) return;
            if (activeConversationId && activeConversationId !== conversationId) {
                window.ChatRoxWS.unsubscribe(activeConversationId);
            }
            activeConversationId = conversationId;
            window.ChatRoxWS.subscribe(conversationId);
        }

        var withUsername = chatScreen ? chatScreen.dataset.withUsername : null;
        if (withUsername) {
            var sidebarItem = document.querySelector('.dm-list a[data-dm-username="' + withUsername + '"]');
            var unreadEl = sidebarItem ? sidebarItem.querySelector('.unread-count') : null;
            var unreadCount = unreadEl ? parseInt(unreadEl.textContent, 10) : 0;
            if (unreadCount > 0) {
                var msgEls = dmChatMessages.querySelectorAll('.dm-chat-msg');
                var themCount = 0;
                var dividerInserted = false;
                for (var i = 0; i < msgEls.length; i++) {
                    var el = msgEls[i];
                    if (!el.classList.contains('dm-chat-msg--me')) {
                        themCount++;
                        if (themCount === unreadCount) {
                            var dividerHtml = '<div class="dm-unread-divider"><span class="dm-unread-divider-text">New Messages</span></div>';
                            el.insertAdjacentHTML('afterend', dividerHtml);
                            hasUnread = true;
                            dividerInserted = true;
                            break;
                        }
                    }
                }
                // Fallback: if unread count exceeds loaded messages, insert divider at oldest visible message boundary
                if (!dividerInserted && msgEls.length > 0 && unreadCount > 0) {
                    var lastMsg = msgEls[msgEls.length - 1];
                    var dividerHtml = '<div class="dm-unread-divider"><span class="dm-unread-divider-text">New Messages</span></div>';
                    lastMsg.insertAdjacentHTML('afterend', dividerHtml);
                    hasUnread = true;
                }
            }
        }

        handleSubscribe();
        markAsRead(true);
        if (dmChatInput) {
            dmChatInput.focus();
        }
        listen(document, 'chatrox:ws_connected', function () {
            handleSubscribe();
            markAsRead(true);
        });

        // Notify global handlers that this conversation is now open (clears unread badges)
        var withUsername = chatScreen ? chatScreen.dataset.withUsername : null;
        console.log('[ChatRoxDMs] initChat: withUsername =', withUsername, 'conversationId =', conversationId);
        if (withUsername) {
            document.dispatchEvent(new CustomEvent('chatrox:conversation_opened', {
                detail: { with_username: withUsername, conversation_id: conversationId }
            }));
        }

        // Receiver: New Messages
        listen(document, 'chatrox:new_message', function (e) {
            var msg = e.detail;
            if (!msg) return;

            var currentMemberId = window.CHATROX.user.workspace_member_id;
            var side = (parseInt(msg.sender_id, 10) === parseInt(currentMemberId, 10)) ? 'me' : 'them';

            if (String(msg.conversation_id) !== String(conversationId)) return;
            
            if (hasNewerMessages) {
                return;
            }
            
            // Avoid duplicate rendering
            if (document.getElementById('dm-msg-' + msg.id)) return;
            
            var bubbleContent = buildMessageBubbleContent(msg);
            var bubbleClasses = getMessageBubbleClasses(msg);

            // Capture scroll position BEFORE inserting the new message
            var isNearBottom = Math.abs(dmChatMessages.scrollTop) < 150;

            if (side === 'me') {
                renderMessage('dm-msg-' + msg.id, bubbleContent, bubbleClasses, msg.time_label, 'me', msg.read_status, msg.created_at);
                dmChatMessages.scrollTop = 0;
            } else {
                renderMessage('dm-msg-' + msg.id, bubbleContent, bubbleClasses, msg.time_label, 'them', null, msg.created_at);
                if (isNearBottom) {
                    // User is at (or near) bottom — scroll to show new message and mark read
                    dmChatMessages.scrollTop = 0;
                    markAsRead();
                } else {
                    // User is scrolled up reading history — show divider at bottom boundary (visible)
                    if (!dmChatMessages.querySelector('.dm-unread-divider')) {
                        var dividerHtml = '<div class="dm-unread-divider"><span class="dm-unread-divider-text">New Messages</span></div>';
                        var msgEl = document.getElementById('dm-msg-' + msg.id);
                        if (msgEl) {
                            msgEl.insertAdjacentHTML('afterend', dividerHtml);
                        }
                    }
                    hasUnread = true;
                }
            }
            appendAttachmentsToDetails(msg);
            refreshIcons();
            if (window.highlightCodeBlocks) {
                window.highlightCodeBlocks(dmChatMessages);
            }
        });

        // Receiver: Conversation Read Cursors
        listen(document, 'chatrox:conversation_read', function (e) {
            var data = e.detail;
            if (!data || String(data.conversation_id) !== String(conversationId)) return;
            
            var currentMemberId = window.CHATROX.user.workspace_member_id;
            if (parseInt(data.workspace_member_id, 10) === parseInt(currentMemberId, 10)) {
                return;
            }
            
            var myMsgs = dmChatMessages.querySelectorAll('.dm-chat-msg--me');
            myMsgs.forEach(function (msgEl) {
                var matches = (msgEl.id || '').match(/dm-msg-(\d+)/);
                if (matches) {
                    var msgId = parseInt(matches[1], 10);
                    if (msgId <= parseInt(data.last_read_message_id, 10)) {
                        setReadReceipt(msgEl, 'read');
                    }
                }
            });

            if (window.ChatRoxDmSidebar && typeof window.ChatRoxDmSidebar.upgradeReceiptByConversation === 'function') {
                window.ChatRoxDmSidebar.upgradeReceiptByConversation(data.conversation_id, 'read');
            }
        });

        // Receiver: Reactions
        listen(document, 'chatrox:message_reaction', function (e) {
            var data = e.detail;
            if (!data || String(data.conversation_id) !== String(conversationId)) return;
            updateMessageReactions(data.message_id, data.emoji, data.action, data.count, data.prev_emoji, data.prev_count, data.actor_member_id);
        });

        // Receiver: Deletions
        listen(document, 'chatrox:message_deleted', function (e) {
            var data = e.detail;
            if (!data || String(data.conversation_id) !== String(conversationId)) return;
            var msgEl = document.getElementById('dm-msg-' + data.message_id);
            if (!msgEl) return;
            applyDeletedForEveryoneUI(msgEl);
        });

        // ── Receiver: Edits ──────────────────────────────────────────────────
        listen(document, 'chatrox:message_edited', function (e) {
            var data = e.detail;
            if (!data || String(data.conversation_id) !== String(conversationId)) return;
            var msgEl = document.getElementById('dm-msg-' + data.message_id);
            if (!msgEl) return;
            
            var p = msgEl.querySelector('.dm-msg-bubble p');
            if (p) {
                setMessageParagraphContent(p, data.body);
            }
            
            var timeSpan = msgEl.querySelector('.dm-msg-time');
            if (timeSpan && !msgEl.querySelector('.dm-msg-edited-label')) {
                var editedLabel = document.createElement('span');
                editedLabel.className = 'dm-msg-edited-label';
                editedLabel.textContent = '(Edited) ';
                editedLabel.style.fontSize = '11px';
                editedLabel.style.marginRight = '4px';
                timeSpan.insertBefore(editedLabel, timeSpan.firstChild);
            }
        });

        // ═══════════════════════════════════════════════════════════════
        //  TYPING INDICATOR
        // ═══════════════════════════════════════════════════════════════

        var typingTimer = null;
        var isTypingSent = false;
        var TYPING_TIMEOUT = 2500; // ms of inactivity before typing_stop

        /** Show the animated bubble below messages (column-reverse: afterbegin = visual bottom) */
        function showTypingBubble() {
            if (document.getElementById('dmTypingBubble')) return;
            if (!dmChatMessages) return;

            var bubble = document.createElement('div');
            bubble.className = 'dm-chat-msg dm-chat-msg--them dm-typing-bubble';
            bubble.id = 'dmTypingBubble';
            bubble.innerHTML =
                '<div class="dm-msg-body">' +
                    '<div class="dm-msg-bubble dm-msg-bubble--typing">' +
                        '<span class="dm-typing-dot"></span>' +
                        '<span class="dm-typing-dot"></span>' +
                        '<span class="dm-typing-dot"></span>' +
                    '</div>' +
                '</div>';
            dmChatMessages.insertAdjacentElement('afterbegin', bubble);
        }

        /** Remove the animated bubble */
        function hideTypingBubble() {
            var bubble = document.getElementById('dmTypingBubble');
            if (bubble) bubble.remove();
        }

        /** Broadcast typing_start to the conversation */
        function sendTypingStart() {
            if (!window.ChatRoxWS || !conversationId) return;
            window.ChatRoxWS.broadcast(conversationId, 'typing_start', {
                conversation_id: conversationId,
                sender_id: window.CHATROX.user.workspace_member_id,
                sender_username: window.CHATROX.user.username
            });
        }

        /** Broadcast typing_stop to the conversation */
        function sendTypingStop() {
            if (!window.ChatRoxWS) return;
            window.ChatRoxWS.broadcast(conversationId, 'typing_stop', {
                conversation_id: conversationId,
                sender_id: window.CHATROX.user.workspace_member_id,
                sender_username: window.CHATROX.user.username
            });
        }

        function handleTypingInput() {
            var hasText = (dmChatInput.textContent || '').trim().length > 0;
            if (!hasText) {
                if (isTypingSent) {
                    isTypingSent = false;
                    clearTimeout(typingTimer);
                    sendTypingStop();
                }
                return;
            }

            if (!isTypingSent) {
                isTypingSent = true;
                sendTypingStart();
            }

            clearTimeout(typingTimer);
            activeTypingTimer = setTimeout(function () {
                isTypingSent = false;
                sendTypingStop();
                activeTypingTimer = null;
            }, TYPING_TIMEOUT);
        }

        // Listen for input events in the message box (input + keydown for contenteditable)
        if (dmChatInput) {
            dmChatInput.addEventListener('input', handleTypingInput);
            dmChatInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === 'Escape' || e.ctrlKey || e.metaKey || e.altKey) return;
                handleTypingInput();
            });

            // Also stop typing on form submit
            dmChatForm && dmChatForm.addEventListener('submit', function () {
                clearTimeout(typingTimer);
                if (isTypingSent) {
                    isTypingSent = false;
                    sendTypingStop();
                }
                hideTypingBubble();
            }, true);
        }

        // Receive typing_start from the other person
        listen(document, 'chatrox:typing_start', function (e) {
            var data = e.detail;
            if (!data || String(data.conversation_id) !== String(conversationId)) return;
            // Ignore if it's from ourselves
            if (parseInt(data.sender_id, 10) === parseInt(window.CHATROX.user.workspace_member_id, 10)) return;
            showTypingBubble();
        });

        // Receive typing_stop from the other person
        listen(document, 'chatrox:typing_stop', function (e) {
            var data = e.detail;
            if (!data || String(data.conversation_id) !== String(conversationId)) return;
            if (parseInt(data.sender_id, 10) === parseInt(window.CHATROX.user.workspace_member_id, 10)) return;
            hideTypingBubble();
        });

        // Also hide the bubble when a new message arrives in this conversation
        listen(document, 'chatrox:new_message', function (e) {
            var msg = e.detail;
            if (!msg || String(msg.conversation_id) !== String(conversationId)) return;
            hideTypingBubble();
        });

        initVoicePlayers(dmChatMessages);
        requestAnimationFrame(function () {
            initVoicePlayers(dmChatMessages);
        });

        listen(dmChatMessages, 'click', function (e) {
            var playBtn = e.target.closest('.js-voice-play');
            if (!playBtn || !dmChatMessages.contains(playBtn)) return;

            e.preventDefault();
            e.stopPropagation();

            var wrap = playBtn.closest('.dm-msg-voice');
            if (!wrap) return;

            bindVoicePlayerControls(wrap);

            var media = wrap.querySelector('.dm-voice-audio');
            if (!media) return;

            if (!media.paused && !media.ended) {
                media.pause();
                return;
            }

            playVoiceMedia(wrap, media, playBtn);
        });
        // ═══════════════════════════════════════════════════════════════
        //  DRAFT AUTO-SAVE
        // ═══════════════════════════════════════════════════════════════
        if (dmChatInput && conversationId) {
            var cachedDraft = localStorage.getItem('chatrox_draft_' + conversationId);
            if (cachedDraft) {
                dmChatInput.textContent = cachedDraft;
                // Place cursor at the end
                try {
                    var range = document.createRange();
                    var sel = window.getSelection();
                    range.selectNodeContents(dmChatInput);
                    range.collapse(false);
                    sel.removeAllRanges();
                    sel.addRange(range);
                } catch (e) {
                    console.warn('Failed to restore cursor position', e);
                }
            }

            listen(dmChatInput, 'input', function () {
                var text = dmChatInput.textContent || '';
                if (text.trim() === '') {
                    localStorage.removeItem('chatrox_draft_' + conversationId);
                } else {
                    localStorage.setItem('chatrox_draft_' + conversationId, text);
                }
            });

            if (dmChatForm) {
                listen(dmChatForm, 'submit', function () {
                    localStorage.removeItem('chatrox_draft_' + conversationId);
                }, true);
            }
        }

        applyPendingMessageFocus();
        reconcileChatDateDividers();
        dmChatMessages.scrollTop = 0;
        if (window.highlightCodeBlocks) {
            window.highlightCodeBlocks(dmChatMessages);
        }
    }

    var chatPageLoadHandled = false;

    document.addEventListener('chatrox:page_unload', function () {
        if (chatSessionAbort) {
            chatSessionAbort.abort();
            chatSessionAbort = null;
        }
        if (activeTypingTimer) {
            clearTimeout(activeTypingTimer);
            activeTypingTimer = null;
        }
        if (activeConversationId && window.ChatRoxWS) {
            window.ChatRoxWS.unsubscribe(activeConversationId);
            activeConversationId = null;
        }
    });

    document.addEventListener('chatrox:page_load', function (e) {
        chatPageLoadHandled = true;
        initChat(e.detail);
    });

    // Safety net if initial page_load fired before this script executed.
    setTimeout(function () {
        if (chatPageLoadHandled) return;
        if (!document.getElementById('dmChatForm')) return;
        initChat({ initial: true, recovered: true });
    }, 0);
})();
