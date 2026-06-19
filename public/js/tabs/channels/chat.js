/**
 * DMs Chat screen – message send, scroll, etc.
 * Loaded on all pages; runs only when chat elements exist.
 */
(function () {
    function initChat() {
        var dmChatForm = document.getElementById('dmChatForm');
        var dmChatInput = document.getElementById('dmChatInput');
        var dmChatMessages = document.getElementById('dmChatMessages');
        var dmChatFileInput = document.getElementById('dmChatFileInput');
        var dmChatAttachedWrap = document.getElementById('dmChatAttachedWrap');
        var dmReplyPreview = document.getElementById('dmReplyPreview');
        var dmReplyPreviewText = document.getElementById('dmReplyPreviewText');
        var attachedFiles = [];
        var currentReplyMsg = null;
        if (!dmChatForm || !dmChatInput || !dmChatMessages) return;

        function getReplySnippet(msg) {
            var bubble = msg ? msg.querySelector('.dm-msg-bubble') : null;
            if (!bubble) return '';
            var p = bubble.querySelector('p');
            if (p && (p.textContent || '').trim()) return (p.textContent || '').trim();
            var file = bubble.querySelector('.dm-file-name');
            if (file && file.textContent) return file.textContent.trim();
            if (bubble.querySelector('.dm-msg-images')) return 'Photo';
            return (bubble.textContent || '').trim();
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
                if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
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

        var CH_SEEN_COUNT = 5;
        var CH_AVATAR_LIMIT = 2;

        function channelReadReceiptHtml(readCount, memberCount, readers, notRead) {
            readCount = readCount || 0;
            memberCount = memberCount || 8;
            readers = readers || [];
            notRead = notRead || [];
            var avatarsHtml = '';
            for (var i = 0; i < Math.min(CH_AVATAR_LIMIT, readers.length); i++) {
                avatarsHtml += '<img src="' + escapeHtml(readers[i].avatar) + '" alt="' + escapeHtml(readers[i].name) + '">';
            }
            if (readCount > CH_AVATAR_LIMIT) {
                avatarsHtml += '<span class="ch-read-receipt-more">+' + (readCount - CH_AVATAR_LIMIT) + '</span>';
            }
            return '<button type="button" class="ch-read-receipt js-channel-seen-by"' +
                ' data-readers="' + escapeHtml(JSON.stringify(readers)) + '"' +
                ' data-not-read="' + escapeHtml(JSON.stringify(notRead)) + '"' +
                ' data-read-count="' + readCount + '" data-member-count="' + memberCount + '"' +
                ' aria-label="Seen by ' + readCount + ' members">' +
                '<span class="ch-read-receipt-label">Seen by ' + readCount + '</span>' +
                (avatarsHtml ? '<span class="ch-read-receipt-avatars" aria-hidden="true">' + avatarsHtml + '</span>' : '') +
                '</button>';
        }

        var channelMembersPool = [
            { name: 'Emma Williams', avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150' },
            { name: 'Oliver Mitchell', avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150' },
            { name: 'Charlotte Anderson', avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=150' },
            { name: 'Liam Carter', avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=150' },
            { name: 'Noah Bennett', avatar: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&q=80&w=150' },
            { name: 'Sophia Reynolds', avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=150' },
            { name: 'Emily Chen', avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=150' },
            { name: 'Michael Torres', avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=150' }
        ];

        function buildChannelReadData(readCount) {
            readCount = readCount || CH_SEEN_COUNT;
            var memberCount = channelMembersPool.length;
            readCount = Math.min(readCount, memberCount);
            var readers = [];
            var notRead = [];
            for (var i = 0; i < readCount; i++) {
                readers.push({
                    name: channelMembersPool[i].name,
                    avatar: channelMembersPool[i].avatar,
                    read_at: 'Just now'
                });
            }
            for (var j = readCount; j < memberCount; j++) {
                notRead.push(channelMembersPool[j].name);
            }
            return { read_count: readCount, member_count: memberCount, read_by: readers, not_read: notRead };
        }

        function updateMessageReactions(messageId, emoji, action, count) {
            var msgEl = document.getElementById('dm-msg-' + messageId);
            if (!msgEl) return;
            var reactionsEl = msgEl.querySelector('.dm-msg-reactions');
            if (!reactionsEl) return;

            var existing = null;
            reactionsEl.querySelectorAll('.dm-reaction-bubble').forEach(function (b) {
                if (b.getAttribute('data-emoji') === emoji) existing = b;
            });

            if (action === 'removed') {
                if (count <= 0) {
                    if (existing) existing.remove();
                } else if (existing) {
                    existing.querySelector('.dm-reaction-count').textContent = count;
                }
            } else { // added
                if (existing) {
                    existing.querySelector('.dm-reaction-count').textContent = count;
                } else {
                    var bubble = document.createElement('span');
                    bubble.className = 'dm-reaction-bubble';
                    bubble.setAttribute('data-emoji', emoji);
                    bubble.innerHTML = '<span class="dm-reaction-emoji">' + emoji + '</span> <span class="dm-reaction-count">' + count + '</span>';
                    reactionsEl.appendChild(bubble);
                }
            }
        }

        function renderMessage(id, content, classes, time, side, senderName) {
            side = side || 'me';
            var sideClass = side === 'me' ? 'dm-chat-msg--me' : 'dm-chat-msg--them';
            var senderHtml = (side === 'them' && senderName) ? '<span class="dm-msg-sender">' + escapeHtml(senderName) + '</span>' : '';
            var actionsBar = '<div class="dm-msg-actions" aria-label="Message actions">' +
                '<button type="button" class="dm-msg-action js-msg-react" title="Reaction" aria-label="Reaction"><i data-lucide="smile-plus" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-reply" title="Reply" aria-label="Reply"><i data-lucide="reply" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-pin" title="Pin" aria-label="Pin"><i data-lucide="pin" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-forward" title="Forward" aria-label="Forward"><i data-lucide="forward" size="16"></i></button>' +
                '<span class="dm-msg-actions-sep" aria-hidden="true"></span>' +
                '<button type="button" class="dm-msg-action dm-msg-action--delete js-msg-delete" title="Delete" aria-label="Delete"><i data-lucide="trash-2" size="16"></i></button>' +
                '</div>';
            var readData = buildChannelReadData(CH_SEEN_COUNT);
            var metaHtml = '<div class="dm-msg-meta"><span class="dm-msg-time">' + time + '</span>' +
                (side === 'me' ? channelReadReceiptHtml(readData.read_count, readData.member_count, readData.read_by, readData.not_read) : '') +
                '</div>';
            var body = '<div class="dm-msg-body">' + senderHtml + '<div class="' + classes + '">' + content + '</div><div class="dm-msg-reactions"></div>' + metaHtml + '</div>';
            var msgHtml = '<div class="dm-chat-msg ' + sideClass + '" id="' + id + '" data-msg-index="">' + body + actionsBar + '</div>';
            dmChatMessages.insertAdjacentHTML('afterbegin', msgHtml);
            if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
        }

        var allowedTags = { b: 1, i: 1, s: 1, u: 1, strong: 1, em: 1, ul: 1, ol: 1, li: 1, p: 1, br: 1, span: 1, div: 1 };
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
                        if (name !== 'style' && name !== 'align') node.removeAttribute(name);
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

        function getFileExtInfo(name, mimeType) {
            var parts = String(name || 'File').split('.');
            var extRaw = parts.length > 1 ? parts.pop() : '';
            var extSlug = extRaw.toLowerCase().replace(/[^a-z0-9]/g, '') || 'file';
            if (extSlug === 'file' && mimeType) {
                if (mimeType.indexOf('pdf') !== -1) extSlug = 'pdf';
                else if (mimeType.indexOf('spreadsheet') !== -1 || mimeType.indexOf('excel') !== -1 || mimeType === 'text/csv') extSlug = 'csv';
                else if (mimeType.indexOf('word') !== -1 || mimeType.indexOf('document') !== -1) extSlug = 'doc';
                else if (mimeType.indexOf('zip') !== -1 || mimeType.indexOf('compressed') !== -1) extSlug = 'zip';
                else if (mimeType.indexOf('image/') === 0) extSlug = 'png';
                else if (mimeType.indexOf('text/') === 0) extSlug = 'txt';
            }
            if (!extRaw && extSlug !== 'file') {
                extRaw = extSlug;
            }
            var typeLabels = {
                txt: 'Text Document',
                pdf: 'PDF Document',
                doc: 'Word Document',
                docx: 'Word Document',
                xls: 'Spreadsheet',
                xlsx: 'Spreadsheet',
                csv: 'Spreadsheet',
                zip: 'Archive',
                rar: 'Archive',
                png: 'Image',
                jpg: 'Image',
                jpeg: 'Image'
            };
            var iconMap = {
                pdf: 'file-text',
                txt: 'file-text',
                doc: 'file-text',
                docx: 'file-text',
                xls: 'file-spreadsheet',
                xlsx: 'file-spreadsheet',
                csv: 'file-spreadsheet',
                zip: 'archive',
                rar: 'archive',
                png: 'image',
                jpg: 'image',
                jpeg: 'image'
            };
            return {
                extLabel: (extRaw || extSlug).toUpperCase(),
                extSlug: extSlug,
                typeLabel: typeLabels[extSlug] || 'File',
                lucideIcon: iconMap[extSlug] || 'file'
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
                '<div class="dm-file-icon dm-file-icon--' + escapeHtml(info.extSlug) + '" aria-hidden="true">' +
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

        dmChatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                var event = new Event('submit', { cancelable: true });
                dmChatForm.dispatchEvent(event);
            }
        });

        dmChatInput.addEventListener('paste', function (e) {
            e.preventDefault();
            var clipboard = e.clipboardData || window.clipboardData;
            if (!clipboard) return;
            var html = clipboard.getData('text/html');
            var text = clipboard.getData('text/plain');
            if (html) {
                document.execCommand('insertHTML', false, sanitizeHtml(html));
            } else if (text) {
                document.execCommand('insertText', false, text);
            }
        });


        dmChatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var raw = dmChatInput.innerHTML;
            var text = (dmChatInput.textContent || '').trim();
            if (!text && (!attachedFiles || attachedFiles.length === 0)) return;

            var conversationId = document.querySelector('.dm-chat-screen').dataset.conversationId;
            var now = new Date();
            var timeStr = now.getHours() > 12
                ? (now.getHours() - 12) + ':' + String(now.getMinutes()).padStart(2, '0') + ' PM'
                : now.getHours() + ':' + String(now.getMinutes()).padStart(2, '0') + ' AM';

            // Store copies
            var attachedFilesCopy = [...attachedFiles];
            var currentReplyMsgCopy = currentReplyMsg;

            attachedFiles = [];
            if (dmChatFileInput) dmChatFileInput.value = '';
            if (dmChatAttachedWrap) {
                dmChatAttachedWrap.innerHTML = '';
                dmChatAttachedWrap.setAttribute('hidden', '');
            }
            dmChatInput.innerHTML = '';
            ensurePlaceholder();
            clearReply();

            // 1. Upload files first if any
            var uploadPromise = Promise.resolve([]);
            if (attachedFilesCopy && attachedFilesCopy.length) {
                var formData = new FormData();
                attachedFilesCopy.forEach(function (f) {
                    formData.append('files[]', f.file);
                });
                uploadPromise = fetch(window.CHATROX.baseUrl + '/api/files/upload', {
                    method: 'POST',
                    body: formData
                })
                .then(function (res) { return res.json(); })
                .then(function (resData) {
                    return (resData.success && resData.files) ? resData.files.map(function(f) { return f.id; }) : [];
                })
                .catch(function (err) {
                    console.error('File upload failed:', err);
                    return [];
                });
            }

            // 2. Send message
            uploadPromise.then(function (fileIds) {
                var replyToId = null;
                if (currentReplyMsgCopy) {
                    var matches = (currentReplyMsgCopy.id || '').match(/dm-msg-(\d+)/);
                    if (matches) {
                        replyToId = parseInt(matches[1], 10);
                    }
                }

                fetch(window.CHATROX.baseUrl + '/api/messages/send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversation_id: conversationId,
                        body: text ? raw : '',
                        file_ids: fileIds,
                        reply_to_id: replyToId
                    })
                })
                .then(function (res) { return res.json(); })
                .then(function (resData) {
                    if (resData.success && resData.message) {
                        var msg = resData.message;
                        
                        var bubbleContent = '';
                        if (msg.reply_to_id) {
                            bubbleContent += '<div class="dm-msg-reply-wrap" data-reply-to-id="dm-msg-' + msg.reply_to_id + '"><div class="dm-msg-reply-preview">Replying...</div></div>';
                        }
                        
                        if (msg.body) {
                            bubbleContent += '<p>' + msg.body + '</p>';
                        }

                        if (msg.attachments && msg.attachments.length) {
                            var images = msg.attachments.filter(function(a) { return a.category === 'image'; });
                            var docs = msg.attachments.filter(function(a) { return a.category !== 'image'; });

                            if (images.length === 1) {
                                bubbleContent += '<div class="dm-msg-images dm-msg-images--single"><img src="' + images[0].url + '" alt="" class="dm-msg-img js-msg-img" loading="lazy"></div>';
                            } else if (images.length > 1) {
                                var imgHtml = '';
                                images.forEach(function(img) {
                                    imgHtml += '<img src="' + img.url + '" alt="" class="dm-msg-img js-msg-img" loading="lazy">';
                                });
                                bubbleContent += '<div class="dm-msg-images dm-msg-images--grid dm-msg-images--count-' + Math.min(4, images.length) + '">' + imgHtml + '</div>';
                            }

                            if (docs.length) {
                                bubbleContent += '<div class="dm-msg-files">';
                                docs.forEach(function(d) {
                                    bubbleContent += buildFileCardHtml(d.original_name, (d.size_bytes/1024).toFixed(1) + ' KB', d.url, d.mime_type);
                                });
                                bubbleContent += '</div>';
                            }
                        }

                        var bubbleClasses = 'dm-msg-bubble';
                        if (msg.attachments && msg.attachments.length) {
                            bubbleClasses += ' dm-msg-bubble--media';
                        }

                        renderMessage('dm-msg-' + msg.id, bubbleContent, bubbleClasses, msg.time_label, 'me');
                        
                        var chatScreen = document.querySelector('.dm-chat-screen');
                        var activeChannelId = chatScreen ? chatScreen.dataset.channelId : null;
                        if (activeChannelId) {
                            updateChannelSidebarItem(activeChannelId, msg.time_label, 'me');
                        }

                        // Broadcast message over websocket
                        if (window.ChatRoxWS) {
                            window.ChatRoxWS.broadcast(conversationId, 'new_message', msg);
                        }
                    }
                })
                .catch(function (err) {
                    console.error('Failed to send message:', err);
                });
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
            document.addEventListener('click', function (e) {
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
        var giphyApiKey = (window.CHATROX && window.CHATROX.integrations && window.CHATROX.integrations.giphy_api_key)
            ? window.CHATROX.integrations.giphy_api_key
            : '';
        var gifSearchTimeout = null;

        if (gifPicker && gifToggle && gifResults) {
            function repositionGifPicker() {
                if (!gifPicker.hasAttribute('hidden')) {
                    positionFloater(gifToggle, gifPicker);
                }
            }

            function fetchGIFs(query) {
                var url = query
                    ? 'https://api.giphy.com/v1/gifs/search?api_key=' + giphyApiKey + '&q=' + encodeURIComponent(query) + '&limit=20'
                    : 'https://api.giphy.com/v1/gifs/trending?api_key=' + giphyApiKey + '&limit=20';

                gifResults.innerHTML = '<div class="dm-gif-loading">Loading...</div>';

                fetch(url)
                    .then(function (res) { return res.json(); })
                    .then(function (result) {
                        gifResults.innerHTML = '';
                        var data = result.data || [];
                        if (data.length === 0) {
                            gifResults.innerHTML = '<div class="dm-gif-no-results">No GIFs found.</div>';
                            repositionGifPicker();
                            return;
                        }
                        data.forEach(function (item) {
                            var gifUrl = item.images.fixed_height.url;
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'dm-gif-btn';
                            btn.setAttribute('data-gif', gifUrl);
                            btn.innerHTML = '<img src="' + gifUrl + '" alt="GIF" loading="lazy">';
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
                        gifResults.innerHTML = '<div class="dm-gif-error">Failed to load GIFs.</div>';
                        repositionGifPicker();
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

            document.addEventListener('click', function (e) {
                if (gifPicker.hasAttribute('hidden')) return;
                if (!gifPicker.contains(e.target) && !gifToggle.contains(e.target)) {
                    gifPicker.setAttribute('hidden', '');
                    gifToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        function sendGifMessage(src) {
            if (!src) return;
            
            fetch(window.CHATROX.baseUrl + '/api/messages/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    body: src,
                    file_ids: []
                })
            })
            .then(function (res) { return res.json(); })
            .then(function (resData) {
                if (resData.success && resData.message) {
                    var msg = resData.message;
                    
                    var bubbleContent = '<div class="dm-msg-images dm-msg-images--single"><img src="' + escapeHtml(msg.body) + '" alt="" class="dm-msg-img js-msg-img" loading="lazy"></div>';
                    var bubbleClasses = 'dm-msg-bubble dm-msg-bubble--media';

                    renderMessage('dm-msg-' + msg.id, bubbleContent, bubbleClasses, msg.time_label, 'me');
                    
                    var chatScreen = document.querySelector('.dm-chat-screen');
                    var activeChannelId = chatScreen ? chatScreen.dataset.channelId : null;
                    if (activeChannelId) {
                        updateChannelSidebarItem(activeChannelId, msg.time_label, 'me');
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


        if (dmChatFileInput && dmChatAttachedWrap) {
            var chatScreen = document.querySelector('.dm-chat-screen');
            var dragCounter = 0;

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

                function handleFiles(files) {
                    attachedFiles = [];
                    for (var i = 0; i < files.length; i++) {
                        attachedFiles.push({ name: files[i].name, size: files[i].size, file: files[i] });
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
                        html += '<span class="dm-chat-attached-name">' +
                            escapeHtml(attachedFiles[j].name) +
                            '<button type="button" class="dm-chat-attached-item-remove" data-index="' + j + '" title="Remove file">×</button>' +
                            '</span>';
                    }
                    html += ' <button type="button" class="dm-chat-attached-clear js-clear-attached" title="Remove all">×</button>';
                    dmChatAttachedWrap.innerHTML = html;
                    dmChatAttachedWrap.removeAttribute('hidden');

                    // Clear all button
                    dmChatAttachedWrap.querySelector('.js-clear-attached').addEventListener('click', function () {
                        attachedFiles = [];
                        if (dmChatFileInput) dmChatFileInput.value = '';
                        updateAttachedUI();
                    });

                    // Individual remove buttons
                    dmChatAttachedWrap.querySelectorAll('.dm-chat-attached-item-remove').forEach(function (btn) {
                        btn.addEventListener('click', function (e) {
                            e.stopPropagation();
                            var index = parseInt(this.getAttribute('data-index'), 10);
                            attachedFiles.splice(index, 1);
                            updateAttachedUI();
                        });
                    });
                }
            }

            dmChatFileInput.addEventListener('change', function () {
                if (this.files.length) {
                    handleFiles(this.files);
                }
            });
        }

        document.querySelectorAll('.dm-chat-tool-btn[data-action]').forEach(function (btn) {
            if (btn.getAttribute('data-action') === 'emoji') return;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var action = btn.getAttribute('data-action');
                if (action === 'attach' && dmChatFileInput) {
                    dmChatFileInput.click();
                } else if (action === 'gif') { /* HANDLED BY JS-GIF-TOGGLE */ }
                else if (action === 'voice') { /* TODO: voice recording */ }
            });
        });

        var loadMoreBtn = document.getElementById('dmLoadMore');
        var loadMoreWrap = document.getElementById('dmLoadMoreWrap');
        var loadCount = 20;
        if (loadMoreBtn && loadMoreWrap) {
            loadMoreBtn.addEventListener('click', function () {
                var hidden = dmChatMessages.querySelectorAll('.dm-chat-msg--hidden');
                var toShow = Math.min(loadCount, hidden.length);
                for (var i = 0; i < toShow; i++) {
                    hidden[i].classList.remove('dm-chat-msg--hidden');
                }
                if (hidden.length <= loadCount) {
                    loadMoreWrap.classList.add('dm-load-more-wrap--hidden');
                }
            });
        }

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
            searchInput.addEventListener('input', function () {
                var q = this.value.trim();
                var qLower = q.toLowerCase();
                var hasSearch = q.length > 0;
                var allMsgs = dmChatMessages.querySelectorAll('.dm-chat-msg');
                allMsgs.forEach(function (msg) {
                    var bubbleEl = msg.querySelector('.dm-msg-bubble');
                    var textEls = getMessageTextElements(bubbleEl);
                    var text = getBubbleSearchText(bubbleEl);
                    var match = !hasSearch || text.toLowerCase().indexOf(qLower) !== -1;
                    if (match) {
                        msg.classList.remove('dm-chat-msg--search-nomatch');
                        if (hasSearch) {
                            msg.classList.remove('dm-chat-msg--hidden');
                        } else if (msg.getAttribute('data-initially-hidden') === '1') {
                            msg.classList.add('dm-chat-msg--hidden');
                        }
                        textEls.forEach(function (el) { applyHighlight(el, q, hasSearch); });
                    } else {
                        msg.classList.add('dm-chat-msg--search-nomatch');
                        textEls.forEach(function (el) { applyHighlight(el, '', false); });
                    }
                });
                if (hasSearch) {
                    if (loadMoreWrap) loadMoreWrap.classList.add('dm-load-more-wrap--hidden');
                } else if (loadMoreWrap) {
                    loadMoreWrap.classList.remove('dm-load-more-wrap--hidden');
                }
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
                btn.addEventListener('click', function (e) {
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

                    fetch(window.CHATROX.baseUrl + '/api/messages/react', {
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
                            updateMessageReactions(resData.message_id, resData.emoji, resData.action, resData.count);
                            
                            if (window.ChatRoxWS) {
                                window.ChatRoxWS.broadcast(conversationId, 'message_reaction', {
                                    conversation_id: conversationId,
                                    message_id: resData.message_id,
                                    emoji: resData.emoji,
                                    action: resData.action,
                                    count: resData.count
                                });
                            }
                        }
                    })
                    .catch(function(err) {
                        console.error('Failed to react to message:', err);
                    });
                });
            });
            document.addEventListener('click', function (e) {
                if (!reactionPicker.hasAttribute('hidden') && !reactionPicker.contains(e.target) && !e.target.closest('.js-msg-react')) {
                    closeReactionPicker();
                }
            });
            dmChatMessages.addEventListener('scroll', function () {
                closeReactionPicker();
            }, { passive: true });
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
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
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
            forwardOverlay.addEventListener('click', closeForwardModal);
            document.querySelectorAll('.js-forward-close').forEach(function (btn) {
                btn.addEventListener('click', function () { closeForwardModal(); });
            });
            forwardModal.querySelectorAll('.js-forward-check').forEach(function (cb) {
                cb.addEventListener('change', updateForwardSelectedCount);
            });
            if (forwardSearchInput) {
                forwardSearchInput.addEventListener('input', function () {
                    var q = (this.value || '').trim().toLowerCase();
                    forwardModal.querySelectorAll('.js-forward-row').forEach(function (row) {
                        var search = (row.getAttribute('data-search') || '').toLowerCase();
                        var match = !q || search.indexOf(q) !== -1;
                        row.classList.toggle('dm-forward-row--hidden', !match);
                    });
                });
            }
            forwardSubmit.addEventListener('click', function () {
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

                fetch(window.CHATROX.baseUrl + '/api/messages/forward', {
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
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
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
                        if (window.lucide && typeof window.lucide.createIcons === 'function') {
                            window.lucide.createIcons();
                        }
                    }
                });
            }
            if (msgLightboxNext) {
                msgLightboxNext.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (lightboxIndex < lightboxSrcs.length - 1) {
                        lightboxIndex++;
                        updateLightboxImage();
                        if (window.lucide && typeof window.lucide.createIcons === 'function') {
                            window.lucide.createIcons();
                        }
                    }
                });
            }
            document.querySelectorAll('.js-msg-lightbox-close').forEach(function (btn) {
                btn.addEventListener('click', closeImageLightbox);
            });
        }

        /* Delete menu – Delete for me (all) / Delete for everyone (own only) */
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

        function deleteMessageForMe(msg) {
            if (msg) msg.remove();
        }

        function deleteMessageForEveryone(msg) {
            if (!msg || !msg.classList.contains('dm-chat-msg--me')) return;
            if (msg.getAttribute('data-deleted-everyone') === '1') return;
            var bubble = msg.querySelector('.dm-msg-bubble');
            if (!bubble) return;
            msg.setAttribute('data-deleted-everyone', '1');
            msg.classList.add('dm-chat-msg--deleted-everyone');
            if (msg.getAttribute('data-pinned') === '1') {
                msg.removeAttribute('data-pinned');
                msg.classList.remove('dm-chat-msg--pinned');
            }
            bubble.className = 'dm-msg-bubble';
            bubble.innerHTML = '<p class="dm-msg-deleted-text">This Message was deleted</p>';
            var reactionsEl = msg.querySelector('.dm-msg-reactions');
            if (reactionsEl) reactionsEl.innerHTML = '';
        }

        function configureDeleteMenu(msg) {
            var everyoneBtn = deleteMenu.querySelector('[data-delete-type="everyone"]');
            if (!everyoneBtn) return;
            var isOwn = msg && msg.classList.contains('dm-chat-msg--me');
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
                    fetch(window.CHATROX.baseUrl + '/api/messages/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message_id: messageId })
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
                    fetch(window.CHATROX.baseUrl + '/api/messages/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message_id: messageId })
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

        document.addEventListener('click', function (e) {
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
                    if (!msg.classList.contains('dm-chat-msg--me')) {
                        closeDeleteMenu();
                        closeReactionPicker();
                        deleteMessageForMe(msg);
                        if (typeof renderDetailsPinnedList === 'function') renderDetailsPinnedList();
                        return;
                    }
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
                    if (msg.getAttribute('data-pinned') === '1') {
                        msg.removeAttribute('data-pinned');
                        msg.classList.remove('dm-chat-msg--pinned');
                    } else {
                        msg.setAttribute('data-pinned', '1');
                        msg.classList.add('dm-chat-msg--pinned');
                    }
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
                        var newText = (p.textContent || '').trim();
                        p.contentEditable = 'false';
                        p.removeEventListener('blur', saveEdit);
                        p.removeEventListener('keydown', handleKeydown);

                        var matches = (msg.id || '').match(/dm-msg-(\d+)/);
                        if (!matches || !newText) return;
                        var messageId = parseInt(matches[1], 10);

                        fetch(window.CHATROX.baseUrl + '/api/messages/edit', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                message_id: messageId,
                                body: newText
                            })
                        })
                        .then(function (res) { return res.json(); })
                        .then(function (resData) {
                            if (resData.success) {
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
                                        body: newText
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
                            // Restore original text if canceled
                            p.textContent = p.getAttribute('data-original-text') || p.textContent;
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
        var detailsTabBtns = document.querySelectorAll('.dm-details-tab');
        var currentDetailsTab = 'profile';
        var detailsContents = {
            profile: document.getElementById('dmDetailsContentProfile'),
            media: document.getElementById('dmDetailsContentMedia'),
            files: document.getElementById('dmDetailsContentFiles'),
            pinned: document.getElementById('dmDetailsContentPinned')
        };

        function openDetailsPanel() {
            if (!detailsOverlay || !detailsPanel) return;
            detailsOverlay.removeAttribute('hidden');
            detailsPanel.removeAttribute('hidden');
            detailsOverlay.classList.add('dm-details-overlay--open');
            detailsPanel.classList.add('dm-details-panel--open');
            renderDetailsPinnedList();
            if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
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
                var items = detailsContents.media ? detailsContents.media.querySelectorAll('.dm-details-media-thumb') : [];
                items.forEach(function (img) {
                    var alt = (img.alt || '').toLowerCase();
                    var src = (img.src || '').toLowerCase();
                    var match = !q || alt.indexOf(q) !== -1 || src.split('/').pop().indexOf(q) !== -1;
                    img.classList.toggle('dm-details-media-thumb--hidden', !match);
                });
            } else if (tabName === 'files') {
                var items = detailsContents.files ? detailsContents.files.querySelectorAll('.dm-details-file-row') : [];
                items.forEach(function (row) {
                    var nameEl = row.querySelector('.dm-details-file-name');
                    var name = (nameEl ? nameEl.textContent : '').toLowerCase();
                    var match = !q || name.indexOf(q) !== -1;
                    row.classList.toggle('dm-details-file-row--hidden', !match);
                    if (nameEl) applyHighlight(nameEl, q, !!q);
                });
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
            if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
        }

        function renderDetailsPinnedList() {
            if (!detailsPinnedList || !detailsPinnedEmpty) return;
            var pinned = dmChatMessages ? dmChatMessages.querySelectorAll('.dm-chat-msg[data-pinned="1"]') : [];
            detailsPinnedList.innerHTML = '';
            pinned.forEach(function (msg, idx) {
                var text = getReplySnippet(msg) || 'Message';
                var card = document.createElement('div');
                card.className = 'dm-details-pinned-card';
                card.innerHTML =
                    '<div class="dm-details-pinned-card-header">' +
                    '<span class="dm-details-pinned-label"><i data-lucide="pin" size="12"></i> PINNED CONTEXT</span>' +
                    '<button type="button" class="dm-details-pinned-unpin js-details-unpin" aria-label="Unpin"><i data-lucide="x" size="14"></i></button>' +
                    '</div>' +
                    '<p class="dm-details-pinned-text">' + escapeHtml(text) + '</p>';
                detailsPinnedList.appendChild(card);
                var unpinBtn = card.querySelector('.js-details-unpin');
                if (unpinBtn) {
                    unpinBtn.addEventListener('click', function () {
                        msg.removeAttribute('data-pinned');
                        msg.classList.remove('dm-chat-msg--pinned');
                        renderDetailsPinnedList();
                        if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
                    });
                }
            });
            detailsPinnedEmpty.classList.toggle('dm-details-pinned-empty--show', pinned.length === 0);
        }

        if (document.querySelector('.js-chat-details-open')) {
            document.querySelector('.js-chat-details-open').addEventListener('click', function (e) {
                e.preventDefault();
                openDetailsPanel();
            });
        }
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

        if (document.querySelector('.js-reply-cancel')) {
            document.querySelector('.js-reply-cancel').addEventListener('click', function (e) {
                e.preventDefault();
                clearReply();
            });
        }

        // Channel read-by modal
        var chReadOverlay = document.getElementById('chReadOverlay');
        var chReadModal = document.getElementById('chReadModal');
        var chReadModalBody = document.getElementById('chReadModalBody');

        function openChannelReadModal(readers, notRead, readCount, memberCount) {
            if (!chReadModal || !chReadModalBody) return;
            var html = '';

            if (readers && readers.length) {
                html += '<div class="ch-read-section-title">Read by (' + readers.length + ')</div>';
                readers.forEach(function (reader) {
                    html += '<div class="ch-read-row">' +
                        '<img src="' + escapeHtml(reader.avatar) + '" alt="' + escapeHtml(reader.name) + '">' +
                        '<div class="ch-read-row-info">' +
                        '<span class="ch-read-row-name">' + escapeHtml(reader.name) + '</span>' +
                        '<span class="ch-read-row-time">' + escapeHtml(reader.read_at || 'Just now') + '</span>' +
                        '</div>' +
                        '<span class="ch-read-row-status">Read</span>' +
                        '</div>';
                });
            }

            if (notRead && notRead.length) {
                html += '<div class="ch-read-section-title">Not yet (' + notRead.length + ')</div>';
                notRead.forEach(function (name) {
                    html += '<div class="ch-read-row ch-read-row--pending">' +
                        '<div class="ch-read-row-info" style="padding-left: 0;">' +
                        '<span class="ch-read-row-name">' + escapeHtml(name) + '</span>' +
                        '<span class="ch-read-row-time">Has not read yet</span>' +
                        '</div>' +
                        '<span class="ch-read-row-status">Pending</span>' +
                        '</div>';
                });
            }

            if (!html) {
                html = '<div class="ch-read-section-title">No reads yet</div>';
            }

            chReadModalBody.innerHTML = html;
            if (chReadOverlay) chReadOverlay.removeAttribute('hidden');
            chReadModal.removeAttribute('hidden');
            if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
        }

        function closeChannelReadModal() {
            if (chReadOverlay) chReadOverlay.setAttribute('hidden', '');
            if (chReadModal) chReadModal.setAttribute('hidden', '');
        }

        if (dmChatMessages) {
            dmChatMessages.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-channel-seen-by');
                if (!btn) return;
                e.preventDefault();
                e.stopPropagation();
                var readers = [];
                var notRead = [];
                try {
                    readers = JSON.parse(btn.getAttribute('data-readers') || '[]');
                    notRead = JSON.parse(btn.getAttribute('data-not-read') || '[]');
                } catch (err) { }
                openChannelReadModal(
                    readers,
                    notRead,
                    parseInt(btn.getAttribute('data-read-count') || '0', 10),
                    parseInt(btn.getAttribute('data-member-count') || '0', 10)
                );
            });
        }

        if (chReadOverlay) {
            chReadOverlay.addEventListener('click', closeChannelReadModal);
        }
        document.querySelectorAll('.js-ch-read-close').forEach(function (btn) {
            btn.addEventListener('click', closeChannelReadModal);
        });

        // Edit Channel Modal Logic
        var editChannelModal = document.getElementById('editChannelModal');
        var openEditBtns = document.querySelectorAll('.js-open-edit-channel');
        var closeEditBtns = document.querySelectorAll('.js-close-edit-channel-modal');

        if (editChannelModal) {
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-open-edit-channel');
                if (btn) {
                    e.preventDefault();
                    editChannelModal.classList.add('active');
                }
            });

            closeEditBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    editChannelModal.classList.remove('active');
                });
            });

            editChannelModal.addEventListener('click', function (e) {
                if (e.target === editChannelModal) {
                    editChannelModal.classList.remove('active');
                }
            });

            var editChannelForm = document.getElementById('editChannelForm');
            if (editChannelForm) {
                editChannelForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    // Mock save action - just close the modal for now
                    editChannelModal.classList.remove('active');
                    console.log('Channel details updated');
                });
            }
        }

        // --- WEBSOCKET EVENT HANDLERS ---
        var chatScreen = document.querySelector('.dm-chat-screen');
        var conversationId = chatScreen ? chatScreen.dataset.conversationId : null;

        var hasUnread = false;

        function updateChannelSidebarItem(channelId, time, side) {
            var sidebarItem = document.querySelector('.dir-list a[data-channel-id="' + channelId + '"]');
            if (!sidebarItem) return;

            // 1. Update time and unread badge
            var timeEl = sidebarItem.querySelector('.dir-time');
            if (timeEl) {
                var chatScreen = document.querySelector('.dm-chat-screen');
                var activeChannelId = chatScreen ? chatScreen.dataset.channelId : null;
                var badgeEl = timeEl.querySelector('.badge-dot');
                
                var unreadCount = badgeEl ? (parseInt(badgeEl.textContent, 10) || 0) : 0;
                if (side === 'them' && String(activeChannelId) !== String(channelId)) {
                    unreadCount++;
                }
                
                var badgeHtml = unreadCount > 0 ? '<span class="badge-dot">' + unreadCount + '</span>' : '';
                timeEl.innerHTML = time + ' ' + badgeHtml;
            }

            // 2. Move to top
            var dirList = document.querySelector('.dir-list');
            if (dirList && sidebarItem) {
                dirList.insertBefore(sidebarItem, dirList.firstChild);
            }
        }

        function markAsRead() {
            if (!conversationId) return;
            if (document.hidden || !document.hasFocus()) {
                hasUnread = true;
                return;
            }
            hasUnread = false;
            fetch(window.CHATROX.baseUrl + '/api/messages/read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ conversation_id: conversationId })
            })
            .catch(function(err) {
                console.error('Failed to mark conversation read:', err);
            });
        }

        // Listen for visibility/focus change
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden && document.hasFocus() && hasUnread) {
                markAsRead();
            }
        });
        window.addEventListener('focus', function () {
            if (hasUnread) {
                markAsRead();
            }
        });

        function handleSubscribe() {
            if (conversationId && window.ChatRoxWS) {
                window.ChatRoxWS.subscribe(conversationId);
            }
        }

        handleSubscribe();
        markAsRead();
        document.addEventListener('chatrox:ws_connected', handleSubscribe);

        // Receiver: New Messages
        document.addEventListener('chatrox:new_message', function (e) {
            var msg = e.detail;
            if (!msg) return;

            // Update sidebar item globally for any new message in the workspace if it's a channel
            if (msg.channel_id) {
                var currentMemberId = window.CHATROX.user.workspace_member_id;
                var side = (parseInt(msg.sender_id, 10) === parseInt(currentMemberId, 10)) ? 'me' : 'them';
                updateChannelSidebarItem(msg.channel_id, msg.time_label, side);
            }

            if (String(msg.conversation_id) !== String(conversationId)) return;
            
            // Avoid duplicate rendering
            if (document.getElementById('dm-msg-' + msg.id)) return;

            var currentMemberId = window.CHATROX.user.workspace_member_id;
            var side = (parseInt(msg.sender_id, 10) === parseInt(currentMemberId, 10)) ? 'me' : 'them';
            
            if (side === 'them') {
                markAsRead();
            }
            
            var bubbleContent = '';
            if (msg.is_forwarded) {
                bubbleContent += forwardLabelHtml();
            }
            if (msg.reply_to_id) {
                var replySnippet = 'Replying...';
                var targetEl = document.getElementById('dm-msg-' + msg.reply_to_id);
                if (targetEl) {
                    replySnippet = getReplySnippet(targetEl);
                    if (replySnippet.length > 80) replySnippet = replySnippet.substring(0, 80) + '…';
                }
                bubbleContent += '<div class="dm-msg-reply-wrap" data-reply-to-id="dm-msg-' + msg.reply_to_id + '"><div class="dm-msg-reply-preview">' + escapeHtml(replySnippet) + '</div></div>';
            }
            
            if (msg.message_type === 'gif') {
                bubbleContent += '<div class="dm-msg-images dm-msg-images--single"><img src="' + escapeHtml(msg.body) + '" alt="" class="dm-msg-img js-msg-img" loading="lazy"></div>';
            } else if (msg.body) {
                bubbleContent += '<p>' + msg.body + '</p>';
            }

            if (msg.attachments && msg.attachments.length) {
                var images = msg.attachments.filter(function(a) { return a.category === 'image'; });
                var docs = msg.attachments.filter(function(a) { return a.category !== 'image'; });

                if (images.length === 1) {
                    bubbleContent += '<div class="dm-msg-images dm-msg-images--single"><img src="' + images[0].url + '" alt="" class="dm-msg-img js-msg-img" loading="lazy"></div>';
                } else if (images.length > 1) {
                    var imgHtml = '';
                    images.forEach(function(img) {
                        imgHtml += '<img src="' + img.url + '" alt="" class="dm-msg-img js-msg-img" loading="lazy">';
                    });
                    bubbleContent += '<div class="dm-msg-images dm-msg-images--grid dm-msg-images--count-' + Math.min(4, images.length) + '">' + imgHtml + '</div>';
                }

                if (docs.length) {
                    bubbleContent += '<div class="dm-msg-files">';
                    docs.forEach(function(d) {
                        bubbleContent += buildFileCardHtml(d.original_name, (d.size_bytes/1024).toFixed(1) + ' KB', d.url, d.mime_type);
                    });
                    bubbleContent += '</div>';
                }
            }

            var bubbleClasses = 'dm-msg-bubble';
            if ((msg.attachments && msg.attachments.length) || msg.message_type === 'gif') {
                bubbleClasses += ' dm-msg-bubble--media';
            }

            if (side === 'me') {
                renderMessage('dm-msg-' + msg.id, bubbleContent, bubbleClasses, msg.time_label, 'me');
            } else {
                renderMessage('dm-msg-' + msg.id, bubbleContent, bubbleClasses, msg.time_label, 'them', msg.sender_name);
            }
        });

        // Receiver: Reactions
        document.addEventListener('chatrox:message_reaction', function (e) {
            var data = e.detail;
            if (!data || String(data.conversation_id) !== String(conversationId)) return;
            updateMessageReactions(data.message_id, data.emoji, data.action, data.count);
        });

        // Receiver: Deletions
        document.addEventListener('chatrox:message_deleted', function (e) {
            var data = e.detail;
            if (!data || String(data.conversation_id) !== String(conversationId)) return;
            var msgEl = document.getElementById('dm-msg-' + data.message_id);
            if (!msgEl) return;
            
            msgEl.setAttribute('data-deleted-everyone', '1');
            msgEl.classList.add('dm-chat-msg--deleted-everyone');
            if (msgEl.getAttribute('data-pinned') === '1') {
                msgEl.removeAttribute('data-pinned');
                msgEl.classList.remove('dm-chat-msg--pinned');
            }
            var bubble = msgEl.querySelector('.dm-msg-bubble');
            if (bubble) {
                bubble.className = 'dm-msg-bubble';
                bubble.innerHTML = '<p class="dm-msg-deleted-text">This Message was deleted</p>';
            }
            var reactionsEl = msgEl.querySelector('.dm-msg-reactions');
            if (reactionsEl) reactionsEl.innerHTML = '';
        });

        // Receiver: Edits
        document.addEventListener('chatrox:message_edited', function (e) {
            var data = e.detail;
            if (!data || String(data.conversation_id) !== String(conversationId)) return;
            var msgEl = document.getElementById('dm-msg-' + data.message_id);
            if (!msgEl) return;
            
            var p = msgEl.querySelector('.dm-msg-bubble p');
            if (p) {
                p.textContent = data.body;
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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChat);
    } else {
        initChat();
    }
})();
