document.addEventListener('DOMContentLoaded', function () {
    // Profile panel - right side
    var profilePanelOverlay = document.getElementById('profilePanelOverlay');
    var profilePanel = document.getElementById('profilePanel');
    if (profilePanelOverlay && profilePanel) {
        function openProfilePanel() {
            profilePanelOverlay.classList.add('active');
            profilePanel.classList.add('active');
            lucide.createIcons({ nodes: [profilePanel] });
        }
        function closeProfilePanel() {
            profilePanelOverlay.classList.remove('active');
            profilePanel.classList.remove('active');
        }
        document.querySelectorAll('.js-open-profile-panel').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                openProfilePanel();
            });
        });
        profilePanelOverlay.addEventListener('click', closeProfilePanel);
        profilePanel.querySelectorAll('.js-close-profile-panel').forEach(function (btn) {
            btn.addEventListener('click', closeProfilePanel);
        });


        function updateProfileChannelsActiveCount() {
            var list = document.getElementById('profileChannelsList');
            var badge = document.getElementById('profileChannelsActiveBadge');
            if (list && badge) {
                var visible = list.querySelectorAll('.profile-channel-row:not(.profile-channel-row--hidden)').length;
                badge.textContent = visible + ' active';
            }
        }

        profilePanel.querySelectorAll('.js-leave-channel').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var channel = btn.getAttribute('data-channel') || '';
                var channelId = btn.getAttribute('data-channel-id');
                var row = btn.closest('.profile-channel-row');
                if (row && channelId) {
                    window.ChatRoxDialog.confirm('Leave #' + channel + '?', 'Leave Channel').then(function (confirmed) {
                        if (confirmed) {
                            btn.disabled = true;
                            fetch(window.CHATROX.apiUrl + '/channels/leave', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({ channel_id: parseInt(channelId, 10) })
                            })
                            .then(function (res) { return res.json(); })
                            .then(function (data) {
                                if (data.error) {
                                    window.ChatRoxDialog.alert(data.error, 'Error');
                                    btn.disabled = false;
                                } else if (data.success) {
                                    // Broadcast leave system messages via WebSocket
                                    if (data.system_messages && data.conversation_id && window.ChatRoxWS) {
                                        data.system_messages.forEach(function (msg) {
                                            window.ChatRoxWS.broadcast(data.conversation_id, 'new_message', msg);
                                        });
                                    }

                                    row.remove();
                                    updateProfileChannelsActiveCount();
                                    // If we are currently in this channel's chat, redirect to channels
                                    const chatScreen = document.querySelector('.dm-chat-screen');
                                    if (chatScreen && String(chatScreen.dataset.channelId) === String(channelId)) {
                                        if (window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                                            window.ChatRoxRouter.navigate('channels', { replace: true, force: true });
                                        } else {
                                            window.location.href = window.CHATROX.baseUrl + '/channels';
                                        }
                                    }
                                }
                            })
                            .catch(function (err) {
                                console.error('Failed to leave channel:', err);
                                window.ChatRoxDialog.alert('An unexpected error occurred. Please try again.', 'Error');
                                btn.disabled = false;
                            });
                        }
                    });
                }
            });
        });

        var joinedChannelsSearch = document.querySelector('.js-joined-channels-search');
        var profileChannelsList = document.getElementById('profileChannelsList');
        if (joinedChannelsSearch && profileChannelsList) {
            joinedChannelsSearch.addEventListener('input', function () {
                var q = this.value.trim().toLowerCase();
                profileChannelsList.querySelectorAll('.profile-channel-row').forEach(function (row) {
                    var name = (row.getAttribute('data-channel-name') || '').toLowerCase();
                    var match = !q || name.indexOf(q) !== -1;
                    row.classList.toggle('profile-channel-row--hidden', !match);
                });
                updateProfileChannelsActiveCount();
            });
        }

        var profileIdentityView = document.getElementById('profileIdentityView');
        var profileIdentityEdit = document.getElementById('profileIdentityEdit');
        var profileNameText = document.getElementById('profileNameText');
        var profileEmailText = document.getElementById('profileEmailText');
        var profileUsernameInput = document.getElementById('profileUsername');
        var profileEmailInput = document.getElementById('profileEmail');
        var profileBio = document.getElementById('profileBio');
        
        function saveProfileInfo(fullName, bioText) {
            var spaceIndex = fullName.indexOf(' ');
            var firstName = fullName;
            var lastName = '';
            if (spaceIndex !== -1) {
                firstName = fullName.substring(0, spaceIndex);
                lastName = fullName.substring(spaceIndex + 1);
            }

            return fetch(window.CHATROX.apiUrl + '/profile', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    first_name: firstName,
                    last_name: lastName,
                    bio: bioText,
                    phone: window.CHATROX.user.phone || '',
                    job_title: window.CHATROX.user.job_title || ''
                })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    window.ChatRoxDialog.alert(data.error, 'Error');
                    throw new Error(data.error);
                }
                // Update global CHATROX user values
                window.CHATROX.user.first_name = firstName;
                window.CHATROX.user.last_name = lastName;
                window.CHATROX.user.bio = bioText;
                return data;
            });
        }

        if (profileIdentityView && profileIdentityEdit && profileNameText && profileEmailText && profileUsernameInput && profileEmailInput) {
            profilePanel.querySelectorAll('.js-profile-edit-identity').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    profileIdentityView.style.display = 'none';
                    profileIdentityEdit.classList.remove('profile-panel-identity-edit--hidden');
                    profileUsernameInput.focus();
                    lucide.createIcons({ nodes: [profileIdentityEdit] });
                });
            });

            profilePanel.querySelectorAll('.js-profile-done-identity').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var newName = profileUsernameInput.value.trim();
                    if (!newName) {
                        window.ChatRoxDialog.alert('Name cannot be empty', 'Validation Error');
                        return;
                    }
                    var currentBio = profileBio ? profileBio.value.trim() : '';

                    saveProfileInfo(newName, currentBio)
                    .then(function() {
                        profileNameText.textContent = newName;
                        profileIdentityEdit.classList.add('profile-panel-identity-edit--hidden');
                        profileIdentityView.style.display = '';
                        lucide.createIcons({ nodes: [profileIdentityView] });
                    })
                    .catch(function(err) {
                        console.error('Error saving profile:', err);
                    });
                });
            });
        }

        if (profileBio) {
            profileBio.addEventListener('blur', function () {
                var currentName = profileUsernameInput ? profileUsernameInput.value.trim() : profileNameText.textContent.trim();
                var newBio = profileBio.value.trim();
                if (newBio !== (window.CHATROX.user.bio || '')) {
                    saveProfileInfo(currentName, newBio)
                    .catch(function(err) {
                        console.error('Error saving bio:', err);
                    });
                }
            });
        }

        // --- Profile Avatar Uploader ---
        var profileAvatarInput = document.getElementById('profileAvatarInput');
        var profilePanelAvatarImg = document.getElementById('profilePanelAvatarImg');
        if (profileAvatarInput && profilePanelAvatarImg) {
            profileAvatarInput.addEventListener('change', function () {
                if (profileAvatarInput.files.length === 0) return;
                var file = profileAvatarInput.files[0];
                var formData = new FormData();
                formData.append('avatar', file);

                // Show a loading indicator/opacity
                profilePanelAvatarImg.style.opacity = '0.5';

                fetch(window.CHATROX.apiUrl + '/profile/avatar', {
                    method: 'POST',
                    body: formData
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    profilePanelAvatarImg.style.opacity = '1';
                    if (data.error) {
                        window.ChatRoxDialog.alert(data.error, 'Upload Error');
                    } else if (data.success && data.avatar_path) {
                        profilePanelAvatarImg.src = data.avatar_path;
                        // Also update any other avatar occurrences on the page (e.g. sidebar profile thumb)
                        var userThumb = document.querySelector('.sidebar-user-avatar, .sidebar-profile-avatar');
                        if (userThumb) {
                            userThumb.src = data.avatar_path;
                        }
                    }
                })
                .catch(function (err) {
                    profilePanelAvatarImg.style.opacity = '1';
                    console.error('Avatar upload failed:', err);
                    window.ChatRoxDialog.alert('Failed to upload image. Please try again.', 'Error');
                });
            });
        }
    }
});
