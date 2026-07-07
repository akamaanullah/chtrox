document.addEventListener('DOMContentLoaded', () => {
    // Create Channel modal — open from Channels +, New Channel card, Browse Create channel
    const createChannelModal = document.getElementById('createChannelModal');
    if (createChannelModal) {
        function openCreateChannelModal() {
            createChannelModal.classList.add('active');
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons({ nodes: [createChannelModal] });
            }
            updateCreateChannelSubmitState();
        }
        var ccChannelName = document.getElementById('ccChannelName');
        var ccSubmitBtn = document.getElementById('ccSubmitBtn');
        function updateCreateChannelSubmitState() {
            if (!ccSubmitBtn || !ccChannelName) return;
            ccSubmitBtn.disabled = ccChannelName.value.trim().length === 0;
        }
        if (ccChannelName && ccSubmitBtn) {
            ccChannelName.addEventListener('input', updateCreateChannelSubmitState);
            ccChannelName.addEventListener('keyup', updateCreateChannelSubmitState);
        }
        function closeCreateChannelModal() {
            createChannelModal.classList.remove('active');
        }

        // Use event delegation on document for opening to cover dynamically swapped SPA buttons
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-open-create-channel-modal');
            if (btn) {
                e.preventDefault();
                openCreateChannelModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            var btn = e.target.closest('.js-open-create-channel-modal');
            if (btn && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                openCreateChannelModal();
            }
        });

        // Use event delegation on modal for closing
        createChannelModal.addEventListener('click', function (e) {
            if (e.target === createChannelModal || e.target.closest('.js-close-create-channel-modal')) {
                closeCreateChannelModal();
            }
        });

        // Search filtering for members list
        var searchPeople = document.getElementById('searchPeople');
        if (searchPeople) {
            searchPeople.addEventListener('input', function () {
                var query = this.value.toLowerCase().trim();
                var rows = createChannelModal.querySelectorAll('.cc-member-row');
                rows.forEach(function (row) {
                    var name = row.getAttribute('data-member-name') || '';
                    if (!query || name.indexOf(query) !== -1) {
                        row.style.display = 'flex';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        var visibilityDesc = document.getElementById('visibilityDesc');
        var visibilityRadios = createChannelModal.querySelectorAll('input[name="visibility"]');
        if (visibilityDesc && visibilityRadios.length) {
            visibilityRadios.forEach(function (r) {
                r.addEventListener('change', function () {
                    visibilityDesc.textContent = this.value === 'public'
                        ? 'Anyone in the workspace can find and join this channel.'
                        : 'Only people you add can access this channel.';
                });
            });
        }

        var selectedCountEl = document.getElementById('selectedCount');
        var memberChecks = createChannelModal.querySelectorAll('.cc-member-check');
        function updateSelectedCount() {
            if (!selectedCountEl) return;
            var n = 0;
            memberChecks.forEach(function (c) { if (c.checked) n++; });
            selectedCountEl.textContent = n + ' selected';
        }
        memberChecks.forEach(function (c) {
            c.addEventListener('change', updateSelectedCount);
        });

        var addAllMembers = document.getElementById('addAllMembers');
        var specificPeopleField = document.getElementById('ccSpecificPeopleField');
        if (addAllMembers && specificPeopleField) {
            function toggleSpecificPeople() {
                specificPeopleField.classList.toggle('cc-field--hidden', addAllMembers.checked);
            }
            addAllMembers.addEventListener('change', toggleSpecificPeople);
            toggleSpecificPeople();
        }

        const form = document.getElementById('createChannelForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!ccSubmitBtn || !ccChannelName) return;

                ccSubmitBtn.disabled = true;

                var visibilityRadio = createChannelModal.querySelector('input[name="visibility"]:checked');
                var addAllCheckbox = document.getElementById('addAllMembers');
                
                var name = ccChannelName.value.trim();
                var visibility = visibilityRadio ? visibilityRadio.value : 'public';
                var addAll = addAllCheckbox ? addAllCheckbox.checked : false;

                var checkedMembers = [];
                createChannelModal.querySelectorAll('.cc-member-check:checked').forEach(function (chk) {
                    var val = parseInt(chk.value, 10);
                    if (val && checkedMembers.indexOf(val) === -1) {
                        checkedMembers.push(val);
                    }
                });

                var payload = {
                    name: name,
                    description: '',
                    visibility: visibility,
                    add_all_members: addAll,
                    members: checkedMembers
                };

                fetch(window.CHATROX.apiUrl + '/channels', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.error) {
                        window.ChatRoxDialog.alert(data.error, 'Error');
                        ccSubmitBtn.disabled = false;
                    } else if (data.success && data.channel) {
                        closeCreateChannelModal();
                        
                        // Reset form
                        ccChannelName.value = '';
                        if (searchPeople) searchPeople.value = '';
                        
                        if (addAllCheckbox) {
                            addAllCheckbox.checked = false;
                            toggleSpecificPeople();
                        }
                        createChannelModal.querySelectorAll('.cc-member-check').forEach(function(chk) {
                            chk.checked = false;
                        });
                        updateSelectedCount();

                        // Navigate to new channel
                        if (window.ChatRoxRouter && typeof window.ChatRoxRouter.navigate === 'function') {
                            window.ChatRoxRouter.navigate('channels/' + data.channel.slug, { force: true });
                        } else {
                            window.location.href = window.CHATROX.baseUrl + '/channels/' + data.channel.slug;
                        }
                    }
                })
                .catch(function (err) {
                    console.error('Failed to create channel:', err);
                    window.ChatRoxDialog.alert('An unexpected error occurred. Please try again.', 'Error');
                    ccSubmitBtn.disabled = false;
                });
            });
        }
    }
});
