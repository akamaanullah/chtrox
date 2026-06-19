document.addEventListener('DOMContentLoaded', function () {
    const filesTable = document.getElementById('filesTable');
    if (!filesTable) return;

    const filesSearch = document.getElementById('filesSearch');
    const filesFilter = document.getElementById('filesFilter');
    const rowsPerPageSelect = document.getElementById('filesPerPage');
    const prevPageBtn = document.getElementById('filesPrevPage');
    const nextPageBtn = document.getElementById('filesNextPage');
    const pageNumbersContainer = document.getElementById('filesPageNumbers');
    const showingStart = document.getElementById('filesShowingStart');
    const showingEnd = document.getElementById('filesShowingEnd');
    const totalRowsSpan = document.getElementById('filesTotalRows');

    const fileRows = Array.from(filesTable.querySelectorAll('tbody tr.file-row'));

    let currentPage = 1;
    let rowsPerPage = parseInt(rowsPerPageSelect.value, 10);
    let filteredRows = [...fileRows];

    function updatePagination() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        fileRows.forEach(row => {
            row.style.display = 'none';
        });

        filteredRows.slice(start, end).forEach(row => {
            row.style.display = '';
        });

        showingStart.textContent = totalRows === 0 ? 0 : start + 1;
        showingEnd.textContent = Math.min(end, totalRows);
        totalRowsSpan.textContent = totalRows;

        renderPageNumbers(totalPages);

        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages || totalRows === 0;

        if (window.lucide) window.lucide.createIcons();
    }

    function renderPageNumbers(totalPages) {
        pageNumbersContainer.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            const pageNum = document.createElement('button');
            pageNum.type = 'button';
            pageNum.className = `page-num${i === currentPage ? ' active' : ''}`;
            pageNum.textContent = i;
            pageNum.setAttribute('aria-label', `Page ${i}`);
            pageNum.addEventListener('click', () => {
                currentPage = i;
                updatePagination();
            });
            pageNumbersContainer.appendChild(pageNum);
        }
    }

    function applyFilters() {
        const searchQuery = filesSearch.value.toLowerCase().trim();
        const filterValue = filesFilter.value;

        filteredRows = fileRows.filter(row => {
            const fileName = row.querySelector('.file-name-text')?.textContent.toLowerCase() || '';
            const sharedBy = row.querySelector('.shared-by-text')?.textContent.toLowerCase() || '';
            const isSharedByYou = row.dataset.sharedByYou === '1';

            const matchesSearch = !searchQuery
                || fileName.includes(searchQuery)
                || sharedBy.includes(searchQuery);

            let matchesFilter = true;
            if (filterValue === 'shared_by_me') {
                matchesFilter = isSharedByYou;
            } else if (filterValue === 'shared_by_others') {
                matchesFilter = !isSharedByYou;
            }

            return matchesSearch && matchesFilter;
        });

        currentPage = 1;
        updatePagination();
    }

    filesSearch.addEventListener('input', applyFilters);
    filesFilter.addEventListener('change', applyFilters);

    rowsPerPageSelect.addEventListener('change', function () {
        rowsPerPage = parseInt(this.value, 10);
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

    updatePagination();
});
