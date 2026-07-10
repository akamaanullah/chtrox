document.addEventListener('DOMContentLoaded', function () {
    const utils = window.ChatroxUtils;
    const memberSearch = document.getElementById('memberSearch');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const membersTable = document.getElementById('membersTable');
    const memberRows = Array.from(membersTable.querySelectorAll('tbody tr'));
    
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
    let filteredRows = [...memberRows];

    function updatePagination() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        
        // Boundaries
        if (currentPage > totalPages) currentPage = totalPages || 1;
        if (currentPage < 1) currentPage = 1;

        // Visibility
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        memberRows.forEach(row => row.style.display = 'none');
        
        const currentSlice = filteredRows.slice(start, end);
        currentSlice.forEach(row => row.style.display = '');

        // Info text
        showingStart.textContent = totalRows === 0 ? 0 : start + 1;
        showingEnd.textContent = Math.min(end, totalRows);
        totalRowsSpan.textContent = totalRows;

        // No Results State
        if (totalRows === 0) {
            membersTable.style.display = 'none';
            noResults.style.display = 'flex';
        } else {
            membersTable.style.display = '';
            noResults.style.display = 'none';
        }

        // Render Page Numbers
        renderPageNumbers(totalPages);

        // Update Buttons
        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;

        // Refresh Lucide icons for dynamic content if any
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
                membersTable.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            pageNumbersContainer.appendChild(pageNum);
        }
    }

    function applyFilters() {
        const searchQuery = memberSearch.value.toLowerCase().trim();
        const roleQuery = roleFilter.value;
        const statusQuery = statusFilter.value;

        filteredRows = memberRows.filter(row => {
            const name = row.querySelector('.member-name').textContent.toLowerCase();
            const email = row.querySelector('.member-email').textContent.toLowerCase();
            const role = row.querySelector('.role-badge').textContent.trim();
            const status = row.querySelector('.status-indicator span:last-child').textContent.trim();

            const matchesSearch = name.includes(searchQuery) || email.includes(searchQuery);
            const matchesRole = !roleQuery || role === roleQuery;
            const matchesStatus = !statusQuery || status === statusQuery;

            return matchesSearch && matchesRole && matchesStatus;
        });

        currentPage = 1;
        updatePagination();
    }

    // Event Listeners
    memberSearch.addEventListener('input', applyFilters);
    roleFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    
    const resetBtn = document.querySelector('.reset-filters-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            memberSearch.value = '';
            roleFilter.value = '';
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
    const urlParams = new URLSearchParams(window.location.search);
    const statusParam = urlParams.get('status');
    if (statusParam) {
        if (statusParam.toLowerCase() === 'active' || statusParam.toLowerCase() === 'online') {
            statusFilter.value = 'Active';
        } else if (statusParam.toLowerCase() === 'offline') {
            statusFilter.value = 'Offline';
        }
        applyFilters();
    } else {
        updatePagination();
    }

    // Helper to send AJAX requests
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

    // Modal Interactivity
    const editModal = document.getElementById('editMemberModal');
    const addModal = document.getElementById('addMemberModal');
    const openAddBtn = document.querySelector('.invite-btn');

    function openEditModal(row) {
        const name = row.querySelector('.member-name').textContent;
        const email = row.querySelector('.member-email').textContent;
        const role = row.dataset.role;

        document.getElementById('editMemberId').value = row.dataset.id;
        document.getElementById('editName').value = name;
        document.getElementById('editEmail').value = email;
        document.getElementById('editRole').value = role;
        const editPassword = document.getElementById('editPassword');
        if (editPassword) editPassword.value = '';

        editModal.classList.add('active');
    }

    // Use event delegation for buttons since rows are effectively dynamic
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-member-btn');
        if (editBtn) {
            const row = editBtn.closest('.member-row');
            openEditModal(row);
            return;
        }

        const deleteBtn = e.target.closest('.member-row .delete');
        if (deleteBtn) {
            const row = deleteBtn.closest('.member-row');
            const memberId = row.dataset.id;
            const name = row.querySelector('.member-name').textContent;
            if (confirm(`Are you sure you want to remove ${name} from this workspace?`)) {
                adminAjax('/api/admin/members', 'DELETE', { id: memberId })
                    .then(res => {
                        window.location.reload();
                    })
                    .catch(err => {
                        alert(err.message);
                    });
            }
        }
    });

    const closeBtn = document.getElementById('closeModalBtn');
    if (closeBtn) closeBtn.addEventListener('click', () => utils.closeModal(editModal));
    
    const closeAddBtn = document.getElementById('closeAddModalBtn');
    if (closeAddBtn) closeAddBtn.addEventListener('click', () => utils.closeModal(addModal));

    if (openAddBtn) {
        openAddBtn.addEventListener('click', () => {
            const addForm = document.getElementById('addMemberForm');
            if (addForm) addForm.reset();
            addModal.classList.add('active');
        });
    }

    // Save Logic (Edit)
    const saveBtn = document.getElementById('saveEditBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const id = document.getElementById('editMemberId').value;
            const name = document.getElementById('editName').value;
            const email = document.getElementById('editEmail').value;
            const role = document.getElementById('editRole').value;
            const password = document.getElementById('editPassword').value;

            if (!name || !email) {
                alert('Name and Email are required.');
                return;
            }

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i data-lucide="loader" class="spin"></i> <span>Saving...</span>';
            if (window.lucide) window.lucide.createIcons();

            adminAjax('/api/admin/members', 'PATCH', { id, name, email, role, password })
                .then(res => {
                    utils.closeModal(editModal);
                    window.location.reload();
                })
                .catch(err => {
                    alert(err.message);
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i data-lucide="check"></i> <span>Save Changes</span>';
                    if (window.lucide) window.lucide.createIcons();
                });
        });
    }

    // Save Logic (Add)
    const saveAddBtn = document.getElementById('saveAddBtn');
    if (saveAddBtn) {
        saveAddBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const username = document.getElementById('addUsername').value.trim();
            const email = document.getElementById('addEmail').value.trim();
            const password = document.getElementById('addPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (!username || !email || !password || !confirmPassword) {
                alert('All fields are required.');
                return;
            }

            if (password !== confirmPassword) {
                alert('Passwords do not match.');
                return;
            }

            saveAddBtn.disabled = true;
            saveAddBtn.innerHTML = '<i data-lucide="loader" class="spin"></i> <span>Adding...</span>';
            if (window.lucide) window.lucide.createIcons();

            adminAjax('/api/admin/members', 'POST', { username, email, password, confirmPassword })
                .then(res => {
                    utils.closeModal(addModal);
                    window.location.reload();
                })
                .catch(err => {
                    alert(err.message);
                    saveAddBtn.disabled = false;
                    saveAddBtn.innerHTML = '<i data-lucide="plus"></i> <span>Add Member</span>';
                    if (window.lucide) window.lucide.createIcons();
                });
        });
    }

    // Generate Invite Link Modal Logic
    const inviteModal = document.getElementById('generateInviteModal');
    const openInviteBtn = document.querySelector('.generate-invite-btn');
    const closeInviteBtn = document.getElementById('closeInviteModalBtn');
    const submitInviteBtn = document.getElementById('btnSubmitGenerateInvite');
    const copyInviteBtn = document.getElementById('btnCopyInviteUrl');
    
    if (openInviteBtn && inviteModal) {
        openInviteBtn.addEventListener('click', () => {
            document.getElementById('generateInviteForm').reset();
            document.getElementById('inviteResultArea').style.display = 'none';
            inviteModal.classList.add('active');
        });
        
        // Close modal when clicking on backdrop overlay
        inviteModal.addEventListener('click', (e) => {
            if (e.target === inviteModal) {
                utils.closeModal(inviteModal);
            }
        });
    }

    if (closeInviteBtn && inviteModal) {
        closeInviteBtn.addEventListener('click', () => {
            utils.closeModal(inviteModal);
        });
    }

    if (submitInviteBtn) {
        submitInviteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const email = document.getElementById('inviteEmail').value.trim();
            const role = document.getElementById('inviteRole').value;

            submitInviteBtn.disabled = true;
            submitInviteBtn.innerHTML = '<i data-lucide="loader" class="spin"></i> <span>Generating...</span>';
            if (window.lucide) window.lucide.createIcons();

            adminAjax('/api/admin/members/generate-invite', 'POST', { email, role })
                .then(res => {
                    submitInviteBtn.disabled = false;
                    submitInviteBtn.innerHTML = '<i data-lucide="sparkles"></i> <span>Generate Invite Link</span>';
                    if (window.lucide) window.lucide.createIcons();

                    if (res.success) {
                        const inputUrl = document.getElementById('generatedInviteUrl');
                        inputUrl.value = res.join_url;
                        document.getElementById('inviteResultArea').style.display = 'flex';
                    }
                })
                .catch(err => {
                    alert(err.message || 'Failed to generate invite link');
                    submitInviteBtn.disabled = false;
                    submitInviteBtn.innerHTML = '<i data-lucide="sparkles"></i> <span>Generate Invite Link</span>';
                    if (window.lucide) window.lucide.createIcons();
                });
        });
    }

    if (copyInviteBtn) {
        copyInviteBtn.addEventListener('click', () => {
            const inputUrl = document.getElementById('generatedInviteUrl');
            inputUrl.select();
            inputUrl.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                navigator.clipboard.writeText(inputUrl.value)
                    .then(() => {
                        copyInviteBtn.innerHTML = '<i data-lucide="check"></i> <span>Copied!</span>';
                        if (window.lucide) window.lucide.createIcons();
                        setTimeout(() => {
                            copyInviteBtn.innerHTML = '<i data-lucide="copy" size="14"></i> <span>Copy</span>';
                            if (window.lucide) window.lucide.createIcons();
                        }, 2000);
                    });
            } catch (e) {
                // Fallback
                document.execCommand('copy');
                copyInviteBtn.innerHTML = '<i data-lucide="check"></i> <span>Copied!</span>';
                if (window.lucide) window.lucide.createIcons();
                setTimeout(() => {
                    copyInviteBtn.innerHTML = '<i data-lucide="copy" size="14"></i> <span>Copy</span>';
                    if (window.lucide) window.lucide.createIcons();
                }, 2000);
            }
        });
    }

    console.log('Manage Members with Pagination initialized');
});
