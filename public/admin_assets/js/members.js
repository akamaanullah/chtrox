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
    updatePagination();

    // Modal Interactivity (Retaining existing logic with delegation for paginated rows)
    const editModal = document.getElementById('editMemberModal');
    const addModal = document.getElementById('addMemberModal');
    const openAddBtn = document.querySelector('.invite-btn');

    function openEditModal(row) {
        const name = row.querySelector('.member-name').textContent;
        const email = row.querySelector('.member-email').textContent;
        const role = row.querySelector('.role-badge').textContent.trim();

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

    // Save Logic (Simulated)
    const saveBtn = document.getElementById('saveEditBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            saveBtn.innerHTML = '<i data-lucide="loader" class="spin"></i> <span>Saving...</span>';
            lucide.createIcons();
            setTimeout(() => {
                utils.closeModal(editModal);
                saveBtn.innerHTML = '<i data-lucide="check"></i> <span>Save Changes</span>';
                lucide.createIcons();
            }, 1000);
        });
    }

    console.log('Manage Members with Pagination initialized');
});
