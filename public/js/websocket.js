/**
 * ChatRox WebSocket Client Manager
 * Handles real-time connection, subscriptions, and custom DOM event dispatching.
 */
(function () {
    // --- Dynamic Audio Tone Synthesis using Web Audio API ---
    window.ChatRoxAudio = window.ChatRoxAudio || {
        play: function (tone) {
            try {
                if (!tone || tone === 'none') return;
                
                var ctx = new (window.AudioContext || window.webkitAudioContext)();
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }

                if (tone === 'default' || tone === 'ding') {
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(820, ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(1250, ctx.currentTime + 0.12);
                    gain.gain.setValueAtTime(0.3, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.28);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.3);
                } else if (tone === 'chime') {
                    var playBeep = function (freq, start, duration) {
                        var osc = ctx.createOscillator();
                        var gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, start);
                        gain.gain.setValueAtTime(0.2, start);
                        gain.gain.exponentialRampToValueAtTime(0.01, start + duration);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(start);
                        osc.stop(start + duration);
                    };
                    playBeep(523.25, ctx.currentTime, 0.12);
                    playBeep(659.25, ctx.currentTime + 0.08, 0.18);
                } else if (tone === 'pop') {
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(140, ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(580, ctx.currentTime + 0.06);
                    gain.gain.setValueAtTime(0.4, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.1);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.12);
                } else if (tone === 'ping') {
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(980, ctx.currentTime);
                    gain.gain.setValueAtTime(0.25, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.005, ctx.currentTime + 0.45);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.5);
                }
            } catch (err) {
                console.warn('[ChatRoxAudio] Web Audio API blocked or not supported:', err);
            }
        }
    };

    if (!window.CHATROX || !window.CHATROX.user || !window.CHATROX.user.session_token) {
        console.warn('ChatRox WebSocket: Missing session token, skipping connection.');
        return;
    }

    const wsPort = window.CHATROX.wsPort || 8080;
    const hostname = window.location.hostname || '127.0.0.1';
    
    // Support secure ws connection if page runs on https
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';

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

    async function fetchTicket() {
        const url = (window.CHATROX.apiUrl || (window.CHATROX.baseUrl + '/api/v1')) + '/ws-ticket';
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) {
            throw new Error(`Failed to fetch WebSocket ticket: ${response.statusText}`);
        }
        const data = await response.json();
        if (data.error) {
            throw new Error(data.error);
        }
        return data;
    }

    async function connect() {
        console.log('Fetching WebSocket ticket...');
        let ticketData;
        try {
            ticketData = await fetchTicket();
        } catch (err) {
            console.error('Failed to acquire WebSocket connection ticket:', err);
            scheduleReconnect();
            return;
        }

        const ticket = ticketData.ticket;
        const workspaceId = ticketData.workspace_id;
        const wsUrl = `${protocol}//${hostname}:${wsPort}?token=${ticket}&workspace_id=${workspaceId}`;

        console.log(`Connecting to WebSocket at ${wsUrl.replace(ticket, '***')}...`);
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

                if (payload.event === 'ping') {
                    send('pong');
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

    function notifyMembers(memberIds, event, eventData = {}) {
        const payload = {
            member_ids: Array.isArray(memberIds) ? memberIds : [memberIds],
            event: event,
            data: eventData
        };
        send('notify_members', payload);
    }

    function updatePresence(status) {
        send('presence_change', { status: status });
    }

    // Expose WebSocket helper globally
    window.ChatRoxWS = {
        connect: connect,
        send: send,
        subscribe: subscribe,
        unsubscribe: unsubscribe,
        broadcast: broadcast,
        notifyMembers: notifyMembers,
        updatePresence: updatePresence,
        getSocket: function () { return socket; }
    };

    // Auto-connect on script load
    connect();

    // Standard client listener for presence changes to update avatar indicators in real-time
    document.addEventListener('chatrox:presence_change', function (e) {
        const data = e.detail;
        if (!data || !data.workspace_member_id) return;
        
        const memberId = data.workspace_member_id;
        const status = data.status; // online/away/dnd/offline

        // Update presence dots in Sidebar, chat header, or details panel
        const presenceIndicators = document.querySelectorAll(`[data-member-id="${memberId}"] .presence-dot, [data-member-id="${memberId}"] .status-dot, [data-member-id="${memberId}"] .sidebar-member-avatar-status`);
        presenceIndicators.forEach(indicator => {
            if (indicator.classList.contains('dm-chat-header-status')) {
                indicator.className = `presence-dot dm-chat-header-status dm-chat-header-status--${status}`;
            } else if (indicator.classList.contains('dm-details-status')) {
                indicator.className = `presence-dot dm-details-status dm-details-status--${status}`;
            } else if (indicator.classList.contains('sidebar-member-avatar-status')) {
                indicator.className = `sidebar-member-avatar-status sidebar-member-avatar-status--${status}`;
            } else if (indicator.classList.contains('status-dot')) {
                indicator.className = `status-dot status-dot--${status}`;
            } else {
                indicator.className = `presence-dot presence-dot--${status}`;
            }
        });

        // Update presence text labels in real-time
        const presenceLabels = document.querySelectorAll(`.presence-label[data-member-id="${memberId}"]`);
        presenceLabels.forEach(label => {
            let labelText = 'Offline';
            if (status === 'online') labelText = 'Online';
            else if (status === 'away') labelText = 'Away';
            else if (status === 'dnd') labelText = 'Do Not Disturb';
            label.textContent = labelText;
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
    function sidebarReceiptHtml(status) {
        if (!status) return '';
        const icon = status === 'sent' ? 'check' : 'check-check';
        const label = status === 'read' ? 'Seen' : (status === 'delivered' ? 'Delivered' : 'Sent');
        return '<span class="dm-read-receipt dm-read-receipt--compact dm-read-receipt--' + status + '" data-read-status="' + status + '" title="' + label + '" aria-label="' + label + '">' +
            '<i data-lucide="' + icon + '"></i>' +
            '<span class="dm-read-receipt-label">' + label + '</span>' +
            '</span>';
    }

    function updateSidebarReceiptEl(receipt, status) {
        if (!receipt || !status) return;
        const icon = status === 'sent' ? 'check' : 'check-check';
        const label = status === 'read' ? 'Seen' : (status === 'delivered' ? 'Delivered' : 'Sent');
        receipt.className = 'dm-read-receipt dm-read-receipt--compact dm-read-receipt--' + status;
        receipt.setAttribute('data-read-status', status);
        receipt.setAttribute('title', label);
        receipt.setAttribute('aria-label', label);
        receipt.innerHTML = '<i data-lucide="' + icon + '"></i><span class="dm-read-receipt-label">' + label + '</span>';
    }

    function setSidebarReceiptState(sidebarItem, readStatus) {
        if (!sidebarItem) return;
        if (readStatus) {
            sidebarItem.dataset.lastIsMine = '1';
            sidebarItem.dataset.lastReadStatus = readStatus;
        } else {
            sidebarItem.dataset.lastIsMine = '0';
            delete sidebarItem.dataset.lastReadStatus;
        }
    }

    function renderSidebarPreview(sidebarItem, text, readStatus) {
        const previewEl = sidebarItem.querySelector('.dm-msg');
        if (!previewEl) return;

        const display = (window.ChatRoxText && window.ChatRoxText.toSidebarPreview)
            ? window.ChatRoxText.toSidebarPreview(text, 30)
            : ((text || '').replace(/<[^>]*>/g, '') || 'Attachment');

        let html = '';
        if (readStatus) {
            html += sidebarReceiptHtml(readStatus);
        }
        html += '<span class="dm-msg-text">' + display.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>';
        previewEl.innerHTML = html;
        sidebarItem.dataset.lastPreview = display;
        sidebarItem.classList.remove('dm-item--typing');
        delete sidebarItem.dataset.originalPreview;
        setSidebarReceiptState(sidebarItem, readStatus || null);

        if (window.ChatRoxLucide) {
            window.ChatRoxLucide.refresh(previewEl);
        } else if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons({ nodes: [previewEl] });
        }
    }

    function setSidebarPreview(sidebarItem, text) {
        const readStatus = sidebarItem.dataset.lastIsMine === '1'
            ? (sidebarItem.dataset.lastReadStatus || null)
            : null;
        renderSidebarPreview(sidebarItem, text, readStatus);
    }

    function findSidebarItemByConversation(conversationId) {
        if (!conversationId) return null;
        return document.querySelector('.dm-list a[data-conversation-id="' + conversationId + '"]');
    }

    function upgradeSidebarReceipt(sidebarItem, nextStatus) {
        if (!sidebarItem || sidebarItem.dataset.lastIsMine !== '1') return;
        const order = { sent: 0, delivered: 1, read: 2 };
        const current = sidebarItem.dataset.lastReadStatus || 'sent';
        if ((order[nextStatus] || 0) <= (order[current] || 0)) return;

        sidebarItem.dataset.lastReadStatus = nextStatus;
        const previewEl = sidebarItem.querySelector('.dm-msg');
        const receipt = sidebarItem.querySelector('.dm-msg .dm-read-receipt');
        if (receipt) {
            updateSidebarReceiptEl(receipt, nextStatus);
        } else {
            const textEl = previewEl ? previewEl.querySelector('.dm-msg-text') : null;
            if (previewEl) {
                if (textEl) {
                    textEl.insertAdjacentHTML('beforebegin', sidebarReceiptHtml(nextStatus));
                } else {
                    previewEl.insertAdjacentHTML('afterbegin', sidebarReceiptHtml(nextStatus));
                }
            }
        }
        if (previewEl) {
            if (window.ChatRoxLucide) {
                window.ChatRoxLucide.refresh(previewEl);
            } else if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons({ nodes: [previewEl] });
            }
        }
    }

    function upgradeSidebarReceiptByConversation(conversationId, nextStatus) {
        const sidebarItem = findSidebarItemByConversation(conversationId);
        if (sidebarItem) {
            upgradeSidebarReceipt(sidebarItem, nextStatus);
        }
    }

    /**
     * Update the DM sidebar item: preview text, time, unread badge, read receipt, and move to top.
     */
    function updateDmSidebarItem(username, text, timeLabel, isIncoming, isCurrentlyViewing, readStatus) {
        const sidebarItem = findDmSidebarItem(username);
        if (!sidebarItem) return;

        const chatScreen = document.querySelector('.dm-chat-screen');
        if (chatScreen && chatScreen.dataset.conversationId) {
            sidebarItem.dataset.conversationId = chatScreen.dataset.conversationId;
        }

        if (text !== undefined) {
            renderSidebarPreview(
                sidebarItem,
                text,
                isIncoming ? null : (readStatus || 'sent')
            );
        } else if (!isIncoming && readStatus) {
            upgradeSidebarReceipt(sidebarItem, readStatus);
        }

        const timeEl = sidebarItem.querySelector('.time');
        if (timeEl && timeLabel) {
            timeEl.textContent = timeLabel;
        }

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
        console.log('[ChatRoxWS] clearDmUnreadBadge: sidebarItem =', sidebarItem);
        if (!sidebarItem) return;
        const unreadEl = sidebarItem.querySelector('.unread-count');
        console.log('[ChatRoxWS] clearDmUnreadBadge: unreadEl =', unreadEl);
        if (unreadEl) {
            // Subtract the unread count before removing
            const count = parseInt(unreadEl.textContent, 10) || 0;
            console.log('[ChatRoxWS] clearDmUnreadBadge: count =', count);
            if (count > 0) {
                updateNavBadge('dms', -count);
                updateHomeUnreadCount(-count);
            }
            unreadEl.remove();
        }
    }

    /**
     * Update the channel sidebar item: time, unread badge, and move to top.
     */
    function updateChannelSidebarItem(channelId, time, side) {
        const sidebarItem = document.querySelector('.dir-list a[data-channel-id="' + channelId + '"]');
        if (!sidebarItem) return;

        // 1. Update time and unread badge
        const timeEl = sidebarItem.querySelector('.dir-time');
        if (timeEl) {
            const chatScreen = document.querySelector('.dm-chat-screen');
            const activeChannelId = chatScreen ? chatScreen.dataset.channelId : null;
            const badgeEl = timeEl.querySelector('.badge-dot');
            
            let unreadCount = badgeEl ? (parseInt(badgeEl.textContent, 10) || 0) : 0;
            if (side === 'them' && String(activeChannelId) !== String(channelId)) {
                unreadCount++;
            }
            
            const badgeHtml = unreadCount > 0 ? '<span class="badge-dot">' + unreadCount + '</span>' : '';
            timeEl.innerHTML = time + ' ' + badgeHtml;
        }

        // 2. Move to top
        const dirList = document.querySelector('.dir-list');
        if (dirList && sidebarItem) {
            dirList.insertBefore(sidebarItem, dirList.firstChild);
        }
    }

    /**
     * Clear the unread badge on a channel sidebar item (when user opens the channel).
     */
    function clearChannelUnreadBadge(channelId) {
        const sidebarItem = document.querySelector('.dir-list a[data-channel-id="' + channelId + '"]');
        console.log('[ChatRoxWS] clearChannelUnreadBadge: sidebarItem =', sidebarItem);
        if (!sidebarItem) return;
        const unreadEl = sidebarItem.querySelector('.badge-dot');
        console.log('[ChatRoxWS] clearChannelUnreadBadge: unreadEl =', unreadEl);
        if (unreadEl) {
            const count = parseInt(unreadEl.textContent, 10) || 0;
            console.log('[ChatRoxWS] clearChannelUnreadBadge: count =', count);
            if (count > 0) {
                updateNavBadge('channels', -count);
                updateHomeUnreadCount(-count);
            }
            unreadEl.remove();
        }
    }

    // ─── Global new_message handler ───────────────────────────────────────────
    /**
     * Fires for every new message received via WebSocket.
     * Updates: DM sidebar, nav badge, home dashboard (via ChatRoxHomeLive).
     * chat.js handles in-chat rendering separately.
     */
    document.addEventListener('chatrox:new_message', function (e) {
        const msg = e.detail;
        if (!msg || !window.CHATROX || !window.CHATROX.user) return;

        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        const senderId = parseInt(msg.sender_id, 10);
        const isIncoming = (senderId !== currentMemberId);
        const convType = msg.conversation_type || 'dm';

        if (convType === 'channel') {
            const chatScreen = document.querySelector('.dm-chat-screen');
            const activeChannel = chatScreen ? chatScreen.dataset.channelId : null;
            const isCurrentlyViewing = !!(activeChannel && (
                activeChannel === msg.channel_slug ||
                String(activeChannel) === String(msg.channel_id)
            ));

            const side = isIncoming ? 'them' : 'me';
            updateChannelSidebarItem(msg.channel_id, msg.time_label, side);

            if (isIncoming && !isCurrentlyViewing) {
                updateHomeUnreadCount(+1);
                updateNavBadge('channels', +1);
            }

            if (window.ChatRoxHomeLive) {
                window.ChatRoxHomeLive.scheduleRefresh();
            }
            return;
        }

        if (convType !== 'dm') {
            if (window.ChatRoxHomeLive) {
                window.ChatRoxHomeLive.scheduleRefresh();
            }
            return;
        }

        // Determine the "other" user's username for sidebar lookup
        let targetUsername = null;
        if (isIncoming) {
            targetUsername = msg.sender_username;
        } else {
            targetUsername = msg.recipient_username || null;
        }
        if (!targetUsername) {
            if (window.ChatRoxHomeLive) {
                window.ChatRoxHomeLive.scheduleRefresh();
            }
            return;
        }

        // Determine if user is currently viewing this conversation
        const chatScreen = document.querySelector('.dm-chat-screen');
        const activeWithUsername = chatScreen ? chatScreen.dataset.withUsername : null;
        const isCurrentlyViewing = (activeWithUsername === targetUsername);

        const resolvedType = (window.ChatRoxGiphy && typeof window.ChatRoxGiphy.resolveMessageType === 'function')
            ? window.ChatRoxGiphy.resolveMessageType(msg.message_type, msg.body)
            : msg.message_type;
        const msgText = resolvedType === 'voice' ? 'Voice message'
            : (resolvedType === 'gif' ? 'Photo'
                : (msg.body || (msg.attachments && msg.attachments.length ? 'Sent an attachment' : '')));

        updateDmSidebarItem(
            targetUsername,
            msgText,
            msg.time_label,
            isIncoming,
            isCurrentlyViewing,
            isIncoming ? null : (msg.read_status || 'sent')
        );

        // Update home unread count and nav badge for incoming messages not being viewed
        if (isIncoming && !isCurrentlyViewing) {
            updateHomeUnreadCount(+1);
            updateNavBadge('dms', +1);
        }

        if (window.ChatRoxHomeLive) {
            window.ChatRoxHomeLive.scheduleRefresh();
        }
    });

    // ─── Clear unread badge when user opens a chat ────────────────────────────
    /**
     * When the DM chat screen is loaded and visible, clear that user's unread badge.
     * Dispatch a custom event from chat.js when a conversation is opened.
     */
    document.addEventListener('chatrox:conversation_opened', function (e) {
        const data = e.detail;
        console.log('[ChatRoxWS] conversation_opened received:', data);
        if (!data) return;
        if (data.with_username) {
            console.log('[ChatRoxWS] clearing DM badge for:', data.with_username);
            clearDmUnreadBadge(data.with_username);
        } else if (data.channel_id) {
            console.log('[ChatRoxWS] clearing Channel badge for:', data.channel_id);
            clearChannelUnreadBadge(data.channel_id);
        }
        if (window.ChatRoxHomeLive) {
            window.ChatRoxHomeLive.scheduleRefresh();
        }
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
        if (chatScreen && chatScreen.dataset.withUsername && String(chatScreen.dataset.conversationId) === String(data.conversation_id)) {
            const dmChatMessages = document.getElementById('dmChatMessages');
            if (dmChatMessages) {
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

                if (window.ChatRoxLucide) {
                    window.ChatRoxLucide.refresh(dmChatMessages);
                } else if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons({ nodes: [dmChatMessages] });
                }
            }
        }

        upgradeSidebarReceiptByConversation(data.conversation_id, 'delivered');
    }

    function applyDmReadReceipts(data) {
        if (!data || data.conversation_id == null || data.last_read_message_id == null) return;
        if (!window.CHATROX || !window.CHATROX.user) return;

        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        if (parseInt(data.workspace_member_id, 10) === currentMemberId) return;

        const chatScreen = document.querySelector('.dm-chat-screen');
        if (chatScreen && chatScreen.dataset.withUsername && String(chatScreen.dataset.conversationId) === String(data.conversation_id)) {
            const dmChatMessages = document.getElementById('dmChatMessages');
            if (dmChatMessages) {
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

                if (window.ChatRoxLucide) {
                    window.ChatRoxLucide.refresh(dmChatMessages);
                } else if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons({ nodes: [dmChatMessages] });
                }
            }
        }

        upgradeSidebarReceiptByConversation(data.conversation_id, 'read');
    }

    document.addEventListener('chatrox:messages_delivered', function (e) {
        applyDmDeliveredReceipts(e.detail);
    });

    document.addEventListener('chatrox:conversation_read', function (e) {
        applyDmReadReceipts(e.detail);
    });

    document.addEventListener('chatrox:channel_updated', function (e) {
        const data = e.detail;
        if (!data || !data.channel_id || !data.slug) return;

        // 1. Update any sidebar link pointing to this channel
        const sidebarItem = document.querySelector('.dir-list a[data-channel-id="' + data.channel_id + '"]');
        if (sidebarItem) {
            sidebarItem.setAttribute('href', 'channels/' + data.slug);
            const titleEl = sidebarItem.querySelector('.dir-title');
            if (titleEl) {
                titleEl.textContent = '#' + data.name;
            }
            
            // Update initials if name changed
            const iconEl = sidebarItem.querySelector('.dir-icon-box');
            if (iconEl && data.name) {
                const words = data.name.split(/[-_\s]+/);
                let initials = '';
                words.forEach(w => { initials += w.charAt(0).toUpperCase(); });
                initials = initials.substring(0, 2) || '#';
                iconEl.textContent = initials;
            }
        }

        // 2. If the user is currently viewing this channel, navigate to the new slug in real time
        const chatScreen = document.querySelector('.dm-chat-screen');
        if (chatScreen && String(chatScreen.dataset.channelId) === String(data.channel_id)) {
            if (window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                window.ChatRoxRouter.navigate('channels/' + data.slug, { replace: true, force: true });
            } else {
                window.location.href = window.CHATROX.baseUrl + '/channels/' + data.slug;
            }
        }
    });

    // ─── Typing indicator — DM & Channel sidebar ──────────────────────────────
    /**
     * Stores per-username/conversation timeout handles for auto-clearing "typing..." after 5s.
     * Safety net in case typing_stop is never received (e.g. browser crash).
     */
    const _typingAutoStop = {};

    /**
     * Find a Channel sidebar item by its conversation ID.
     */
    function findChannelSidebarItem(conversationId) {
        const channelList = document.querySelector('.dir-list');
        if (!channelList || !conversationId) return null;
        return channelList.querySelector('a[data-conversation-id="' + conversationId + '"]');
    }

    /**
     * Show "typing..." in the DM or Channel sidebar preview.
     */
    function showSidebarTyping(data) {
        if (!data) return;
        const conversationId = data.conversation_id;
        const senderUsername = data.sender_username;
        const senderName = data.sender_name || senderUsername;

        // Try to find DM item
        let sidebarItem = findDmSidebarItem(senderUsername);
        let previewEl = null;
        let isChannel = false;

        if (sidebarItem) {
            previewEl = sidebarItem.querySelector('.dm-msg');
        } else {
            // Try to find Channel item
            sidebarItem = findChannelSidebarItem(conversationId);
            if (sidebarItem) {
                previewEl = sidebarItem.querySelector('.dir-meta');
                isChannel = true;
            }
        }

        if (!sidebarItem || !previewEl) return;

        const timerKey = isChannel ? ('channel_' + conversationId + '_' + senderUsername) : ('dm_' + senderUsername);

        // Remember last real preview (never overwrite with "typing...")
        if (!sidebarItem.dataset.lastPreview) {
            const textEl = previewEl.querySelector('.dm-msg-text');
            const current = textEl ? textEl.textContent.trim() : previewEl.textContent.trim();
            if (current && current.indexOf('typing...') === -1 && current.indexOf('is typing') === -1) {
                sidebarItem.dataset.lastPreview = current;
            }
        }

        // Replace with animated "typing..." label
        if (isChannel) {
            previewEl.innerHTML = '<span class="dm-typing-label" style="color: var(--indigo-600); font-weight: 500;">' + senderName + ' is typing...</span>';
            sidebarItem.classList.add('channel-item--typing');
        } else {
            previewEl.innerHTML = '<span class="dm-typing-label">typing...</span>';
            sidebarItem.classList.add('dm-item--typing');
        }

        // Auto-clear after 5s (safety net)
        clearTimeout(_typingAutoStop[timerKey]);
        _typingAutoStop[timerKey] = setTimeout(function () {
            hideSidebarTyping(data);
        }, 5000);
    }

    /**
     * Restore last preview after typing stops.
     */
    function hideSidebarTyping(data) {
        if (!data) return;
        const conversationId = data.conversation_id;
        const senderUsername = data.sender_username;

        let sidebarItem = findDmSidebarItem(senderUsername);
        let previewEl = null;
        let isChannel = false;

        if (sidebarItem) {
            previewEl = sidebarItem.querySelector('.dm-msg');
        } else {
            sidebarItem = findChannelSidebarItem(conversationId);
            if (sidebarItem) {
                previewEl = sidebarItem.querySelector('.dir-meta');
                isChannel = true;
            }
        }

        if (!sidebarItem || !previewEl) return;

        const timerKey = isChannel ? ('channel_' + conversationId + '_' + senderUsername) : ('dm_' + senderUsername);
        clearTimeout(_typingAutoStop[timerKey]);

        if (isChannel) {
            sidebarItem.classList.remove('channel-item--typing');
        } else {
            sidebarItem.classList.remove('dm-item--typing');
        }
        delete sidebarItem.dataset.originalPreview;

        const restore = (sidebarItem.dataset.lastPreview || '').trim();
        const readStatus = (!isChannel && sidebarItem.dataset.lastIsMine === '1')
            ? (sidebarItem.dataset.lastReadStatus || 'sent')
            : null;

        if (restore && restore.indexOf('typing...') === -1 && restore.indexOf('is typing') === -1) {
            if (isChannel) {
                previewEl.textContent = restore;
            } else {
                renderSidebarPreview(sidebarItem, restore, readStatus);
            }
        } else {
            if (isChannel) {
                previewEl.textContent = '2 members'; // default fallback
            } else {
                if (previewEl.querySelector('.dm-typing-label')) {
                    previewEl.innerHTML = '<span class="dm-msg-text"></span>';
                }
            }
        }
        
        // Clean up stored state so next time we capture updated values correctly
        delete sidebarItem.dataset.lastPreview;
    }

    // Receive typing_start globally
    document.addEventListener('chatrox:typing_start', function (e) {
        const data = e.detail;
        if (!data) return;
        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        // Ignore our own typing events
        if (parseInt(data.sender_id, 10) === currentMemberId) return;
        showSidebarTyping(data);
    });

    // Receive typing_stop globally
    document.addEventListener('chatrox:typing_stop', function (e) {
        const data = e.detail;
        if (!data) return;
        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        if (parseInt(data.sender_id, 10) === currentMemberId) return;
        hideSidebarTyping(data);
    });

    // When a new message arrives, clear typing state
    document.addEventListener('chatrox:new_message', function (e) {
        const msg = e.detail;
        if (!msg) return;
        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        if (parseInt(msg.sender_id, 10) !== currentMemberId && msg.sender_username) {
            const timerKey = msg.channel_id 
                ? ('channel_' + msg.conversation_id + '_' + msg.sender_username)
                : ('dm_' + msg.sender_username);
            clearTimeout(_typingAutoStop[timerKey]);
            const sidebarItem = msg.channel_id 
                ? findChannelSidebarItem(msg.conversation_id) 
                : findDmSidebarItem(msg.sender_username);
            if (sidebarItem) {
                if (msg.channel_id) {
                    sidebarItem.classList.remove('channel-item--typing');
                    const previewEl = sidebarItem.querySelector('.dir-meta');
                    const restore = (sidebarItem.dataset.lastPreview || '').trim();
                    if (previewEl && restore && restore.indexOf('typing...') === -1 && restore.indexOf('is typing') === -1) {
                        previewEl.textContent = restore;
                    }
                } else {
                    sidebarItem.classList.remove('dm-item--typing');
                }
                delete sidebarItem.dataset.originalPreview;
                delete sidebarItem.dataset.lastPreview;
            }
        }
    });

    // ─── Browser Desktop Notifications ─────────────────────────────────────────

    function initNotificationPermissions() {
        if ("Notification" in window) {
            if (Notification.permission === "default") {
                Notification.requestPermission().catch(err => console.warn('Notification permission request failed', err));
                
                // Fallback for browsers requiring user interaction
                const requestOnGesture = function() {
                    if (Notification.permission === "default") {
                        Notification.requestPermission();
                    }
                    document.removeEventListener('click', requestOnGesture);
                    document.removeEventListener('keydown', requestOnGesture);
                };
                document.addEventListener('click', requestOnGesture);
                document.addEventListener('keydown', requestOnGesture);
            }
        }
    }
    initNotificationPermissions();

    function stripHtml(html) {
        if (!html) return '';
        try {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            return doc.body.textContent || '';
        } catch (e) {
            return html.replace(/<[^>]*>/g, '');
        }
    }

    document.addEventListener('chatrox:new_message', function (e) {
        const msg = e.detail;
        if (!msg || !window.CHATROX || !window.CHATROX.user) return;

        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        const senderId = parseInt(msg.sender_id, 10);

        // Don't notify self
        if (senderId === currentMemberId) return;

        const mentionedIds = Array.isArray(msg.mentioned_ids) ? msg.mentioned_ids : [];
        const conversationType = String(msg.conversation_type || (msg.recipient_username ? 'dm' : 'channel')).toLowerCase();
        const isDirectMessage = conversationType === 'dm';
        const isChannelMessage = conversationType === 'channel';
        const isMention = mentionedIds.includes(currentMemberId);

        if (!isDirectMessage && !isChannelMessage) {
            return;
        }

        // Only increment activity badge for mention notifications.
        if (isMention) {
            updateNavBadge('activity', 1);
        }

        const chatScreen = document.querySelector('.dm-chat-screen');
        let isCurrentlyViewing = false;
        if (chatScreen) {
            if (isChannelMessage) {
                const activeChannel = chatScreen.dataset.channelId;
                isCurrentlyViewing = !!(activeChannel && (
                    activeChannel === msg.channel_slug || String(activeChannel) === String(msg.channel_id)
                ));
            } else if (isDirectMessage) {
                const activeWithUsername = chatScreen.dataset.withUsername;
                isCurrentlyViewing = (activeWithUsername === msg.sender_username || activeWithUsername === msg.recipient_username);
            }
        }

        const isCurrentlyViewingAndActive = isCurrentlyViewing && !document.hidden;

        if (!isCurrentlyViewingAndActive) {
            // Play sound tone if presence status is NOT 'dnd'
            const currentPresence = (window.CHATROX.user.presence_status || 'online').toLowerCase();
            if (currentPresence !== 'dnd') {
                const tone = (window.CHATROX.user.preferences && window.CHATROX.user.preferences.notification_settings)
                    ? window.CHATROX.user.preferences.notification_settings.tone
                    : 'default';
                if (tone && tone !== 'none') {
                    window.ChatRoxAudio.play(tone);
                }
            }

            if (Notification.permission === 'granted') {
                const baseUrl = (window.CHATROX && window.CHATROX.baseUrl) ? String(window.CHATROX.baseUrl).replace(/\/+$/, '') : '';
            const icon = msg.sender_avatar 
                ? (msg.sender_avatar.startsWith('http') ? msg.sender_avatar : (baseUrl + '/' + msg.sender_avatar))
                : (baseUrl + '/assets/images/logo.png');

            let title = msg.sender_name || 'New message';
            let body = stripHtml(msg.body).replace(/\s+/g, ' ').trim() || 'Sent a message';
            let tag = 'message-' + msg.id;
            let path = '';

            if (isDirectMessage) {
                title = msg.sender_name || 'New direct message';
                tag = 'dm-' + msg.id;
                path = `dms/${msg.sender_username}`;
            } else if (isMention) {
                title = `${msg.sender_name} mentioned you`;
                if (msg.channel_name) {
                    body = `mentioned you in #${msg.channel_name}: "${body}"`;
                } else {
                    body = `mentioned you: "${body}"`;
                }
                tag = 'mention-' + msg.id;
                path = `channels/${msg.channel_slug}`;
            } else if (isChannelMessage) {
                title = msg.channel_name ? `New message in #${msg.channel_name}` : 'New channel message';
                tag = 'channel-' + msg.id;
                path = `channels/${msg.channel_slug}`;
            }

            const notification = new Notification(title, {
                body: body,
                icon: icon,
                badge: baseUrl + '/assets/images/logo.png',
                tag: tag,
                silent: true
            });

            notification.onclick = function (event) {
                event.preventDefault();
                window.focus();
                if (path && window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                    window.ChatRoxRouter.navigate(path);
                }
                notification.close();
            };
        }
    }
});

    document.addEventListener('chatrox:message_reaction', function (e) {
        const data = e.detail;
        if (!data || !window.CHATROX || !window.CHATROX.user) return;

        const currentMemberId = parseInt(window.CHATROX.user.workspace_member_id, 10);
        const actorId = parseInt(data.actor_member_id || data.actor_id || 0, 10);
        const recipientId = parseInt(data.recipient_member_id || data.to_member_id || 0, 10);
        if (actorId === currentMemberId) return;
        if (!recipientId || recipientId !== currentMemberId) return;
        if (data.action !== 'added') return;

        updateNavBadge('activity', 1);

        const chatScreen = document.querySelector('.dm-chat-screen');
        const activeConversation = chatScreen ? chatScreen.dataset.conversationId : null;
        const isCurrentlyViewing = activeConversation && String(activeConversation) === String(data.conversation_id);
        const isCurrentlyViewingAndActive = isCurrentlyViewing && !document.hidden;

        if (!isCurrentlyViewingAndActive && Notification.permission === 'granted') {
            const actorName = data.actor_name || data.actor_username || 'Someone';
            const emoji = data.emoji || '';
            const title = `${actorName} reacted to your message`;
            const body = emoji ? `reacted with ${emoji} to your message` : 'reacted to your message';
            const baseUrl = (window.CHATROX && window.CHATROX.baseUrl) ? String(window.CHATROX.baseUrl).replace(/\/+$/, '') : '';

            const notification = new Notification(title, {
                body: body,
                icon: baseUrl + '/assets/images/logo.png',
                badge: baseUrl + '/assets/images/logo.png',
                tag: 'reaction-' + (data.message_id || 'unknown'),
                silent: true
            });

            notification.onclick = function (event) {
                event.preventDefault();
                window.focus();
                if (window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                    window.ChatRoxRouter.navigate('activity');
                }
                notification.close();
            };
        }
    });

    document.addEventListener('chatrox:channel_join_request', function (e) {
        const data = e.detail;
        if (!data || !window.CHATROX || !window.CHATROX.user) return;

        // Increment Activity tab badge count
        updateNavBadge('activity', 1);

        // Show in-app toast notification
        if (window.ChatRoxToast) {
            window.ChatRoxToast.info(`${data.display_name} requested to join #${data.channel_name}.`, 'Join request received');
        }

        // Auto-refresh Activity feed if user is currently looking at it
        if (window.ChatRoxRouter && typeof window.ChatRoxRouter.currentPath === 'function' && window.ChatRoxRouter.currentPath() === 'activity') {
            window.ChatRoxRouter.navigate('activity', { force: true, replace: true });
        }

        // Show browser desktop notification if not actively looking at the activity page
        let isCurrentlyViewingActivity = false;
        if (window.ChatRoxRouter && typeof window.ChatRoxRouter.currentPath === 'function') {
            isCurrentlyViewingActivity = (window.ChatRoxRouter.currentPath() === 'activity');
        } else {
            isCurrentlyViewingActivity = window.location.pathname.indexOf('/activity') !== -1;
        }

        const isCurrentlyViewingAndActive = isCurrentlyViewingActivity && !document.hidden;

        if (!isCurrentlyViewingAndActive && Notification.permission === 'granted') {
            const baseUrl = (window.CHATROX && window.CHATROX.baseUrl) ? String(window.CHATROX.baseUrl).replace(/\/+$/, '') : '';
            const title = 'Join request received';
            const body = `${data.display_name} requested to join #${data.channel_name}.`;

            const notification = new Notification(title, {
                body: body,
                icon: baseUrl + '/assets/images/logo.png',
                badge: baseUrl + '/assets/images/logo.png',
                tag: 'channel-join-request-' + data.channel_id,
                silent: true
            });

            notification.onclick = function (event) {
                event.preventDefault();
                window.focus();
                if (window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                    window.ChatRoxRouter.navigate('activity');
                } else {
                    window.location.href = window.CHATROX.baseUrl + '/activity';
                }
                notification.close();
            };
        }
    });

    document.addEventListener('chatrox:channel_join_request_approved', function (e) {
        const data = e.detail;
        if (!data || !window.CHATROX || !window.CHATROX.user) return;

        // Increment Activity tab badge count
        updateNavBadge('activity', 1);

        // Show in-app toast notification
        if (window.ChatRoxToast) {
            window.ChatRoxToast.success(`Your request to join #${data.channel_name} has been approved.`, 'Request Approved');
        }

        // Auto-refresh Activity feed if user is currently looking at it
        if (window.ChatRoxRouter && typeof window.ChatRoxRouter.currentPath === 'function' && window.ChatRoxRouter.currentPath() === 'activity') {
            window.ChatRoxRouter.navigate('activity', { force: true, replace: true });
        }

        // Show browser desktop notification
        let isCurrentlyViewingChannel = false;
        const chatScreen = document.querySelector('.dm-chat-screen');
        if (chatScreen && chatScreen.dataset.channelId) {
            isCurrentlyViewingChannel = String(chatScreen.dataset.channelId) === String(data.channel_id);
        }

        const isCurrentlyViewingAndActive = isCurrentlyViewingChannel && !document.hidden;

        if (!isCurrentlyViewingAndActive && Notification.permission === 'granted') {
            const baseUrl = (window.CHATROX && window.CHATROX.baseUrl) ? String(window.CHATROX.baseUrl).replace(/\/+$/, '') : '';
            const title = 'Join request approved';
            const body = `Your request to join #${data.channel_name} has been approved.`;
            const path = data.channel_slug ? `channels/${data.channel_slug}` : 'channels';

            const notification = new Notification(title, {
                body: body,
                icon: baseUrl + '/assets/images/logo.png',
                badge: baseUrl + '/assets/images/logo.png',
                tag: 'channel-join-approved-' + data.channel_id,
                silent: true
            });

            notification.onclick = function (event) {
                event.preventDefault();
                window.focus();
                if (window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                    window.ChatRoxRouter.navigate(path);
                } else {
                    window.location.href = window.CHATROX.baseUrl + '/' + path;
                }
                notification.close();
            };
        }
    });

    document.addEventListener('chatrox:channel_join_request_rejected', function (e) {
        const data = e.detail;
        if (!data || !window.CHATROX || !window.CHATROX.user) return;

        // Increment Activity tab badge count
        updateNavBadge('activity', 1);

        // Show in-app toast notification
        if (window.ChatRoxToast) {
            window.ChatRoxToast.error(`Your request to join #${data.channel_name} has been rejected.`, 'Request Rejected');
        }

        // Auto-refresh Activity feed if user is currently looking at it
        if (window.ChatRoxRouter && typeof window.ChatRoxRouter.currentPath === 'function' && window.ChatRoxRouter.currentPath() === 'activity') {
            window.ChatRoxRouter.navigate('activity', { force: true, replace: true });
        }

        // Show browser desktop notification if not actively looking at the activity page
        let isCurrentlyViewingActivity = false;
        if (window.ChatRoxRouter && typeof window.ChatRoxRouter.currentPath === 'function') {
            isCurrentlyViewingActivity = (window.ChatRoxRouter.currentPath() === 'activity');
        } else {
            isCurrentlyViewingActivity = window.location.pathname.indexOf('/activity') !== -1;
        }

        const isCurrentlyViewingAndActive = isCurrentlyViewingActivity && !document.hidden;

        if (!isCurrentlyViewingAndActive && Notification.permission === 'granted') {
            const baseUrl = (window.CHATROX && window.CHATROX.baseUrl) ? String(window.CHATROX.baseUrl).replace(/\/+$/, '') : '';
            const title = 'Join request rejected';
            const body = `Your request to join #${data.channel_name} has been rejected.`;

            const notification = new Notification(title, {
                body: body,
                icon: baseUrl + '/assets/images/logo.png',
                badge: baseUrl + '/assets/images/logo.png',
                tag: 'channel-join-rejected-' + data.channel_id,
                silent: true
            });

            notification.onclick = function (event) {
                event.preventDefault();
                window.focus();
                if (window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                    window.ChatRoxRouter.navigate('activity');
                } else {
                    window.location.href = window.CHATROX.baseUrl + '/activity';
                }
                notification.close();
            };
        }
    });

    document.addEventListener('chatrox:new_notification', function (e) {
        const data = e.detail;
        if (!data || !window.CHATROX || !window.CHATROX.user) return;

        // Increment Activity tab badge count
        updateNavBadge('activity', 1);

        // Play sound tone if presence status is NOT 'dnd'
        const currentPresence = (window.CHATROX.user.presence_status || 'online').toLowerCase();
        if (currentPresence !== 'dnd') {
            const tone = (window.CHATROX.user.preferences && window.CHATROX.user.preferences.notification_settings)
                ? window.CHATROX.user.preferences.notification_settings.tone
                : 'default';
            if (tone && tone !== 'none') {
                window.ChatRoxAudio.play(tone);
            }
        }

        // Show in-app toast notification
        if (window.ChatRoxToast) {
            if (data.type === 'announcement') {
                window.ChatRoxToast.info(data.body, data.title || 'New Announcement');
            } else {
                window.ChatRoxToast.info(data.body, data.title || 'New Notification');
            }
        }

        // Auto-refresh Activity feed if user is currently looking at it
        if (window.ChatRoxRouter && typeof window.ChatRoxRouter.currentPath === 'function' && window.ChatRoxRouter.currentPath() === 'activity') {
            window.ChatRoxRouter.navigate('activity', { force: true, replace: true });
        }

        // Show browser desktop notification if not actively looking at the target page
        let isCurrentlyViewingTarget = false;
        if (data.type === 'announcement') {
            if (window.ChatRoxRouter && typeof window.ChatRoxRouter.currentPath === 'function') {
                isCurrentlyViewingTarget = (window.ChatRoxRouter.currentPath() === 'home');
            } else {
                isCurrentlyViewingTarget = window.location.pathname.indexOf('/home') !== -1;
            }
        } else {
            if (window.ChatRoxRouter && typeof window.ChatRoxRouter.currentPath === 'function') {
                isCurrentlyViewingTarget = (window.ChatRoxRouter.currentPath() === 'activity');
            } else {
                isCurrentlyViewingTarget = window.location.pathname.indexOf('/activity') !== -1;
            }
        }

        const isCurrentlyViewingAndActive = isCurrentlyViewingTarget && !document.hidden;

        if (!isCurrentlyViewingAndActive && Notification.permission === 'granted') {
            const baseUrl = (window.CHATROX && window.CHATROX.baseUrl) ? String(window.CHATROX.baseUrl).replace(/\/+$/, '') : '';
            const title = data.title || 'New Notification';
            const body = data.body || '';
            const path = data.type === 'announcement' ? 'home' : 'activity';

            const notification = new Notification(title, {
                body: body,
                icon: baseUrl + '/assets/images/logo.png',
                badge: baseUrl + '/assets/images/logo.png',
                tag: (data.reference_type || 'system') + '-' + (data.reference_id || 'new'),
                silent: true
            });

            notification.onclick = function (event) {
                event.preventDefault();
                window.focus();
                if (window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                    window.ChatRoxRouter.navigate(path);
                } else {
                    window.location.href = window.CHATROX.baseUrl + '/' + path;
                }
                notification.close();
            };
        }
    });

    document.addEventListener('chatrox:page_load', function () {
        if (window.ChatRoxRouter && typeof window.ChatRoxRouter.currentPath === 'function') {
            const currentPath = window.ChatRoxRouter.currentPath();
            if (currentPath === 'activity') {
                updateNavBadge('activity', null);
            }
        }
    });


    window.ChatRoxDmSidebar = {
        updateItem: updateDmSidebarItem,
        renderPreview: renderSidebarPreview,
        upgradeReceipt: upgradeSidebarReceipt,
        upgradeReceiptByConversation: upgradeSidebarReceiptByConversation,
        findItem: findDmSidebarItem
    };

})();
