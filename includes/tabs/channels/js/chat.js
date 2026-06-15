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
            var pw = floater.offsetWidth;
            var ph = floater.offsetHeight;
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

        function renderMessage(id, content, classes, time) {
            var actionsBar = '<div class="dm-msg-actions" aria-label="Message actions">' +
                '<button type="button" class="dm-msg-action js-msg-react" title="Reaction" aria-label="Reaction"><i data-lucide="smile-plus" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-reply" title="Reply" aria-label="Reply"><i data-lucide="reply" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-pin" title="Pin" aria-label="Pin"><i data-lucide="pin" size="16"></i></button>' +
                '<button type="button" class="dm-msg-action js-msg-forward" title="Forward" aria-label="Forward"><i data-lucide="forward" size="16"></i></button>' +
                '<span class="dm-msg-actions-sep" aria-hidden="true"></span>' +
                '<button type="button" class="dm-msg-action dm-msg-action--delete js-msg-delete" title="Delete" aria-label="Delete"><i data-lucide="trash-2" size="16"></i></button>' +
                '</div>';
            var body = '<div class="dm-msg-body"><div class="' + classes + '">' + content + '</div><div class="dm-msg-reactions"></div><span class="dm-msg-time">' + time + '</span></div>';
            var msgHtml = '<div class="dm-chat-msg dm-chat-msg--me" id="' + id + '" data-msg-index="">' + body + actionsBar + '</div>';
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
            var now = new Date();
            var timeStr = now.getHours() > 12
                ? (now.getHours() - 12) + ':' + String(now.getMinutes()).padStart(2, '0') + ' PM'
                : now.getHours() + ':' + String(now.getMinutes()).padStart(2, '0') + ' AM';

            var bubbleContent = '';
            var imageFiles = [];
            var nonImageFiles = [];
            if (attachedFiles && attachedFiles.length) {
                attachedFiles.forEach(function (f) {
                    if (f.file && f.file.type && f.file.type.indexOf('image/') === 0) {
                        imageFiles.push(f);
                    } else {
                        nonImageFiles.push(f);
                    }
                });
            }

            var replyBlock = '';
            if (currentReplyMsg) {
                var replyId = currentReplyMsg.id || ('dm-msg-ref-' + Date.now());
                var replyImgSrcs = getReplyImageSrcs(currentReplyMsg);
                var replySnippet = getReplySnippet(currentReplyMsg);
                if (replySnippet.length > 80) replySnippet = replySnippet.substring(0, 80) + '…';
                if (replyImgSrcs.length > 0) {
                    var total = replyImgSrcs.length;
                    var showCount = total > 4 ? 4 : total;
                    var moreCount = total > 4 ? total - 4 : 0;
                    var gridHtml = '';
                    for (var ri = 0; ri < showCount; ri++) {
                        var rsrc = replyImgSrcs[ri];
                        var rimg = '<img src="' + escapeHtml(rsrc) + '" alt="" class="dm-msg-reply-grid-img" loading="lazy">';
                        if (moreCount > 0 && ri === 3) {
                            gridHtml += '<div class="dm-msg-reply-grid-cell dm-msg-reply-grid-cell--more">' + rimg + '<span class="dm-msg-reply-grid-more">+' + moreCount + '</span></div>';
                        } else {
                            gridHtml += '<div class="dm-msg-reply-grid-cell">' + rimg + '</div>';
                        }
                    }
                    replyBlock = '<div class="dm-msg-reply-wrap dm-msg-reply-wrap--image" data-reply-to-id="' + escapeHtml(replyId) + '"><div class="dm-msg-reply-grid dm-msg-reply-grid--count-' + showCount + '">' + gridHtml + '</div></div>';
                } else {
                    replyBlock = '<div class="dm-msg-reply-wrap" data-reply-to-id="' + escapeHtml(replyId) + '"><div class="dm-msg-reply-preview">' + escapeHtml(replySnippet) + '</div></div>';
                }
                clearReply();
            }
            if (text) {
                bubbleContent += '<p>' + sanitizeHtml(raw) + '</p>';
            }
            if (imageFiles.length === 1) {
                bubbleContent += '<div class="dm-msg-images dm-msg-images--single"><img src="' + URL.createObjectURL(imageFiles[0].file) + '" alt="" class="dm-msg-img js-msg-img" loading="lazy"></div>';
            } else if (imageFiles.length > 1) {
                var total = imageFiles.length;
                var showCount = total > 4 ? 4 : total;
                var moreCount = total > 4 ? total - 4 : 0;
                var allSrcs = [];
                for (var a = 0; a < total; a++) {
                    allSrcs.push(URL.createObjectURL(imageFiles[a].file));
                }
                var gridHtml = '';
                for (var i = 0; i < showCount; i++) {
                    var src = allSrcs[i];
                    var imgTag = '<img src="' + src + '" alt="" class="dm-msg-img js-msg-img" loading="lazy" data-index="' + i + '">';
                    if (moreCount > 0 && i === 3) {
                        gridHtml += '<div class="dm-msg-grid-cell-wrap">' + imgTag + '<span class="dm-msg-grid-more">+' + moreCount + '</span></div>';
                    } else {
                        gridHtml += imgTag;
                    }
                }
                bubbleContent += '<div class="dm-msg-images dm-msg-images--grid dm-msg-images--count-' + showCount + '" data-lightbox-srcs="' + escapeHtml(JSON.stringify(allSrcs)) + '">' + gridHtml + '</div>';
            }
            if (nonImageFiles.length) {
                var filesHtml = '<div class="dm-msg-files">';
                nonImageFiles.forEach(function (f) {
                    var sizeKb = f.size ? (f.size / 1024) : 0;
                    var sizeLabel;
                    if (sizeKb >= 1024) {
                        sizeLabel = (sizeKb / 1024).toFixed(2) + ' MB';
                    } else {
                        sizeLabel = sizeKb.toFixed(2) + ' KB';
                    }
                    var href = f.file ? URL.createObjectURL(f.file) : '#';
                    var name = escapeHtml(f.name || 'File');
                    filesHtml += '' +
                        '<div class="dm-file-card">' +
                        '<div class="dm-file-icon">' +
                        '<i data-lucide="file" size="18"></i>' +
                        '</div>' +
                        '<div class="dm-file-main">' +
                        '<div class="dm-file-name">' + name + '</div>' +
                        '<div class="dm-file-meta">' + sizeLabel + ' · FILE</div>' +
                        '</div>' +
                        '<a href="' + href + '" download="' + name + '" class="dm-file-download" aria-label="Download file">' +
                        '<i data-lucide="download" size="18"></i>' +
                        '</a>' +
                        '</div>';
                });
                filesHtml += '</div>';
                bubbleContent += filesHtml;
            }
            if (replyBlock) bubbleContent = replyBlock + bubbleContent;
            if (!bubbleContent) return;

            attachedFiles = [];
            if (dmChatFileInput) dmChatFileInput.value = '';
            if (dmChatAttachedWrap) {
                dmChatAttachedWrap.innerHTML = '';
                dmChatAttachedWrap.setAttribute('hidden', '');
            }

            var bubbleClasses = 'dm-msg-bubble';
            if (imageFiles.length || nonImageFiles.length) {
                bubbleClasses += ' dm-msg-bubble--media';
            }

            renderMessage('dm-msg-new-' + Date.now(), bubbleContent, bubbleClasses, timeStr);
            dmChatInput.innerHTML = '';
            ensurePlaceholder();
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
        var giphyApiKey = 'Gc7131jiJuvI7IdN0HZ1D7nh0ow5BU6g'; // Verified Giphy web key
        var gifSearchTimeout = null;

        if (gifPicker && gifToggle && gifResults) {

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
                    })
                    .catch(function (err) {
                        gifResults.innerHTML = '<div class="dm-gif-error">Failed to load GIFs.</div>';
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
            var now = new Date();
            var timeStr = now.getHours() > 12
                ? (now.getHours() - 12) + ':' + String(now.getMinutes()).padStart(2, '0') + ' PM'
                : now.getHours() + ':' + String(now.getMinutes()).padStart(2, '0') + ' AM';

            renderMessage('dm-msg-gif-' + Date.now(), '<div class="dm-msg-images dm-msg-images--single"><img src="' + escapeHtml(src) + '" alt="" class="dm-msg-img js-msg-img" loading="lazy"></div>', 'dm-msg-bubble dm-msg-bubble--media', timeStr);
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
            function applyHighlight(p, q, hasSearch) {
                if (!p) return;
                var orig = p.getAttribute('data-original-text');
                if (orig === null) {
                    orig = p.textContent || '';
                    p.setAttribute('data-original-text', orig);
                }
                if (!hasSearch || !q) {
                    p.textContent = orig;
                    p.removeAttribute('data-original-text');
                    return;
                }
                var escaped = escapeHtml(orig);
                var regex = new RegExp('(' + escapeRegex(q) + ')', 'gi');
                p.innerHTML = escaped.replace(regex, '<span class="dm-search-highlight">$1</span>');
            }
            searchInput.addEventListener('input', function () {
                var q = this.value.trim();
                var qLower = q.toLowerCase();
                var hasSearch = q.length > 0;
                var allMsgs = dmChatMessages.querySelectorAll('.dm-chat-msg');
                allMsgs.forEach(function (msg) {
                    var bubble = msg.querySelector('.dm-msg-bubble p');
                    var text = bubble ? (bubble.getAttribute('data-original-text') || bubble.textContent || '') : '';
                    var match = !hasSearch || text.toLowerCase().indexOf(qLower) !== -1;
                    if (match) {
                        msg.classList.remove('dm-chat-msg--search-nomatch');
                        if (hasSearch) {
                            msg.classList.remove('dm-chat-msg--hidden');
                        } else if (msg.getAttribute('data-initially-hidden') === '1') {
                            msg.classList.add('dm-chat-msg--hidden');
                        }
                        applyHighlight(bubble, q, hasSearch);
                    } else {
                        msg.classList.add('dm-chat-msg--search-nomatch');
                        applyHighlight(bubble, '', false);
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
                    var reactionsEl = currentReactMessage.querySelector('.dm-msg-reactions');
                    if (!reactionsEl) {
                        closeReactionPicker();
                        return;
                    }
                    var existing = null;
                    reactionsEl.querySelectorAll('.dm-reaction-bubble').forEach(function (b) {
                        if (b.getAttribute('data-emoji') === emoji) existing = b;
                    });
                    if (existing) {
                        var countSpan = existing.querySelector('.dm-reaction-count');
                        var n = parseInt(countSpan.textContent, 10) || 1;
                        countSpan.textContent = n + 1;
                    } else {
                        var bubble = document.createElement('span');
                        bubble.className = 'dm-reaction-bubble';
                        bubble.setAttribute('data-emoji', emoji);
                        bubble.innerHTML = '<span class="dm-reaction-emoji">' + emoji + '</span> <span class="dm-reaction-count">1</span>';
                        reactionsEl.appendChild(bubble);
                    }
                    closeReactionPicker();
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
                var selected = [];
                forwardModal.querySelectorAll('.dm-forward-check:checked').forEach(function (cb) {
                    var row = cb.closest('.js-forward-row');
                    if (row) {
                        var nameEl = row.querySelector('.dm-forward-name');
                        if (nameEl) selected.push(nameEl.textContent.trim());
                    }
                });
                closeForwardModal();
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
                if (type === 'everyone') {
                    deleteMessageForEveryone(msg);
                } else {
                    deleteMessageForMe(msg);
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
                        p.contentEditable = 'false';
                        p.removeEventListener('blur', saveEdit);
                        p.removeEventListener('keydown', handleKeydown);

                        // Add an (edited) label near the timestamp
                        var timeSpan = msg.querySelector('.dm-msg-time');
                        if (timeSpan && !msg.querySelector('.dm-msg-edited-label')) {
                            var editedLabel = document.createElement('span');
                            editedLabel.className = 'dm-msg-edited-label';
                            editedLabel.textContent = '(Edited) ';
                            editedLabel.style.fontSize = '11px';
                            editedLabel.style.marginRight = '4px';
                            timeSpan.insertBefore(editedLabel, timeSpan.firstChild);
                        }
                    };

                    var handleKeydown = function (e2) {
                        if (e2.key === 'Enter' && !e2.shiftKey) {
                            e2.preventDefault();
                            saveEdit();
                        } else if (e2.key === 'Escape') {
                            e2.preventDefault();
                            // Optional: restore original text if canceled
                            saveEdit();
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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChat);
    } else {
        initChat();
    }
})();
