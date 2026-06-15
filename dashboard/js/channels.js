document.addEventListener('DOMContentLoaded', function () {
    const utils = window.ChatroxUtils;
    const channelSearch = document.getElementById('channelSearch');
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const channelsTable = document.getElementById('channelsTable');
    const channelRows = Array.from(channelsTable.querySelectorAll('tbody .channel-row'));
    
    const rowsPerPageSelect = document.getElementById('rowsPerPage');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const pageNumbersContainer = document.getElementById('pageNumbers');
    const showingStart = document.getElementById('showingStart');
    const showingEnd = document.getElementById('showingEnd');
    const totalRowsSpan = document.getElementById('totalRows');
    const noResults = document.getElementById('noResults');

    let currentPage = 1;
    let rowsPerPage = parseInt(rowsPerPageSelect.value);
    let filteredRows = [...channelRows];

    function updatePagination() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        
        // Boundaries
        if (currentPage > totalPages) currentPage = totalPages || 1;
        if (currentPage < 1) currentPage = 1;

        // Visibility
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        channelRows.forEach(row => row.style.display = 'none');
        
        const currentSlice = filteredRows.slice(start, end);
        currentSlice.forEach(row => row.style.display = '');

        // Info text
        showingStart.textContent = totalRows === 0 ? 0 : start + 1;
        showingEnd.textContent = Math.min(end, totalRows);
        totalRowsSpan.textContent = totalRows;

        // No Results State
        if (totalRows === 0) {
            channelsTable.style.display = 'none';
            noResults.style.display = 'flex';
        } else {
            channelsTable.style.display = '';
            noResults.style.display = 'none';
        }

        // Render Page Numbers
        renderPageNumbers(totalPages);

        // Update Buttons
        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;

        // Refresh Lucide icons for dynamic content
        if (window.lucide) window.lucide.createIcons();
    }

    function renderPageNumbers(totalPages) {
        pageNumbersContainer.innerHTML = '';
        
        // Simple pagination: show all for now, or could truncate if many pages
        for (let i = 1; i <= totalPages; i++) {
            const pageNum = document.createElement('div');
            pageNum.className = `page-num ${i === currentPage ? 'active' : ''}`;
            pageNum.textContent = i;
            pageNum.addEventListener('click', () => {
                currentPage = i;
                updatePagination();
                channelsTable.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            pageNumbersContainer.appendChild(pageNum);
        }
    }

    function applyFilters() {
        const searchQuery = channelSearch.value.toLowerCase().trim();
        const typeQuery = typeFilter.value; // Public/Private
        const statusQuery = statusFilter.value; // Active/Archived

        filteredRows = channelRows.filter(row => {
            const name = row.querySelector('.member-name').textContent.toLowerCase();
            const topic = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const type = row.querySelector('.role-badge').textContent.trim();
            const status = row.querySelector('.status-indicator span:last-child').textContent.trim();

            const matchesSearch = name.includes(searchQuery) || topic.includes(searchQuery);
            const matchesType = !typeQuery || type === typeQuery;
            const matchesStatus = !statusQuery || status === statusQuery;

            return matchesSearch && matchesType && matchesStatus;
        });

        currentPage = 1;
        updatePagination();
    }

    // Event Listeners
    channelSearch.addEventListener('input', applyFilters);
    typeFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    
    const resetBtn = document.querySelector('.reset-filters-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            channelSearch.value = '';
            typeFilter.value = '';
            statusFilter.value = '';
            applyFilters();
        });
    }

    rowsPerPageSelect.addEventListener('change', function () {
        rowsPerPage = parseInt(this.value);
        currentPage = 1;
        updatePagination();
    });

    prevPageBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            updatePagination();
        }
    });

    nextPageBtn.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            updatePagination();
        }
    });

    // Initial load
    updatePagination();

    // --- Modal Logic (Synced and Optimized) ---
    const createChannelModal = document.getElementById('createChannelModal');
    const editChannelModal = document.getElementById('editChannelModal');
    
    // Create Channel
    const openCreateBtn = document.querySelector('.create-channel-btn');
    const createForm = document.getElementById('createChannelForm');
    const ccChannelName = document.getElementById('ccChannelName');
    const ccSubmitBtn = document.getElementById('ccSubmitBtn');

    if (openCreateBtn) {
        openCreateBtn.addEventListener('click', () => {
            if (createForm) createForm.reset();
            createChannelModal.classList.add('active');
            lucide.createIcons();
            utils.updateSelectedCount(createChannelModal, 'selectedCount', '.cc-member-check');
        });
    }

    createChannelModal.querySelectorAll('.js-close-create-channel-modal').forEach(btn => {
        btn.addEventListener('click', () => utils.closeModal(createChannelModal));
    });

    if (createForm) {
        createForm.addEventListener('submit', (e) => {
            e.preventDefault();
            ccSubmitBtn.innerHTML = '<i data-lucide="loader" class="spin" size="18"></i> <span>CREATING...</span>';
            lucide.createIcons();
            setTimeout(() => {
                utils.closeModal(createChannelModal);
                ccSubmitBtn.innerHTML = 'FINALIZE & CREATE CHANNEL';
                lucide.createIcons();
            }, 1200);
        });
    }

    // Edit Channel (Using Delegation for Paginated Rows)
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.js-open-edit-channel-modal');
        if (editBtn) {
            const row = editBtn.closest('.channel-row');
            const rowData = {
                name: row.querySelector('.member-name').textContent.trim(),
                privacy: row.querySelector('.role-badge').textContent.trim(),
                status: row.querySelector('.status-indicator span:last-child').textContent.trim()
            };
            openEditChannelModal(rowData);
        }
    });

    function openEditChannelModal(rowData) {
        document.getElementById('editChannelName').value = rowData.name;
        document.getElementById('editOriginalName').value = rowData.name;
        
        if (rowData.privacy === 'Public') {
            document.getElementById('editVisPublic').checked = true;
        } else {
            document.getElementById('editVisPrivate').checked = true;
        }
        
        editChannelModal.classList.add('active');
        lucide.createIcons();
        utils.updateSelectedCount(editChannelModal, 'editSelectedCount', '.edit-member-check');
    }

    editChannelModal.querySelectorAll('.js-close-edit-channel-modal').forEach(btn => {
        btn.addEventListener('click', () => utils.closeModal(editChannelModal));
    });

    const editForm = document.getElementById('editChannelForm');
    if (editForm) {
        editForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('editSubmitBtn');
            submitBtn.innerHTML = '<i data-lucide="loader" class="spin" size="18"></i> <span>UPDATING...</span>';
            lucide.createIcons();
            setTimeout(() => {
                utils.closeModal(editChannelModal);
                submitBtn.innerHTML = 'SAVE CHANGES';
                lucide.createIcons();
            }, 1200);
        });
    }

    // Member Selection Sync
    document.querySelectorAll('.cc-member-check, .edit-member-check').forEach(check => {
        check.addEventListener('change', function() {
            const modal = this.closest('.modal-overlay');
            if (modal.id === 'createChannelModal') {
                utils.updateSelectedCount(createChannelModal, 'selectedCount', '.cc-member-check');
            } else {
                utils.updateSelectedCount(editChannelModal, 'editSelectedCount', '.edit-member-check');
            }
        });
    });

    // --- Channel Detail Modal Logic ---
    const detailModal = document.getElementById('channelDetailModal');
    const tabButtons = detailModal.querySelectorAll('.modal-tab');
    const tabPanes = detailModal.querySelectorAll('.tab-pane');
    
    document.addEventListener('click', (e) => {
        const detailBtn = e.target.closest('.js-open-channel-detail');
        if (detailBtn) {
            const row = detailBtn.closest('.channel-row');
            const rowData = {
                name: row.querySelector('.member-name').textContent.trim(),
                topic: row.querySelector('td:nth-child(2)').textContent.trim(),
                privacy: row.querySelector('.role-badge').textContent.trim(),
                status: row.querySelector('.status-indicator span:last-child').textContent.trim(),
                memberCount: row.querySelector('td:nth-child(4)').textContent.trim()
            };
            openChannelDetailModal(rowData);
        }
    });

    function openChannelDetailModal(data) {
        document.getElementById('detailChannelName').textContent = data.name;
        document.getElementById('detailChannelTopic').textContent = data.topic;
        document.getElementById('detailPrivacy').textContent = data.privacy;
        document.getElementById('detailStatus').textContent = data.status;
        document.getElementById('detailMemberCount').textContent = data.memberCount;
        
        // Reset Tabs
        tabButtons.forEach(b => b.classList.toggle('active', b.dataset.tab === 'about'));
        tabPanes.forEach(p => p.style.display = p.id === 'tabAbout' ? 'block' : 'none');
        
        // Populate Members (Mock)
        populateDetailMembers(data.memberCount);

        detailModal.classList.add('active');
        lucide.createIcons();
    }

    function populateDetailMembers(count) {
        const listContainer = document.getElementById('detailMembersList');
        listContainer.innerHTML = '';
        const limit = Math.min(parseInt(count), 10); // Show max 10 mock members
        
        const mockMembers = [
            { name: 'Mahad Bukhari', role: 'Admin', avatarLabel: 'MB', color: 'var(--indigo-600)' },
            { name: 'Emma Williams', role: 'Designer', avatarLabel: 'EW', color: '#ec4899' },
            { name: 'Oliver Mitchell', role: 'Developer', avatarLabel: 'OM', color: '#8b5cf6' },
            { name: 'Sophia Reynolds', role: 'Manager', avatarLabel: 'SR', color: '#10b981' },
            { name: 'Liam Carter', role: 'Support', avatarLabel: 'LC', color: '#f59e0b' }
        ];

        for (let i = 0; i < limit; i++) {
            const member = mockMembers[i % mockMembers.length];
            const row = document.createElement('div');
            row.className = 'detail-member-row';
            row.innerHTML = `
                <div class="member-info-mini">
                    <div class="avatar-tiny" style="background: ${member.color}">${member.avatarLabel}</div>
                    <div class="member-text">
                        <span class="m-name">${member.name}</span>
                        <span class="m-role">${member.role}</span>
                    </div>
                </div>
                <button class="btn-text-icon"><i data-lucide="more-horizontal" size="14"></i></button>
            `;
            listContainer.appendChild(row);
        }
        lucide.createIcons();
    }

    // Modal Tab Switching
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const target = btn.dataset.tab;
            tabPanes.forEach(p => {
                p.style.display = p.id === `tab${target.charAt(0).toUpperCase() + target.slice(1)}` ? 'block' : 'none';
            });
        });
    });

    // Close Detail Modal
    detailModal.querySelectorAll('.js-close-channel-detail').forEach(btn => {
        btn.addEventListener('click', () => utils.closeModal(detailModal));
    });

    // Switch to Edit from Detail
    const switchToEditBtn = detailModal.querySelector('.js-switch-to-edit');
    if (switchToEditBtn) {
        switchToEditBtn.addEventListener('click', () => {
            const currentName = document.getElementById('detailChannelName').textContent;
            const currentPrivacy = document.getElementById('detailPrivacy').textContent;
            const currentStatus = document.getElementById('detailStatus').textContent;
            
            utils.closeModal(detailModal);
            setTimeout(() => {
                openEditChannelModal({
                    name: currentName,
                    privacy: currentPrivacy,
                    status: currentStatus
                });
            }, 300);
        });
    }

    // Modal Search (Mock filter)
    const memberSearchModal = document.getElementById('memberSearchModal');
    if (memberSearchModal) {
        memberSearchModal.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.detail-member-row').forEach(row => {
                const name = row.querySelector('.m-name').textContent.toLowerCase();
                row.style.display = name.includes(term) ? 'flex' : 'none';
            });
        });
    }

    console.log('Channels Pagination & Detail Modal initialized');
});
