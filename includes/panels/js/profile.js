document.addEventListener('DOMContentLoaded', function () {
    // Profile panel - right side
    var profilePanelOverlay = document.getElementById('profilePanelOverlay');
    var profilePanel = document.getElementById('profilePanel');
    if (profilePanelOverlay && profilePanel) {
        function openProfilePanel() {
            profilePanelOverlay.classList.add('active');
            profilePanel.classList.add('active');
            lucide.createIcons();
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

        var profileThemeField = document.getElementById('profileThemeField');
        profilePanel.querySelectorAll('.js-theme-color-toggle').forEach(function (el) {
            el.addEventListener('click', function () {
                if (profileThemeField) profileThemeField.classList.toggle('theme-collapsed');
            });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (profileThemeField) profileThemeField.classList.toggle('theme-collapsed');
                }
            });
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
                var row = btn.closest('.profile-channel-row');
                if (row && (channel === '' || confirm('Leave #' + channel + '?'))) {
                    row.remove();
                    updateProfileChannelsActiveCount();
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
        if (profileIdentityView && profileIdentityEdit && profileNameText && profileEmailText && profileUsernameInput && profileEmailInput) {
            profilePanel.querySelectorAll('.js-profile-edit-identity').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    profileIdentityView.style.display = 'none';
                    profileIdentityEdit.classList.remove('profile-panel-identity-edit--hidden');
                    profileUsernameInput.focus();
                    lucide.createIcons();
                });
            });
            profilePanel.querySelectorAll('.js-profile-done-identity').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    profileNameText.textContent = profileUsernameInput.value.trim() || profileNameText.textContent;
                    profileEmailText.textContent = profileEmailInput.value.trim() || profileEmailText.textContent;
                    profileIdentityEdit.classList.add('profile-panel-identity-edit--hidden');
                    profileIdentityView.style.display = '';
                    lucide.createIcons();
                });
            });
        }
    }
});
