document.addEventListener('DOMContentLoaded', function () {
    const utils = window.ChatroxUtils;
    const annSearch = document.getElementById('annSearch');
    const tagFilter = document.getElementById('tagFilter');
    const annTable = document.getElementById('announcementsTable');
    const annRows = Array.from(annTable.querySelectorAll('tbody .ann-row'));
    
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
    let filteredRows = [...annRows];

    // Emoji Mapping
    const emojiMap = {
        'IMPORTANT': '🚨',
        'CELEBRATION': '🎂',
        'UPDATE': '📢'
    };

    function updatePagination() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        
        if (currentPage > totalPages) currentPage = totalPages || 1;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        annRows.forEach(row => row.style.display = 'none');
        
        const currentSlice = filteredRows.slice(start, end);
        currentSlice.forEach(row => row.style.display = '');

        showingStart.textContent = totalRows === 0 ? 0 : start + 1;
        showingEnd.textContent = Math.min(end, totalRows);
        totalRowsSpan.textContent = totalRows;

        if (totalRows === 0) {
            annTable.style.display = 'none';
            noResults.style.display = 'flex';
        } else {
            annTable.style.display = '';
            noResults.style.display = 'none';
        }

        renderPageNumbers(totalPages);

        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;

        if (window.lucide) window.lucide.createIcons();
    }

    function renderPageNumbers(totalPages) {
        pageNumbersContainer.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const pageNum = document.createElement('div');
            pageNum.className = `page-num ${i === currentPage ? 'active' : ''}`;
            pageNum.textContent = i;
            pageNum.addEventListener('click', () => {
                currentPage = i;
                updatePagination();
                annTable.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            pageNumbersContainer.appendChild(pageNum);
        }
    }

    function applyFilters() {
        const searchQuery = annSearch.value.toLowerCase().trim();
        const tagQuery = tagFilter.value;

        filteredRows = annRows.filter(row => {
            const title = row.querySelector('.text-dark').textContent.toLowerCase();
            const tag = row.querySelector('.tag-pill').textContent.trim();
            const postedBy = row.querySelector('.member-name').textContent.toLowerCase();

            const matchesSearch = title.includes(searchQuery) || postedBy.includes(searchQuery);
            const matchesTag = !tagQuery || tag === tagQuery;

            return matchesSearch && matchesTag;
        });

        currentPage = 1;
        updatePagination();
    }

    // Modal Handling
    const addAnnModal = document.getElementById('addAnnouncementModal');
    const openAddBtn = document.querySelector('.add-announcement-btn');
    const addForm = document.getElementById('addAnnouncementForm');
    const tagSelect = document.getElementById('newAnnTag');
    const emojiPreview = document.getElementById('emojiPreview');

    if (openAddBtn) {
        openAddBtn.addEventListener('click', () => {
            if (addForm) addForm.reset();
            updateEmojiPreview();
            addAnnModal.classList.add('active');
            if (window.lucide) window.lucide.createIcons();
        });
    }

    if (addAnnModal) {
        addAnnModal.querySelectorAll('.js-close-ann-modal, .modal-close').forEach(btn => {
            btn.addEventListener('click', () => utils.closeModal(addAnnModal));
        });
    }

    // Edit Modal Elements
    const editAnnModal = document.getElementById('editAnnouncementModal');
    const editForm = document.getElementById('editAnnouncementForm');
    const editTagSelect = document.getElementById('editAnnTag');
    const editEmojiPreview = document.getElementById('editEmojiPreview');

    if (editAnnModal) {
        editAnnModal.querySelectorAll('.js-close-edit-modal, .modal-close').forEach(btn => {
            btn.addEventListener('click', () => utils.closeModal(editAnnModal));
        });
    }

    function updateEditEmojiPreview() {
        if (!editTagSelect || !editEmojiPreview) return;
        const tag = editTagSelect.value;
        editEmojiPreview.textContent = emojiMap[tag] || '📢';
    }

    if (editTagSelect) {
        editTagSelect.addEventListener('change', updateEditEmojiPreview);
    }

    // Delegate Edit Button Click
    annTable.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.js-open-edit-ann-modal');
        if (editBtn) {
            const row = editBtn.closest('.ann-row');
            if (!row) return;

            const data = {
                id: row.dataset.id,
                title: row.dataset.title,
                tag: row.dataset.tag,
                message: row.dataset.message,
                start: row.dataset.start,
                end: row.dataset.end
            };

            // Populate Form
            document.getElementById('editAnnId').value = data.id || '';
            document.getElementById('editAnnTitle').value = data.title || '';
            document.getElementById('editAnnTag').value = data.tag || 'UPDATE';
            document.getElementById('editAnnMessage').value = data.message || '';
            document.getElementById('editAnnStartDate').value = data.start || '';
            document.getElementById('editAnnEndDate').value = data.end || '';

            updateEditEmojiPreview();
            editAnnModal.classList.add('active');
            if (window.lucide) window.lucide.createIcons();
        }
    });

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

    if (editForm) {
        editForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('editAnnSubmitBtn');
            const originalText = submitBtn.innerHTML;
            
            const formData = new FormData(editForm);
            const data = {
                id: formData.get('id'),
                title: formData.get('title'),
                tag: formData.get('tag'),
                message: formData.get('message'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date')
            };

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>SAVING...</span> <i data-lucide="loader" class="spin"></i>';
            if (window.lucide) window.lucide.createIcons();
            
            adminAjax('/api/admin/announcements', 'PATCH', data)
                .then(res => {
                    utils.closeModal(editAnnModal);
                    window.location.reload();
                })
                .catch(err => {
                    alert(err.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    if (window.lucide) window.lucide.createIcons();
                });
        });
    }

    function updateEmojiPreview() {
        if (!tagSelect || !emojiPreview) return;
        const tag = tagSelect.value;
        emojiPreview.textContent = emojiMap[tag] || '📢';
    }

    if (tagSelect) {
        tagSelect.addEventListener('change', updateEmojiPreview);
    }

    if (addForm) {
        addForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('annSubmitBtn');
            const originalText = submitBtn.innerHTML;
            
            const formData = new FormData(addForm);
            const data = {
                title: formData.get('title'),
                tag: formData.get('tag'),
                message: formData.get('message'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date')
            };

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>BROADCASTING...</span> <i data-lucide="loader" class="spin"></i>';
            if (window.lucide) window.lucide.createIcons();
            
            adminAjax('/api/admin/announcements', 'POST', data)
                .then(res => {
                    if (res.ticket && res.recipient_ids && res.recipient_ids.length > 0) {
                        const wsPort = window.CHATROX_ADMIN.wsPort || 8080;
                        const hostname = window.location.hostname || '127.0.0.1';
                        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                        const wsUrl = `${protocol}//${hostname}:${wsPort}?token=${res.ticket}&workspace_id=${res.workspace_id}`;
                        
                        const ws = new WebSocket(wsUrl);
                        ws.onopen = function () {
                            const payload = {
                                action: 'notify_members',
                                member_ids: res.recipient_ids,
                                event: 'new_notification',
                                data: {
                                    type: 'announcement',
                                    title: 'New Announcement',
                                    body: res.announcement_title,
                                    reference_type: 'announcement',
                                    reference_id: res.announcement_id
                                }
                            };
                            ws.send(JSON.stringify(payload));
                            setTimeout(() => {
                                ws.close();
                                utils.closeModal(addAnnModal);
                                window.location.reload();
                            }, 500);
                        };
                        ws.onerror = function () {
                            utils.closeModal(addAnnModal);
                            window.location.reload();
                        };
                    } else {
                        utils.closeModal(addAnnModal);
                        window.location.reload();
                    }
                })
                .catch(err => {
                    alert(err.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    if (window.lucide) window.lucide.createIcons();
                });
        });
    }

    // View Modal Elements
    const viewAnnModal = document.getElementById('viewAnnouncementModal');
    if (viewAnnModal) {
        viewAnnModal.querySelectorAll('.js-close-view-modal, .modal-close').forEach(btn => {
            btn.addEventListener('click', () => utils.closeModal(viewAnnModal));
        });
    }

    // Delegate View Button Click
    annTable.addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.js-open-view-ann-modal');
        if (viewBtn) {
            const row = viewBtn.closest('.ann-row');
            if (!row) return;

            const data = {
                title: row.dataset.title,
                tag: row.dataset.tag,
                message: row.dataset.message,
                start: row.dataset.start,
                end: row.dataset.end,
                author: row.dataset.author,
                created: row.dataset.created
            };

            const formatDate = (str) => {
                if (!str) return '';
                const parts = str.split('-');
                if (parts.length === 3) {
                    return `${parts[1]}/${parts[2]}/${parts[0]}`;
                }
                return str;
            };

            document.getElementById('viewAnnTagPill').textContent = data.tag || 'UPDATE';
            document.getElementById('viewAnnTagPill').className = `tag-pill tag-${(data.tag || 'UPDATE').toLowerCase()}`;
            document.getElementById('viewAnnTitle').textContent = data.title || 'Announcement Details';
            document.getElementById('viewAnnEmoji').textContent = emojiMap[data.tag] || '📢';
            document.getElementById('viewAnnAuthor').textContent = data.author || 'ChatRox Admin';
            document.getElementById('viewAnnDate').textContent = data.created || '';
            document.getElementById('viewAnnMessage').textContent = data.message || '';
            document.getElementById('viewAnnStartDate').textContent = formatDate(data.start);
            document.getElementById('viewAnnEndDate').textContent = formatDate(data.end);

            viewAnnModal.classList.add('active');
            if (window.lucide) window.lucide.createIcons();
        }
    });

    // Delegate Delete Button Click
    annTable.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.ann-row .delete');
        if (deleteBtn) {
            const row = deleteBtn.closest('.ann-row');
            if (!row) return;
            const id = row.dataset.id;
            const title = row.dataset.title;
            if (confirm(`Are you sure you want to remove the announcement "${title}"?`)) {
                adminAjax('/api/admin/announcements', 'DELETE', { id })
                    .then(res => {
                        window.location.reload();
                    })
                    .catch(err => {
                        alert(err.message);
                    });
            }
        }
    });

    // Date inputs usually don't need much logic unless we're validating ranges
    // But we'll keep the script clean by removing unused image upload logic

    // Listeners
    annSearch.addEventListener('input', applyFilters);
    tagFilter.addEventListener('change', applyFilters);
    
    const resetBtn = document.querySelector('.reset-filters-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            annSearch.value = '';
            tagFilter.value = '';
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
});
