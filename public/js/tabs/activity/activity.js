// Activity tab functionality
(function () {
    var sessionAbort = null;

    function initActivity() {
        if (sessionAbort) {
            sessionAbort.abort();
        }
        sessionAbort = new AbortController();
        var signal = sessionAbort.signal;

        var activityFeed = document.querySelector('.activity-feed');
        if (!activityFeed) return;

        var activityCards = document.querySelectorAll('.activity-card');
        var aqItems = document.querySelectorAll('.aq-item');
        var filterPills = document.querySelectorAll('.filter-pills .pill');

        activityCards.forEach(function (card) {
            card.addEventListener('click', function (e) {
                if (e.target.closest('.js-delete-activity')) return;
                var path = card.getAttribute('data-path');
                if (path && window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                    window.ChatRoxRouter.navigate(path);
                }
            }, { signal: signal });
            if (card.getAttribute('data-path')) {
                card.style.cursor = 'pointer';
            }
        });

        aqItems.forEach(function (item) {
            item.addEventListener('click', function () {
                var targetId = item.getAttribute('data-target');
                if (!targetId) return;

                aqItems.forEach(function (aq) { aq.classList.remove('active'); });
                filterPills.forEach(function (pill) { pill.classList.remove('active'); });
                item.classList.add('active');

                activityCards.forEach(function (card) {
                    card.style.display = 'flex';
                });

                var targetCard = document.getElementById('activity-card-' + targetId);
                if (targetCard) {
                    targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    targetCard.classList.add('activity-card--highlight');
                    setTimeout(function () {
                        targetCard.classList.remove('activity-card--highlight');
                    }, 2000);
                }
            }, { signal: signal });

            item.style.cursor = 'pointer';
        });

        filterPills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                var filterType = pill.textContent.trim().toUpperCase();

                filterPills.forEach(function (p) { p.classList.remove('active'); });
                aqItems.forEach(function (aq) { aq.classList.remove('active'); });
                pill.classList.add('active');

                activityCards.forEach(function (card) {
                    if (filterType === 'ALL') {
                        card.style.display = 'flex';
                        return;
                    }

                    var textContent = card.textContent.toLowerCase();
                    var isMatch = false;

                    if (filterType === 'MENTION' && textContent.includes('mentioned you')) {
                        isMatch = true;
                    } else if (filterType === 'FILE' && (textContent.includes('shared a document') || textContent.includes('uploaded'))) {
                        isMatch = true;
                    } else if (filterType === 'REACTION' && textContent.includes('reacted with')) {
                        isMatch = true;
                    } else if (filterType === 'REQUESTS' && card.classList.contains('activity-card--join-request')) {
                        isMatch = true;
                    }

                    card.style.display = isMatch ? 'flex' : 'none';
                });
            }, { signal: signal });
        });

        document.querySelectorAll('.js-delete-activity').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var card = e.target.closest('.activity-card');
                if (!card) return;

                card.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.transform = 'translateX(100%)';
                card.style.opacity = '0';

                setTimeout(function () {
                    var currentHeight = card.offsetHeight;
                    card.style.height = currentHeight + 'px';
                    card.offsetHeight;
                    card.style.height = '0';
                    card.style.paddingTop = '0';
                    card.style.paddingBottom = '0';
                    card.style.marginTop = '0';
                    card.style.marginBottom = '0';
                    card.style.border = 'none';
                    card.style.overflow = 'hidden';

                    setTimeout(function () {
                        card.style.display = 'none';
                    }, 400);
                }, 400);
            }, { signal: signal });
        });

        // Join request approval/rejection handlers
        document.querySelectorAll('.js-activity-approve-request').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var requestId = parseInt(btn.getAttribute('data-request-id') || '0', 10);
                if (!requestId) return;
                approveActivityRequest(requestId, btn);
            }, { signal: signal });
        });

        document.querySelectorAll('.js-activity-reject-request').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var requestId = parseInt(btn.getAttribute('data-request-id') || '0', 10);
                if (!requestId) return;
                rejectActivityRequest(requestId, btn);
            }, { signal: signal });
        });

        function approveActivityRequest(requestId, btnElement) {
            window.ChatRoxDialog.confirm('Approve this join request?', 'Approve Request').then(function (confirmed) {
                if (!confirmed) return;

                fetch(window.CHATROX.baseUrl + '/api/channels/approve-request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.success) throw new Error(data.error || 'Failed to approve');
                        
                        if (window.ChatRoxWS && data.recipient_id) {
                            window.ChatRoxWS.notifyMembers(data.recipient_id, 'channel_join_request_approved', {
                                channel_id: data.channel_id,
                                channel_name: data.channel_name,
                                channel_slug: data.channel_slug
                            });
                        }

                        var card = btnElement.closest('.activity-card');
                        if (card) {
                            card.style.opacity = '0.6';
                            card.style.pointerEvents = 'none';
                            var actionsDiv = card.querySelector('.ac-actions');
                            if (actionsDiv) {
                                actionsDiv.innerHTML = '<span class="ac-action-status" style="color: #22c55e; font-weight: 600;">Approved ✓</span>';
                            }
                        }
                    })
                    .catch(function (err) {
                        console.error('Error approving request:', err);
                        window.ChatRoxDialog.alert('Failed to approve request: ' + err.message, 'Error');
                    });
            });
        }

        function rejectActivityRequest(requestId, btnElement) {
            window.ChatRoxDialog.confirm('Reject this join request?', 'Reject Request').then(function (confirmed) {
                if (!confirmed) return;

                fetch(window.CHATROX.baseUrl + '/api/channels/reject-request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.success) throw new Error(data.error || 'Failed to reject');

                        if (window.ChatRoxWS && data.recipient_id) {
                            window.ChatRoxWS.notifyMembers(data.recipient_id, 'channel_join_request_rejected', {
                                channel_id: data.channel_id,
                                channel_name: data.channel_name
                            });
                        }

                        var card = btnElement.closest('.activity-card');
                        if (card) {
                            card.style.opacity = '0.6';
                            card.style.pointerEvents = 'none';
                            var actionsDiv = card.querySelector('.ac-actions');
                            if (actionsDiv) {
                                actionsDiv.innerHTML = '<span class="ac-action-status" style="color: #ef4444; font-weight: 600;">Rejected ✗</span>';
                            }
                        }
                    })
                    .catch(function (err) {
                        console.error('Error rejecting request:', err);
                        window.ChatRoxDialog.alert('Failed to reject request: ' + err.message, 'Error');
                    });
            });
        }
    }

    document.addEventListener('chatrox:page_load', initActivity);
})();
