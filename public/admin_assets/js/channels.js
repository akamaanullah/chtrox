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

    // --- AJAX Utility ---
    function adminAjax(url, method, data) {
        const headers = {
            'Content-Type': 'application/json',
        };
        if (window.CHATROX_ADMIN && window.CHATROX_ADMIN.csrfToken) {
            headers['X-CSRF-Token'] = window.CHATROX_ADMIN.csrfToken;
        }
        return fetch(window.CHATROX_ADMIN.baseUrl + url, {
            method: method,
            headers: headers,
            body: JSON.stringify(data)
        }).then(res => {
            return res.json().then(json => {
                if (!res.ok) {
                    throw new Error(json.error || 'Server error');
                }
                return json;
            });
        });
    }

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
            if (window.lucide) lucide.createIcons();
            utils.updateSelectedCount(createChannelModal, 'selectedCount', '.cc-member-check');
            if (ccSubmitBtn) {
                ccSubmitBtn.disabled = false;
                ccSubmitBtn.innerHTML = 'FINALIZE & CREATE CHANNEL';
            }
        });
    }

    createChannelModal.querySelectorAll('.js-close-create-channel-modal').forEach(btn => {
        btn.addEventListener('click', () => utils.closeModal(createChannelModal));
    });

    if (createForm) {
        createForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const channel_name = document.getElementById('ccChannelName').value.trim();
            const description = ''; 
            const visibility = createChannelModal.querySelector('input[name="visibility"]:checked').value;
            const add_all_members = document.getElementById('addAllMembers').checked;
            
            const members = [];
            createChannelModal.querySelectorAll('.cc-member-check:checked').forEach(cb => {
                members.push(cb.value);
            });

            if (!channel_name) {
                alert('Channel name is required.');
                return;
            }

            ccSubmitBtn.disabled = true;
            ccSubmitBtn.innerHTML = '<i data-lucide="loader" class="spin" size="18"></i> <span>CREATING...</span>';
            if (window.lucide) window.lucide.createIcons();

            adminAjax('/api/admin/channels', 'POST', { channel_name, description, visibility, add_all_members, members })
                .then(res => {
                    utils.closeModal(createChannelModal);
                    window.location.reload();
                })
                .catch(err => {
                    alert(err.message);
                    ccSubmitBtn.disabled = false;
                    ccSubmitBtn.innerHTML = 'FINALIZE & CREATE CHANNEL';
                    if (window.lucide) window.lucide.createIcons();
                });
        });
    }

    // Edit Channel (Using Delegation for Paginated Rows)
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.js-open-edit-channel-modal');
        if (editBtn) {
            const row = editBtn.closest('.channel-row');
            openEditChannelModal(row);
        }
    });

    function openEditChannelModal(row) {
        const id = row.dataset.id;
        const name = row.dataset.name;
        const topic = row.dataset.topic;
        const visibility = row.dataset.visibility;
        const memberIds = (row.dataset.members || '').split(',').map(Number);

        document.getElementById('editChannelId')?.remove(); 
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.id = 'editChannelId';
        idInput.value = id;
        editForm.appendChild(idInput);

        document.getElementById('editChannelName').value = name;
        document.getElementById('editOriginalName').value = name;
        
        if (visibility === 'public') {
            document.getElementById('editVisPublic').checked = true;
        } else {
            document.getElementById('editVisPrivate').checked = true;
        }
        
        editChannelModal.querySelectorAll('.edit-member-check').forEach(cb => {
            cb.checked = memberIds.includes(Number(cb.value));
        });

        editChannelModal.classList.add('active');
        if (window.lucide) window.lucide.createIcons();
        utils.updateSelectedCount(editChannelModal, 'editSelectedCount', '.edit-member-check');
        
        const submitBtn = document.getElementById('editSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'SAVE CHANGES';
        }
    }

    editChannelModal.querySelectorAll('.js-close-edit-channel-modal').forEach(btn => {
        btn.addEventListener('click', () => utils.closeModal(editChannelModal));
    });

    const editForm = document.getElementById('editChannelForm');
    if (editForm) {
        editForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = document.getElementById('editChannelId').value;
            const channel_name = document.getElementById('editChannelName').value.trim();
            const description = ''; 
            const visibility = editChannelModal.querySelector('input[name="edit_visibility"]:checked').value;
            const add_all_members = document.getElementById('editAddAllMembers').checked;
            
            const members = [];
            editChannelModal.querySelectorAll('.edit-member-check:checked').forEach(cb => {
                members.push(cb.value);
            });

            if (!channel_name) {
                alert('Channel name is required.');
                return;
            }

            const submitBtn = document.getElementById('editSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader" class="spin" size="18"></i> <span>UPDATING...</span>';
            if (window.lucide) window.lucide.createIcons();

            adminAjax('/api/admin/channels', 'PATCH', { id, channel_name, description, visibility, add_all_members, members })
                .then(res => {
                    utils.closeModal(editChannelModal);
                    window.location.reload();
                })
                .catch(err => {
                    alert(err.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'SAVE CHANGES';
                    if (window.lucide) window.lucide.createIcons();
                });
        });
    }

    // Delete/Archive Channel
    document.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.channel-row .delete');
        if (deleteBtn) {
            const row = deleteBtn.closest('.channel-row');
            const id = row.dataset.id;
            const name = row.dataset.name;
            if (confirm(`Are you sure you want to archive #${name}?`)) {
                adminAjax('/api/admin/channels', 'DELETE', { id })
                    .then(res => {
                        window.location.reload();
                    })
                    .catch(err => {
                        alert(err.message);
                    });
            }
        }
    });

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
                id: row.dataset.id,
                name: row.dataset.name,
                topic: row.dataset.topic,
                privacy: row.querySelector('.role-badge').textContent.trim(),
                status: row.querySelector('.status-indicator span:last-child').textContent.trim(),
                memberCount: row.querySelector('td:nth-child(4)').textContent.trim()
            };
            openChannelDetailModal(rowData, row);
        }
    });

    function openChannelDetailModal(data, row) {
        document.getElementById('detailChannelName').textContent = '#' + data.name;
        document.getElementById('detailChannelTopic').textContent = data.topic;
        document.getElementById('detailPrivacy').textContent = data.privacy;
        document.getElementById('detailStatus').textContent = data.status;
        document.getElementById('detailMemberCount').textContent = data.memberCount;
        
        tabButtons.forEach(b => b.classList.toggle('active', b.dataset.tab === 'about'));
        tabPanes.forEach(p => p.style.display = p.id === 'tabAbout' ? 'block' : 'none');
        
        populateDetailMembers(data.id);

        detailModal.classList.add('active');
        if (window.lucide) window.lucide.createIcons();
        
        detailModal.dataset.activeRowId = data.id;
    }

    function populateDetailMembers(channelId) {
        const listContainer = document.getElementById('detailMembersList');
        listContainer.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--slate-400);"><i data-lucide="loader" class="spin"></i> Loading...</div>';
        if (window.lucide) window.lucide.createIcons();

        fetch(window.CHATROX_ADMIN.baseUrl + '/api/admin/channels/members?id=' + channelId)
            .then(res => res.json())
            .then(res => {
                listContainer.innerHTML = '';
                if (res.success && res.members) {
                    res.members.forEach(member => {
                        const row = document.createElement('div');
                        row.className = 'detail-member-row';
                        row.innerHTML = `
                            <div class="member-info-mini">
                                <img src="${member.avatar}" alt="" class="avatar-tiny" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                                <div class="member-text">
                                    <span class="m-name">${member.name}</span>
                                    <span class="m-role">${member.role}</span>
                                </div>
                            </div>
                        `;
                        listContainer.appendChild(row);
                    });
                } else {
                    listContainer.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--slate-400);">No members found</div>';
                }
                if (window.lucide) window.lucide.createIcons();
            })
            .catch(err => {
                listContainer.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--red-500);">Error loading members</div>';
                if (window.lucide) window.lucide.createIcons();
            });
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
