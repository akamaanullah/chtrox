(function () {
    var sessionAbort = null;

    function initFilesTab() {
        if (sessionAbort) {
            sessionAbort.abort();
        }
        sessionAbort = new AbortController();
        var signal = sessionAbort.signal;

        var filesTable = document.getElementById('filesTable');
        if (!filesTable) return;

        var filesSearch = document.getElementById('filesSearch');
        var filesFilter = document.getElementById('filesFilter');
        var rowsPerPageSelect = document.getElementById('filesPerPage');
        var prevPageBtn = document.getElementById('filesPrevPage');
        var nextPageBtn = document.getElementById('filesNextPage');
        var pageNumbersContainer = document.getElementById('filesPageNumbers');
        var showingStart = document.getElementById('filesShowingStart');
        var showingEnd = document.getElementById('filesShowingEnd');
        var totalRowsSpan = document.getElementById('filesTotalRows');

        var fileRows = Array.from(filesTable.querySelectorAll('tbody tr.file-row'));

        var currentPage = 1;
        var rowsPerPage = parseInt(rowsPerPageSelect.value, 10);
        var filteredRows = fileRows.slice();

        function updatePagination() {
            var totalRows = filteredRows.length;
            var totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            var start = (currentPage - 1) * rowsPerPage;
            var end = start + rowsPerPage;

            fileRows.forEach(function (row) {
                row.style.display = 'none';
            });

            filteredRows.slice(start, end).forEach(function (row) {
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

            for (var i = 1; i <= totalPages; i++) {
                (function (pageIndex) {
                    var pageNum = document.createElement('button');
                    pageNum.type = 'button';
                    pageNum.className = 'page-num' + (pageIndex === currentPage ? ' active' : '');
                    pageNum.textContent = pageIndex;
                    pageNum.setAttribute('aria-label', 'Page ' + pageIndex);
                    pageNum.addEventListener('click', function () {
                        currentPage = pageIndex;
                        updatePagination();
                    }, { signal: signal });
                    pageNumbersContainer.appendChild(pageNum);
                })(i);
            }
        }

        function applyFilters() {
            var searchQuery = filesSearch.value.toLowerCase().trim();
            var filterValue = filesFilter.value;

            filteredRows = fileRows.filter(function (row) {
                var fileNameEl = row.querySelector('.file-name-text');
                var sharedByEl = row.querySelector('.shared-by-text');
                var fileName = fileNameEl ? fileNameEl.textContent.toLowerCase() : '';
                var sharedBy = sharedByEl ? sharedByEl.textContent.toLowerCase() : '';
                var isSharedByYou = row.dataset.sharedByYou === '1';

                var matchesSearch = !searchQuery
                    || fileName.indexOf(searchQuery) !== -1
                    || sharedBy.indexOf(searchQuery) !== -1;

                var matchesFilter = true;
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

        filesSearch.addEventListener('input', applyFilters, { signal: signal });
        filesFilter.addEventListener('change', applyFilters, { signal: signal });

        rowsPerPageSelect.addEventListener('change', function () {
            rowsPerPage = parseInt(this.value, 10);
            currentPage = 1;
            updatePagination();
        }, { signal: signal });

        prevPageBtn.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                updatePagination();
            }
        }, { signal: signal });

        nextPageBtn.addEventListener('click', function () {
            var totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                updatePagination();
            }
        }, { signal: signal });

        updatePagination();
    }

    document.addEventListener('chatrox:page_load', initFilesTab);
})();
