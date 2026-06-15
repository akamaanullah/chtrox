document.addEventListener('DOMContentLoaded', function () {
    const activitySearch = document.getElementById('activitySearch');
    const rowsPerPageSelect = document.getElementById('rowsPerPage');
    const activityTable = document.getElementById('activityTable');
    const tableRows = Array.from(activityTable.querySelectorAll('tbody tr'));
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const pageNumbersContainer = document.getElementById('pageNumbers');
    const showingStart = document.getElementById('showingStart');
    const showingEnd = document.getElementById('showingEnd');
    const totalRowsSpan = document.getElementById('totalRows');
    const noResults = document.getElementById('noResults');

    let currentPage = 1;
    let rowsPerPage = parseInt(rowsPerPageSelect.value);
    let filteredRows = [...tableRows];

    function updatePagination() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        
        // Boundaries
        if (currentPage > totalPages) currentPage = totalPages || 1;
        if (currentPage < 1) currentPage = 1;

        // Visibility
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        tableRows.forEach(row => row.style.display = 'none');
        
        const currentSlice = filteredRows.slice(start, end);
        currentSlice.forEach(row => row.style.display = '');

        // Info text
        showingStart.textContent = totalRows === 0 ? 0 : start + 1;
        showingEnd.textContent = Math.min(end, totalRows);
        totalRowsSpan.textContent = totalRows;

        // No Results State
        if (totalRows === 0) {
            activityTable.closest('.activity-table-container').style.display = 'none';
            noResults.style.display = 'flex';
        } else {
            activityTable.closest('.activity-table-container').style.display = '';
            noResults.style.display = 'none';
        }

        // Render Page Numbers
        renderPageNumbers(totalPages);

        // Update Buttons
        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;

        // Create icons for dynamic elements
        if (window.lucide) window.lucide.createIcons();
    }

    function renderPageNumbers(totalPages) {
        pageNumbersContainer.innerHTML = '';
        
        // Simple pagination (1, 2, 3...)
        for (let i = 1; i <= totalPages; i++) {
            const pageNum = document.createElement('div');
            pageNum.className = `page-num ${i === currentPage ? 'active' : ''}`;
            pageNum.textContent = i;
            pageNum.addEventListener('click', () => {
                currentPage = i;
                updatePagination();
                activityTable.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            pageNumbersContainer.appendChild(pageNum);
        }
    }

    // Search Logic integration
    activitySearch.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        
        filteredRows = tableRows.filter(row => {
            const text = row.textContent.toLowerCase();
            return text.includes(query);
        });

        currentPage = 1;
        updatePagination();
    });

    // Rows Per Page toggle
    rowsPerPageSelect.addEventListener('change', function () {
        rowsPerPage = parseInt(this.value);
        currentPage = 1;
        updatePagination();
    });

    // Prev/Next
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
