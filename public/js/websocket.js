/**
 * ChatRox WebSocket Client Manager
 * Handles real-time connection, subscriptions, and custom DOM event dispatching.
 */
(function () {
    if (!window.CHATROX || !window.CHATROX.user || !window.CHATROX.user.session_token) {
        console.warn('ChatRox WebSocket: Missing session token, skipping connection.');
        return;
    }

    const token = window.CHATROX.user.session_token;
    const wsPort = window.CHATROX.wsPort || 8080;
    const hostname = window.location.hostname || '127.0.0.1';
    
    // Support secure ws connection if page runs on https
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const wsUrl = `${protocol}//${hostname}:${wsPort}?token=${token}`;

    let socket = null;
    let reconnectAttempts = 0;
    const maxReconnectDelay = 30000; // 30s
    let currentConversationId = null;
    const pendingBroadcasts = [];

    function flushPendingBroadcasts() {
        if (!socket || socket.readyState !== WebSocket.OPEN) return;
        while (pendingBroadcasts.length) {
            const item = pendingBroadcasts.shift();
            socket.send(JSON.stringify(Object.assign({ action: 'broadcast' }, item)));
        }
    }

    function connect() {
        console.log(`Connecting to WebSocket at ${wsUrl}...`);
        socket = new WebSocket(wsUrl);

        socket.onopen = function () {
            console.log('WebSocket connection established.');
            reconnectAttempts = 0;

            // Trigger a custom event indicating connected state
            document.dispatchEvent(new CustomEvent('chatrox:ws_connected'));

            // Auto-re-subscribe if we had an active conversation ID before disconnection
            if (currentConversationId) {
                subscribe(currentConversationId);
            }

            flushPendingBroadcasts();
        };

        socket.onmessage = function (e) {
            try {
                const payload = JSON.parse(e.data);
                if (payload.error) {
                    console.error('WebSocket Server Error:', payload.error);
                    return;
                }

                if (payload.event) {
                    // Merge root conversation_id so handlers work even if data omits it
                    const eventDetail = Object.assign(
                        {},
                        payload.data || {},
                        payload.conversation_id ? { conversation_id: payload.conversation_id } : {}
                    );

                    // Dispatch custom DOM event
                    const customEvent = new CustomEvent(`chatrox:${payload.event}`, {
                        detail: eventDetail
                    });
                    document.dispatchEvent(customEvent);

                    // Global general event handler
                    document.dispatchEvent(new CustomEvent('chatrox:ws_event', {
                        detail: payload
                    }));
                }
            } catch (err) {
                console.error('Error parsing WebSocket message:', err);
            }
        };

        socket.onclose = function (e) {
            console.log('WebSocket connection closed. Reconnecting...');
            scheduleReconnect();
        };

        socket.onerror = function (err) {
            console.error('WebSocket Error:', err);
            socket.close();
        };
    }

    function scheduleReconnect() {
        reconnectAttempts++;
        const delay = Math.min(Math.pow(2, reconnectAttempts) * 1000, maxReconnectDelay);
        setTimeout(connect, delay);
    }

    function send(action, data = {}) {
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify(Object.assign({ action: action }, data)));
        } else {
            console.warn('Cannot send: WebSocket is not open.');
        }
    }

    function subscribe(conversationId) {
        currentConversationId = parseInt(conversationId, 10);
        send('subscribe', { conversation_id: currentConversationId });
    }

    function unsubscribe(conversationId) {
        send('unsubscribe', { conversation_id: parseInt(conversationId, 10) });
        if (currentConversationId === parseInt(conversationId, 10)) {
            currentConversationId = null;
        }
    }

    function broadcast(conversationId, event, eventData = {}) {
        const payload = {
            conversation_id: parseInt(conversationId, 10),
            event: event,
            data: eventData
        };
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify(Object.assign({ action: 'broadcast' }, payload)));
        } else {
            pendingBroadcasts.push(payload);
        }
    }

    // Expose WebSocket helper globally
    window.ChatRoxWS = {
        connect: connect,
        send: send,
        subscribe: subscribe,
        unsubscribe: unsubscribe,
        broadcast: broadcast,
        getSocket: function () { return socket; }
    };

    // Auto-connect on script load
    connect();

    // Standard client listener for presence changes to update avatar indicators in real-time
    document.addEventListener('chatrox:presence_change', function (e) {
        const data = e.detail;
        if (!data || !data.workspace_member_id) return;
        
        const memberId = data.workspace_member_id;
        const status = data.status; // online/offline

        // Update presence dots in Sidebar or contact listings
        const presenceIndicators = document.querySelectorAll(`[data-member-id="${memberId}"] .presence-dot, [data-member-id="${memberId}"] .sidebar-member-avatar-status`);
        presenceIndicators.forEach(indicator => {
            indicator.className = `presence-dot presence-dot--${status}`;
            if (indicator.classList.contains('sidebar-member-avatar-status')) {
                indicator.className = `sidebar-member-avatar-status sidebar-member-avatar-status--${status}`;
            }
        });
    });

    // ─── Shared helpers ───────────────────────────────────────────────────────

    /**
     * Update the left-nav icon badge for a given tab (e.g. 'dms', 'channels').
     * delta = +1 or -N. Pass null to force-hide the badge.
     */
    function updateNavBadge(tabId, delta) {
        const badge = document.getElementById('navBadge-' + tabId);
        if (!badge) return;
        if (delta === null) {
            badge.textContent = '';
            badge.style.display = 'none';
            return;
        }
        const current = parseInt(badge.textContent, 10) || 0;
        const next = Math.max(0, current + delta);
        if (next > 0) {
            badge.textContent = next;
            badge.style.display = '';
        } else {
            badge.textContent = '';
            badge.style.display = 'none';
        }
    }

    /**
     * Update the home dashboard "Unread Messages" counter.
     * delta = +1 or -N.
     */
    function updateHomeUnreadCount(delta) {
        const el = document.getElementById('homeUnreadCount');
        if (!el) return;
        const current = parseInt(el.textContent, 10) || 0;
        const next = Math.max(0, current + delta);
        el.textContent = next;
    }

    /**
     * Find a DM sidebar item by the other user's username.
     */
    function findDmSidebarItem(username) {
        const dmList = document.querySelector('.dm-list');
        if (!dmList || !username) return null;

        const byData = dmList.querySelector('a[data-dm-username="' + username + '"]');
        if (byData) return byData;

        const links = dmList.querySelectorAll('a');
        for (let i = 0; i < links.length; i++) {
            const href = links[i].getAttribute('href') || '';
            if (href === 'dms/' + username || href === '/dms/' + username ||
                href.endsWith('/dms/' + username) || href.endsWith('dms/' + username)) {
                return links[i];
            }
        }
        return null;
    }

    /**
     * Persist and render the sidebar preview text for a DM item.
     */
    function setSidebarPreview(sidebarItem, text) {
        const previewEl = sidebarItem.querySelector('.dm-msg');
        if (!previewEl) return;

        const display = (window.ChatRoxText && window.ChatRoxText.toSidebarPreview)
            ? window.ChatRoxText.toSidebarPreview(text, 30)
            : ((text || '').replace(/<[^>]*>/g, '') || 'Attachment');

        previewEl.textContent = display;
        sidebarItem.dataset.lastPreview = display;
        sidebarItem.classList.remove('dm-item--typing');
        delete sidebarItem.dataset.originalPreview;
    }

    /**
     * Update the DM sidebar item: preview text, time, unread badge, and move to top.
     */
    function updateDmSidebarItem(username, text, timeLabel, isIncoming, isCurrentlyViewing) {
        const sidebarItem = findDmSidebarItem(username);
        if (!sidebarItem) return;

        // Update preview text
        if (text !== undefined) {
            setSidebarPreview(sidebarItem, text);
        }

        // Update time
        const timeEl = sidebarItem.querySelector('.time');
        if (timeEl && timeLabel) {
            timeEl.textContent = timeLabel;
        }

        // Add/increment unread badge only for incoming messages not currently viewed
        if (isIncoming && !isCurrentlyViewing) {
            let unreadEl = sidebarItem.querySelector('.unread-count');
            if (unreadEl) {
                const count = parseInt(unreadEl.textContent, 10) || 0;
                unreadEl.textContent = count + 1;
            } else {
                const rightEl = sidebarItem.querySelector('.dm-right');
                if (rightEl) {
                    const badge = document.createElement('span');
                    badge.className = 'unread-count';
                    badge.textContent = '1';
                    rightEl.appendChild(badge);
                }
            }
        }

        // Move to top
        const dmList = document.querySelector('.dm-list');
        if (dmList) {
            dmList.insertBefore(sidebarItem, dmList.firstChild);
        }
    }

    /**
     * Clear the unread badge on a DM sidebar item (when user opens the chat).
     */
    function clearDmUnreadBadge(username) {
        const sidebarItem = findDmSidebarItem(username);
        if (!sidebarItem) return;
        const unreadEl = sidebarItem.querySelector('.unread-count');
        if (unreadEl) {
            // Subtract the unread count before removing
            const count = parseInt(unreadEl.textContent, 10) || 0;
            if (count > 0) {
                updateNavBadge('dms', -count);
                updateHomeUnreadCount(-count);
            }
            unreadEl.remove();
        }
    }

    // ─── Global new_message handler ───────────────────────────────────────────
    /**
     * Fires for every new DM message received via WebSocket.
     * Updates: DM sidebar, nav badge, home unread count.
     * chat.js handles in-chat rendering separately.
     */
    document.addEventListener('chatrox:new_message', function (e) {
        const msg = e.detail;
        if (!msg || !window.CHATROX || !window.CHATROX.user) return;

        // Only handle DM conversations here
        if (msg.conversation_type && msg.conversation_type !== 'dm') return;

        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        const senderId = parseInt(msg.sender_id, 10);
        const isIncoming = (senderId !== currentMemberId);

        // Determine the "other" user's username for sidebar lookup
        let targetUsername = null;
        if (isIncoming) {
            targetUsername = msg.sender_username;
        } else {
            targetUsername = msg.recipient_username || null;
        }
        if (!targetUsername) return;

        // Determine if user is currently viewing this conversation
        const chatScreen = document.querySelector('.dm-chat-screen');
        const activeWithUsername = chatScreen ? chatScreen.dataset.withUsername : null;
        const isCurrentlyViewing = (activeWithUsername === targetUsername);

        const msgText = msg.body || (msg.attachments && msg.attachments.length ? 'Sent an attachment' : '');

        // Update DM sidebar
        updateDmSidebarItem(targetUsername, msgText, msg.time_label, isIncoming, isCurrentlyViewing);

        // Update home unread count and nav badge for incoming messages not being viewed
        if (isIncoming && !isCurrentlyViewing) {
            updateHomeUnreadCount(+1);
            updateNavBadge('dms', +1);
        }
    });

    // ─── Clear unread badge when user opens a chat ────────────────────────────
    /**
     * When the DM chat screen is loaded and visible, clear that user's unread badge.
     * Dispatch a custom event from chat.js when a conversation is opened.
     */
    document.addEventListener('chatrox:conversation_opened', function (e) {
        const data = e.detail;
        if (!data || !data.with_username) return;
        clearDmUnreadBadge(data.with_username);
    });

    // ─── Read receipt real-time update (global — works on any open DM chat) ──
    function updateDmReceiptEl(receipt, status) {
        if (!receipt) return;
        const icon = status === 'sent' ? 'check' : 'check-check';
        const label = status === 'read' ? 'Seen' : (status === 'delivered' ? 'Delivered' : 'Sent');
        receipt.className = 'dm-read-receipt dm-read-receipt--' + status;
        receipt.setAttribute('data-read-status', status);
        receipt.setAttribute('title', label);
        receipt.setAttribute('aria-label', label);
        receipt.innerHTML = '<i data-lucide="' + icon + '"></i><span class="dm-read-receipt-label">' + label + '</span>';
    }

    function applyDmDeliveredReceipts(data) {
        if (!data || data.conversation_id == null || !data.message_ids || !data.message_ids.length) return;
        if (!window.CHATROX || !window.CHATROX.user) return;

        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        if (parseInt(data.sender_id, 10) !== currentMemberId) return;

        const chatScreen = document.querySelector('.dm-chat-screen');
        if (!chatScreen || String(chatScreen.dataset.conversationId) !== String(data.conversation_id)) return;

        const dmChatMessages = document.getElementById('dmChatMessages');
        if (!dmChatMessages) return;

        const idSet = {};
        data.message_ids.forEach(function (id) { idSet[parseInt(id, 10)] = true; });

        dmChatMessages.querySelectorAll('.dm-chat-msg--me').forEach(function (msgEl) {
            const matches = (msgEl.id || '').match(/dm-msg-(\d+)/);
            if (!matches) return;
            const msgId = parseInt(matches[1], 10);
            if (!idSet[msgId]) return;

            const receipt = msgEl.querySelector('.dm-read-receipt');
            if (!receipt) return;
            const current = receipt.getAttribute('data-read-status');
            if (current === 'read') return;

            updateDmReceiptEl(receipt, 'delivered');
        });

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function applyDmReadReceipts(data) {
        if (!data || data.conversation_id == null || data.last_read_message_id == null) return;
        if (!window.CHATROX || !window.CHATROX.user) return;

        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        if (parseInt(data.workspace_member_id, 10) === currentMemberId) return;

        const chatScreen = document.querySelector('.dm-chat-screen');
        if (!chatScreen || String(chatScreen.dataset.conversationId) !== String(data.conversation_id)) return;

        const dmChatMessages = document.getElementById('dmChatMessages');
        if (!dmChatMessages) return;

        const lastReadId = parseInt(data.last_read_message_id, 10);
        dmChatMessages.querySelectorAll('.dm-chat-msg--me').forEach(function (msgEl) {
            const matches = (msgEl.id || '').match(/dm-msg-(\d+)/);
            if (!matches) return;
            const msgId = parseInt(matches[1], 10);
            if (msgId > lastReadId) return;

            const receipt = msgEl.querySelector('.dm-read-receipt');
            if (!receipt) return;
            updateDmReceiptEl(receipt, 'read');
        });

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    document.addEventListener('chatrox:messages_delivered', function (e) {
        applyDmDeliveredReceipts(e.detail);
    });

    document.addEventListener('chatrox:conversation_read', function (e) {
        applyDmReadReceipts(e.detail);
    });

    // ─── Typing indicator — DM sidebar ────────────────────────────────────────
    /**
     * Stores per-username timeout handles for auto-clearing "typing..." after 5s.
     * Safety net in case typing_stop is never received (e.g. browser crash).
     */
    const _typingAutoStop = {};

    /**
     * Show "typing..." in the DM sidebar preview under the sender's name.
     */
    function showSidebarTyping(senderUsername) {
        const sidebarItem = findDmSidebarItem(senderUsername);
        if (!sidebarItem) return;

        const previewEl = sidebarItem.querySelector('.dm-msg');
        if (!previewEl) return;

        // Remember last real preview (never overwrite with "typing...")
        if (!sidebarItem.dataset.lastPreview) {
            const current = previewEl.textContent.trim();
            if (current && current !== 'typing...') {
                sidebarItem.dataset.lastPreview = current;
            }
        }

        // Replace with animated "typing..." label
        previewEl.innerHTML = '<span class="dm-typing-label">typing...</span>';
        sidebarItem.classList.add('dm-item--typing');

        // Auto-clear after 5s (safety net)
        clearTimeout(_typingAutoStop[senderUsername]);
        _typingAutoStop[senderUsername] = setTimeout(function () {
            hideSidebarTyping(senderUsername);
        }, 5000);
    }

    /**
     * Restore last message preview after typing stops.
     */
    function hideSidebarTyping(senderUsername) {
        clearTimeout(_typingAutoStop[senderUsername]);
        const sidebarItem = findDmSidebarItem(senderUsername);
        if (!sidebarItem) return;
        const previewEl = sidebarItem.querySelector('.dm-msg');
        if (!previewEl) return;

        sidebarItem.classList.remove('dm-item--typing');
        delete sidebarItem.dataset.originalPreview;

        const restore = (sidebarItem.dataset.lastPreview || '').trim();
        if (restore && restore !== 'typing...') {
            previewEl.textContent = restore;
        } else if (previewEl.querySelector('.dm-typing-label')) {
            previewEl.innerHTML = '';
        }
    }

    // Receive typing_start globally
    document.addEventListener('chatrox:typing_start', function (e) {
        const data = e.detail;
        if (!data) return;
        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        // Ignore our own typing events
        if (parseInt(data.sender_id, 10) === currentMemberId) return;
        if (!data.sender_username) return;
        showSidebarTyping(data.sender_username);
    });

    // Receive typing_stop globally
    document.addEventListener('chatrox:typing_stop', function (e) {
        const data = e.detail;
        if (!data) return;
        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        if (parseInt(data.sender_id, 10) === currentMemberId) return;
        if (!data.sender_username) return;
        hideSidebarTyping(data.sender_username);
    });

    // When a new message arrives, clear typing state (preview already updated above)
    document.addEventListener('chatrox:new_message', function (e) {
        const msg = e.detail;
        if (!msg) return;
        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        if (parseInt(msg.sender_id, 10) !== currentMemberId && msg.sender_username) {
            clearTimeout(_typingAutoStop[msg.sender_username]);
            const sidebarItem = findDmSidebarItem(msg.sender_username);
            if (sidebarItem) {
                sidebarItem.classList.remove('dm-item--typing');
                delete sidebarItem.dataset.originalPreview;
            }
        }
    });

})();
