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

    if (editForm) {
        editForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('editAnnSubmitBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>SAVING...</span> <i data-lucide="loader" class="spin"></i>';
            if (window.lucide) window.lucide.createIcons();
            
            setTimeout(() => {
                utils.closeModal(editAnnModal);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                if (window.lucide) window.lucide.createIcons();
                // In a real app, we'd update the row here
                utils.showToast("Announcement updated successfully!");
            }, 1200);
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
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>BROADCASTING...</span> <i data-lucide="loader" class="spin"></i>';
            if (window.lucide) window.lucide.createIcons();
            
            setTimeout(() => {
                utils.closeModal(addAnnModal);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                if (window.lucide) window.lucide.createIcons();
                // Reset form
                addForm.reset();
                updateEmojiPreview();
            }, 1500);
        });
    }

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
