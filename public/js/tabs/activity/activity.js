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

        var aqItems = document.querySelectorAll('.aq-item');
        var filterPills = document.querySelectorAll('.filter-pills .pill');
        var clearAllBtn = document.querySelector('.js-clear-all-activity');

        function checkEmptyState() {
            var activeCards = activityFeed.querySelectorAll('.activity-card');
            if (activeCards.length === 0) {
                var emptyBlock = activityFeed.querySelector('.activity-empty-state');
                if (!emptyBlock) {
                    activityFeed.innerHTML = [
                        '<div class="activity-empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 24px; text-align: center; border: 1.5px dashed var(--border-color, #e2e8f0); background: #f8fafc; border-radius: 16px; min-height: 250px; width: 100%; gap: 12px; box-sizing: border-box;">',
                        '    <div style="background: var(--indigo-50, rgba(99, 102, 241, 0.06)); color: var(--indigo-600, #4f46e5); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 4px;">',
                        '        <i data-lucide="bell-off" size="28"></i>',
                        '    </div>',
                        '    <p style="margin: 0; color: var(--text-primary, #0f172a); font-size: 16px; font-weight: 600; font-family: inherit;">No Notifications Yet</p>',
                        '    <span style="color: var(--text-muted, #64748b); font-size: 13px; font-family: inherit; max-width: 320px;">We will notify you here when you receive new mentions, files, or reactions.</span>',
                        '</div>'
                    ].join('\n');
                    if (window.lucide) window.lucide.createIcons();
                }
                if (clearAllBtn) {
                    clearAllBtn.style.display = 'none';
                }
            } else {
                if (clearAllBtn) {
                    clearAllBtn.style.display = 'flex';
                }
            }
        }

        // Run initial empty state check
        checkEmptyState();

        // Use event delegation for card click
        activityFeed.addEventListener('click', function (e) {
            var card = e.target.closest('.activity-card');
            if (!card) return;
            if (e.target.closest('.js-delete-activity') || e.target.closest('.ac-actions')) return;

            var path = card.getAttribute('data-path');
            if (path && window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                window.ChatRoxRouter.navigate(path);
            }
        }, { signal: signal });

        // Add visual cursor for pointer cards
        activityFeed.querySelectorAll('.activity-card').forEach(function (card) {
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

                var activityCards = activityFeed.querySelectorAll('.activity-card');
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

                var activityCards = activityFeed.querySelectorAll('.activity-card');
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

        // Event delegation for delete buttons
        activityFeed.addEventListener('click', function (e) {
            var deleteBtn = e.target.closest('.js-delete-activity');
            if (!deleteBtn) return;

            e.preventDefault();
            e.stopPropagation();

            var card = deleteBtn.closest('.activity-card');
            if (!card) return;
            var notifId = card.getAttribute('data-id');
            if (!notifId) return;

            fetch(window.CHATROX.apiUrl + '/activity/' + notifId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': window.CHATROX.csrfToken
                }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
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
                            card.remove();
                            checkEmptyState();
                        }, 400);
                    }, 400);
                } else {
                    console.error('Failed to delete notification', data.error);
                }
            })
            .catch(function (err) {
                console.error('Error deleting notification', err);
            });
        }, { signal: signal });

        // Event delegation for approve/reject buttons
        activityFeed.addEventListener('click', function (e) {
            var approveBtn = e.target.closest('.js-activity-approve-request');
            if (approveBtn) {
                e.preventDefault();
                e.stopPropagation();
                var requestId = parseInt(approveBtn.getAttribute('data-request-id') || '0', 10);
                if (requestId) approveActivityRequest(requestId, approveBtn);
                return;
            }

            var rejectBtn = e.target.closest('.js-activity-reject-request');
            if (rejectBtn) {
                e.preventDefault();
                e.stopPropagation();
                var requestId = parseInt(rejectBtn.getAttribute('data-request-id') || '0', 10);
                if (requestId) rejectActivityRequest(requestId, rejectBtn);
                return;
            }
        }, { signal: signal });

        // Clear all listener
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                window.ChatRoxDialog.confirm('Are you sure you want to clear all notifications?', 'Clear All Notifications')
                .then(function (confirmed) {
                    if (!confirmed) return;

                    fetch(window.CHATROX.apiUrl + '/activity/clear', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-Token': window.CHATROX.csrfToken
                        }
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            var cards = activityFeed.querySelectorAll('.activity-card');
                            cards.forEach(function (card) {
                                card.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                                card.style.opacity = '0';
                            });
                            setTimeout(function () {
                                cards.forEach(function (card) { card.remove(); });
                                checkEmptyState();
                            }, 400);
                        } else {
                            console.error('Failed to clear notifications', data.error);
                        }
                    })
                    .catch(function (err) {
                        console.error('Error clearing notifications', err);
                    });
                });
            }, { signal: signal });
        }

        function approveActivityRequest(requestId, btnElement) {
            window.ChatRoxDialog.confirm('Approve this join request?', 'Approve Request').then(function (confirmed) {
                if (!confirmed) return;

                fetch(window.CHATROX.apiUrl + '/channels/approve-request', {
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

                fetch(window.CHATROX.apiUrl + '/channels/reject-request', {
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
