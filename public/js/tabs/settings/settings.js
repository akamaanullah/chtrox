// Settings tab functionality
(function () {
    'use strict';

    // --- Dynamic Audio Tone Synthesis using Web Audio API ---
    window.ChatRoxAudio = {
        play: function (tone) {
            try {
                if (!tone || tone === 'none') return;
                
                var ctx = new (window.AudioContext || window.webkitAudioContext)();
                if (ctx.state === 'suspended') {
                    // AudioContext requires user interaction first in some browsers
                    ctx.resume();
                }

                if (tone === 'default' || tone === 'ding') {
                    // Ding: Sine wave sliding up in frequency
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
                    // Chime: Sweet double-beep (C5 followed by E5)
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
                    // Pop: Short plucky woody triangle pop
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
                    // Ping: Crystal clean decay sine wave
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

    var sessionAbort = null;

    function initSettings() {
        if (sessionAbort) {
            sessionAbort.abort();
        }
        sessionAbort = new AbortController();
        var signal = sessionAbort.signal;

        var container = document.querySelector('.settings-page');
        if (!container) return;

        // --- Sound Test Button ---
        var btnSoundTest = document.getElementById('btnSoundTest');
        var selectTone = document.getElementById('notif-tone');
        if (btnSoundTest && selectTone) {
            btnSoundTest.addEventListener('click', function () {
                var selected = selectTone.value || 'default';
                window.ChatRoxAudio.play(selected);
            }, { signal: signal });
        }

        // --- Re-bind Theme Swatches via themes-shared.js ---
        if (window.ChatroxTheme && typeof window.ChatroxTheme.init === 'function') {
            window.ChatroxTheme.init();
        }

        // Add custom theme save hook on radio select
        document.querySelectorAll('input[name="theme_color"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (this.checked) {
                    var theme = this.value;
                    
                    // Visual update for active card border highlight
                    document.querySelectorAll('.theme-card-option').forEach(function (card) {
                        card.classList.toggle('active-card', card.getAttribute('data-theme') === theme);
                    });
                    
                    // Fetch PATCH update theme color in database
                    fetch(window.CHATROX.apiUrl + '/settings', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            theme_color: theme,
                            notification_settings: getFormNotificationSettings()
                        })
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            if (window.CHATROX.user.preferences) {
                                window.CHATROX.user.preferences.theme_color = theme;
                            }
                        }
                    })
                    .catch(function (err) {
                        console.error('Failed to save theme setting in DB:', err);
                    });
                }
            }, { signal: signal });
        });

        // --- Save Preferences ---
        var btnSavePref = document.getElementById('btnSavePreferences');
        if (btnSavePref) {
            btnSavePref.addEventListener('click', function () {
                var config = getFormNotificationSettings();
                btnSavePref.disabled = true;

                fetch(window.CHATROX.apiUrl + '/settings', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        notification_settings: config
                    })
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    btnSavePref.disabled = false;
                    if (data.error) {
                        window.ChatRoxDialog.alert(data.error, 'Error');
                    } else if (data.success) {
                        if (window.CHATROX.user.preferences) {
                            window.CHATROX.user.preferences.notification_settings = config;
                        }
                        window.ChatRoxDialog.alert('Preferences updated successfully!', 'Settings Saved');
                    }
                })
                .catch(function (err) {
                    btnSavePref.disabled = false;
                    console.error('Failed to save preferences:', err);
                    window.ChatRoxDialog.alert('Unable to save settings. Please try again.', 'Error');
                });
            }, { signal: signal });
        }

        // Helper to assemble config from inputs
        function getFormNotificationSettings() {
            return {
                all: document.getElementById('notif-all') ? document.getElementById('notif-all').checked : true,
                dm: document.getElementById('notif-dm') ? document.getElementById('notif-dm').checked : true,
                channels: document.getElementById('notif-channels') ? document.getElementById('notif-channels').checked : true,
                channel_requests: document.getElementById('notif-requests') ? document.getElementById('notif-requests').checked : true,
                mentions: document.getElementById('notif-mentions') ? document.getElementById('notif-mentions').checked : true,
                tone: selectTone ? selectTone.value : 'default'
            };
        }

        // --- Change Password Form ---
        var formChangePwd = document.getElementById('formChangePassword');
        var btnUpdatePwd = document.getElementById('btnUpdatePassword');
        if (formChangePwd && btnUpdatePwd) {
            formChangePwd.addEventListener('submit', function (e) {
                e.preventDefault();

                var currentPwd = document.getElementById('current_password').value;
                var newPwd = document.getElementById('new_password').value;
                var confirmPwd = document.getElementById('confirm_password').value;

                if (!currentPwd || !newPwd || !confirmPwd) {
                    window.ChatRoxDialog.alert('All fields are required.', 'Validation Error');
                    return;
                }

                if (newPwd.length < 6) {
                    window.ChatRoxDialog.alert('New password must be at least 6 characters.', 'Validation Error');
                    return;
                }

                if (newPwd !== confirmPwd) {
                    window.ChatRoxDialog.alert('New password and confirmation do not match.', 'Validation Error');
                    return;
                }

                btnUpdatePwd.disabled = true;

                fetch(window.CHATROX.apiUrl + '/settings/change-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        current_password: currentPwd,
                        new_password: newPwd,
                        confirm_password: confirmPwd
                    })
                })
                .then(function (res) {
                    return res.json().catch(function () {
                        throw new Error('Server returned an invalid response (Status: ' + res.status + ').');
                    });
                })
                .then(function (data) {
                    btnUpdatePwd.disabled = false;
                    if (data.error) {
                        window.ChatRoxDialog.alert(data.error, 'Error');
                    } else if (data.success) {
                        formChangePwd.reset();
                        window.ChatRoxDialog.alert('Password changed successfully!', 'Security Updated');
                    }
                })
                .catch(function (err) {
                    btnUpdatePwd.disabled = false;
                    console.error('Password change failed:', err);
                    window.ChatRoxDialog.alert(err.message || 'Failed to change password. Please try again.', 'Error');
                });
            }, { signal: signal });
        }

        // --- Presence & Sessions Management ---
        var selectPresence = document.getElementById('selectPresenceStatus');
        var btnUpdatePresence = document.getElementById('btnUpdatePresence');
        var sessionsList = document.getElementById('sessionsListContainer');
        var sessionBadge = document.getElementById('sessionCountBadge');

        // Fetch settings from server to prefill presence select
        fetch(window.CHATROX.apiUrl + '/settings', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: signal
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success && data.preferences && data.preferences.presence_status && selectPresence) {
                selectPresence.value = data.preferences.presence_status;
            }
        })
        .catch(function (err) {
            console.error('Failed to load current presence status:', err);
        });

        // Update Presence Status
        if (btnUpdatePresence && selectPresence) {
            btnUpdatePresence.addEventListener('click', function () {
                var status = selectPresence.value;
                btnUpdatePresence.disabled = true;

                fetch(window.CHATROX.apiUrl + '/settings/presence', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    btnUpdatePresence.disabled = false;
                    if (data.error) {
                        window.ChatRoxToast ? window.ChatRoxToast.error(data.error) : alert(data.error);
                    } else if (data.success) {
                        if (window.CHATROX.user) {
                            window.CHATROX.user.presence_status = status;
                        }
                        if (window.ChatRoxWS && typeof window.ChatRoxWS.updatePresence === 'function') {
                            window.ChatRoxWS.updatePresence(status);
                        }
                        window.ChatRoxToast ? window.ChatRoxToast.success('Presence status updated successfully') : alert(data.message);
                    }
                })
                .catch(function (err) {
                    btnUpdatePresence.disabled = false;
                    console.error('Failed to update presence:', err);
                    window.ChatRoxToast ? window.ChatRoxToast.error('Failed to update presence status') : alert('Failed to update presence');
                });
            }, { signal: signal });
        }

        // Fetch & render sessions list
        function loadSessions() {
            if (!sessionsList) return;

            fetch(window.CHATROX.apiUrl + '/settings/sessions', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: signal
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    sessionsList.innerHTML = '<div style="color: var(--text-secondary); font-size: 14px;">Error loading active sessions.</div>';
                    return;
                }

                var sessions = data.sessions || [];
                if (sessionBadge) {
                    sessionBadge.textContent = sessions.length;
                }

                if (sessions.length === 0) {
                    sessionsList.innerHTML = '<div style="color: var(--text-secondary); font-size: 14px;">No active sessions found.</div>';
                    return;
                }

                var html = '';
                sessions.forEach(function (sess) {
                    var lastSeen = sess.last_seen ? new Date(sess.last_seen).toLocaleString() : 'Never';
                    var created = sess.created_at ? new Date(sess.created_at).toLocaleDateString() : 'Unknown';
                    
                    var currentBadge = sess.is_current ? '<span class="session-current-badge">Current Session</span>' : '';
                    var revokeBtn = sess.is_current ? '' : '<button type="button" class="btn-revoke-session js-revoke-session" data-id="' + sess.id + '"><i data-lucide="trash-2" size="14"></i> Revoke</button>';
                    
                    // Simple browser detection icon
                    var icon = 'laptop';
                    var devLower = (sess.device_name || '').toLowerCase();
                    if (devLower.indexOf('phone') !== -1 || devLower.indexOf('android') !== -1 || devLower.indexOf('iphone') !== -1) {
                        icon = 'smartphone';
                    }

                    html += '<div class="session-item">' +
                        '<div class="session-info">' +
                            '<div class="session-icon"><i data-lucide="' + icon + '" size="18"></i></div>' +
                            '<div class="session-details">' +
                                '<div class="session-device">' +
                                    '<span>' + (sess.device_name || 'Unknown Device') + '</span>' +
                                    currentBadge +
                                '</div>' +
                                '<div class="session-meta">' +
                                    'IP: ' + (sess.ip_address || 'Unknown') + ' &bull; Last seen: ' + lastSeen + ' &bull; Logged in: ' + created +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        revokeBtn +
                    '</div>';
                });

                sessionsList.innerHTML = html;

                // Re-bind Lucide icons
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons({ nodes: [sessionsList] });
                }

                // Bind revoke handlers
                sessionsList.querySelectorAll('.js-revoke-session').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var sessId = this.getAttribute('data-id');
                        window.ChatRoxDialog.confirm('Are you sure you want to revoke this session? The device will be signed out immediately.', 'Revoke Session').then(function (confirmed) {
                            if (!confirmed) return;
                            
                            btn.disabled = true;
                            fetch(window.CHATROX.apiUrl + '/settings/sessions/revoke', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({ session_id: sessId })
                            })
                            .then(function (res) { return res.json(); })
                            .then(function (resData) {
                                if (resData.success) {
                                    window.ChatRoxToast ? window.ChatRoxToast.success('Session revoked successfully') : alert('Session revoked');
                                    loadSessions();
                                } else {
                                    btn.disabled = false;
                                    window.ChatRoxToast ? window.ChatRoxToast.error(resData.error || 'Failed to revoke session') : alert(resData.error || 'Failed');
                                }
                            })
                            .catch(function (err) {
                                btn.disabled = false;
                                console.error('Failed to revoke session:', err);
                            });
                        });
                    });
                });
            })
            .catch(function (err) {
                console.error('Failed to load active sessions:', err);
                sessionsList.innerHTML = '<div style="color: var(--text-secondary); font-size: 14px;">Error loading active sessions.</div>';
            });
        }

        // Trigger load
        loadSessions();

        // --- Settings Tab Switcher ---
        var subnavLinks = document.querySelectorAll('.settings-subnav-link');
        var tabContents = document.querySelectorAll('.settings-tab-content');

        function switchTab(targetId) {
            // Update subnav link classes and active states
            subnavLinks.forEach(function (link) {
                var href = link.getAttribute('href');
                var isActive = href === '#section-' + targetId;
                
                link.classList.toggle('active', isActive);
                if (isActive) {
                    link.style.background = 'var(--indigo-50, rgba(99, 102, 241, 0.08))';
                    link.style.color = 'var(--indigo-600, #4f46e5)';
                    link.style.fontWeight = '600';
                } else {
                    link.style.background = '';
                    link.style.color = 'var(--text-secondary, #475569)';
                    link.style.fontWeight = '500';
                }
            });

            // Toggle settings section visibility
            tabContents.forEach(function (card) {
                var tabName = card.getAttribute('data-settings-tab');
                card.classList.toggle('active', tabName === targetId);
            });
        }

        // Add event listeners on subnav links
        subnavLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var href = this.getAttribute('href') || '';
                var targetId = href.replace('#section-', '');
                if (targetId) {
                    switchTab(targetId);
                    // Silently update the hash in address bar without triggering popstate/navigate
                    var newUrl = window.location.pathname + href;
                    window.history.replaceState(window.history.state, '', newUrl);
                }
            }, { signal: signal });
        });

        // Initialize active tab from URL hash (e.g. #section-theme) or default to 'notifications'
        var initialHash = window.location.hash || '';
        var initialTarget = initialHash.replace('#section-', '') || 'notifications';
        
        var validTabs = ['notifications', 'theme', 'password', 'security', 'usage'];
        if (validTabs.indexOf(initialTarget) === -1) {
            initialTarget = 'notifications';
        }
        switchTab(initialTarget);
    }

    document.addEventListener('chatrox:page_load', initSettings);
})();
