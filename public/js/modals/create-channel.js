document.addEventListener('DOMContentLoaded', () => {
    // Create Channel modal — open from Channels +, New Channel card, Browse Create channel
    const createChannelModal = document.getElementById('createChannelModal');
    if (createChannelModal) {
        function openCreateChannelModal() {
            createChannelModal.classList.add('active');
            lucide.createIcons({ nodes: [createChannelModal] });
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

        document.querySelectorAll('.js-open-create-channel-modal').forEach(function (el) {
            el.addEventListener('click', openCreateChannelModal);
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openCreateChannelModal();
                }
            });
        });
        createChannelModal.querySelectorAll('.js-close-create-channel-modal').forEach(function (btn) {
            btn.addEventListener('click', closeCreateChannelModal);
        });
        createChannelModal.addEventListener('click', function (e) {
            if (e.target === createChannelModal) closeCreateChannelModal();
        });

        const form = document.getElementById('createChannelForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                closeCreateChannelModal();
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
    }
});
