(function () {
    var sessionAbort = null;

    function initFilesTab() {
        if (sessionAbort) {
            sessionAbort.abort();
        }
        sessionAbort = new AbortController();
        var signal = sessionAbort.signal;

        var filesCardsGrid = document.getElementById('filesCardsGrid');
        if (!filesCardsGrid) return;

        var filesSearch = document.getElementById('filesSearch');
        var filesFilter = document.getElementById('filesFilter');
        var rowsPerPageSelect = document.getElementById('filesPerPage');
        var prevPageBtn = document.getElementById('filesPrevPage');
        var nextPageBtn = document.getElementById('filesNextPage');
        var pageNumbersContainer = document.getElementById('filesPageNumbers');
        var showingStart = document.getElementById('filesShowingStart');
        var showingEnd = document.getElementById('filesShowingEnd');
        var totalRowsSpan = document.getElementById('filesTotalRows');

        var currentPage = 1;
        var rowsPerPage = parseInt(rowsPerPageSelect.value, 10);
        var currentFilesList = []; // Holds items of the current page for lightbox carousel

        function fetchFiles() {
            var searchVal = filesSearch.value.trim();
            var filterVal = filesFilter.value;

            // Show loading placeholder with spinner
            filesCardsGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--text-muted, #64748b); font-weight: 500;"><span style="display: inline-flex; align-items: center; gap: 8px;"><svg class="animate-spin" style="animation: spin 1s linear infinite; width: 24px; height: 24px; color: var(--indigo-600, #4f46e5);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="8"></circle></svg> Loading shared files...</span></div>';
            if (window.lucide) window.lucide.createIcons();

            var url = window.CHATROX.apiUrl + '/files?page=' + currentPage + '&per_page=' + rowsPerPage + '&search=' + encodeURIComponent(searchVal) + '&filter=' + encodeURIComponent(filterVal);

            fetch(url, { signal: signal })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success) {
                        throw new Error(data.error || 'Failed to fetch files');
                    }
                    currentFilesList = data.files || [];
                    renderFiles(currentFilesList);
                    updatePaginationControls(data.total_rows, data.total_pages);
                })
                .catch(function (err) {
                    if (err.name === 'AbortError') return;
                    console.error('Error fetching files:', err);
                    filesCardsGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: #ef4444;">Failed to load files. Please try again.</div>';
                });
        }

        function renderFiles(files) {
            filesCardsGrid.innerHTML = '';
            if (files.length === 0) {
                filesCardsGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--text-muted, #64748b); font-weight: 500;">No files found matching your search.</div>';
                return;
            }

            files.forEach(function (file, index) {
                var card = document.createElement('div');
                card.className = 'file-card';
                card.setAttribute('data-shared-by-you', file.shared_by_you ? '1' : '0');

                var isImage = file.icon === 'file-image';
                var downloadUrl = window.CHATROX.baseUrl + '/files/download/' + file.id;

                var previewHtml = '';
                if (isImage) {
                    previewHtml = '<img src="' + downloadUrl + '" alt="' + escapeHtml(file.name) + '" class="file-card-img js-file-preview-trigger" data-index="' + index + '">';
                } else {
                    previewHtml = '<div class="file-card-icon-placeholder ' + file.icon_class + ' js-file-preview-trigger" data-index="' + index + '"><i data-lucide="' + file.icon + '" size="36"></i></div>';
                }

                var sharedByLabel = file.shared_by_you 
                    ? '<span style="color: var(--indigo-600); font-weight: 800;">Shared by You</span>'
                    : escapeHtml(file.shared_by);

                card.innerHTML = [
                    '<div class="file-card-preview">',
                    '    ' + previewHtml,
                    '    <div class="file-card-actions">',
                    '        <a href="' + downloadUrl + '" class="file-action-btn" download title="Download File">',
                    '            <i data-lucide="download" size="13"></i>',
                    '        </a>',
                    '    </div>',
                    '</div>',
                    '<div class="file-card-info">',
                    '    <div class="file-card-name" title="' + escapeHtml(file.name) + '">' + escapeHtml(file.name) + '</div>',
                    '    <div class="file-card-meta">',
                    '        <span class="file-card-size">' + file.size + '</span>',
                    '        <span class="file-card-dot">&bull;</span>',
                    '        <span class="file-card-date">' + file.date + '</span>',
                    '    </div>',
                    '    <div class="file-card-user">',
                    '        <img src="' + file.shared_avatar + '" alt="' + escapeHtml(file.shared_by) + '" class="file-card-avatar">',
                    '        <span class="file-card-username">' + sharedByLabel + '</span>',
                    '    </div>',
                    '</div>'
                ].join('\n');

                filesCardsGrid.appendChild(card);
            });

            if (window.lucide) window.lucide.createIcons();
            initCardsLightbox();
        }

        function initCardsLightbox() {
            var lightbox = document.getElementById('filesLightbox');
            var lightboxImg = document.getElementById('filesLightboxImg');
            var lightboxDoc = document.getElementById('filesLightboxDoc');
            var lightboxDocName = document.getElementById('filesLightboxDocName');
            var lightboxDocMeta = document.getElementById('filesLightboxDocMeta');
            var lightboxDocDlBtn = document.getElementById('filesLightboxDocDlBtn');
            var lightboxDocIconBox = document.getElementById('filesLightboxDocIconBox');
            
            var closeBtn = document.getElementById('filesLightboxClose');
            var prevBtn = document.getElementById('filesLightboxPrev');
            var nextBtn = document.getElementById('filesLightboxNext');
            
            if (!lightbox) return;

            var activeIndex = -1;

            function updateLightboxContent(index) {
                if (index < 0 || index >= currentFilesList.length) return;
                activeIndex = index;

                var file = currentFilesList[activeIndex];
                var isImage = file.icon === 'file-image';
                var downloadUrl = window.CHATROX.baseUrl + '/files/download/' + file.id;

                if (isImage) {
                    if (lightboxDoc) lightboxDoc.setAttribute('hidden', '');
                    if (lightboxImg) {
                        lightboxImg.src = downloadUrl;
                        lightboxImg.removeAttribute('hidden');
                    }
                } else {
                    if (lightboxImg) lightboxImg.setAttribute('hidden', '');
                    if (lightboxDoc) {
                        if (lightboxDocName) lightboxDocName.textContent = file.name;
                        if (lightboxDocMeta) lightboxDocMeta.innerHTML = escapeHtml(file.size) + ' &bull; Shared ' + escapeHtml(file.date);
                        if (lightboxDocDlBtn) lightboxDocDlBtn.href = downloadUrl;
                        
                        if (lightboxDocIconBox) {
                            var colorClass = file.icon_class || 'bg-gray';
                            lightboxDocIconBox.className = 'files-lightbox-doc-icon-box ' + colorClass;
                            lightboxDocIconBox.style.color = '#ffffff';
                            lightboxDocIconBox.innerHTML = '<i data-lucide="' + file.icon + '" size="48"></i>';
                        }
                        
                        lightboxDoc.removeAttribute('hidden');
                    }
                }

                if (prevBtn) prevBtn.disabled = activeIndex === 0;
                if (nextBtn) nextBtn.disabled = activeIndex === currentFilesList.length - 1;

                if (window.lucide) window.lucide.createIcons();
            }

            document.querySelectorAll('.js-file-preview-trigger').forEach(function (trigger) {
                trigger.addEventListener('click', function (e) {
                    e.preventDefault();
                    var idx = parseInt(this.getAttribute('data-index'), 10);
                    updateLightboxContent(idx);
                    lightbox.removeAttribute('hidden');
                    setTimeout(function () {
                        lightbox.classList.add('active');
                    }, 10);
                }, { signal: signal });
            });

            function closeLightbox() {
                lightbox.classList.remove('active');
                setTimeout(function () {
                    lightbox.setAttribute('hidden', '');
                    if (lightboxImg) lightboxImg.src = '';
                }, 300);
            }

            if (closeBtn) closeBtn.addEventListener('click', closeLightbox, { signal: signal });
            
            var overlay = lightbox.querySelector('.files-lightbox-overlay');
            if (overlay) overlay.addEventListener('click', closeLightbox, { signal: signal });

            if (prevBtn) {
                prevBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (activeIndex > 0) updateLightboxContent(activeIndex - 1);
                }, { signal: signal });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (activeIndex < currentFilesList.length - 1) updateLightboxContent(activeIndex + 1);
                }, { signal: signal });
            }

            var escHandler = function (e) {
                if (e.key === 'Escape') {
                    closeLightbox();
                } else if (e.key === 'ArrowLeft') {
                    if (activeIndex > 0) updateLightboxContent(activeIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    if (activeIndex < currentFilesList.length - 1) updateLightboxContent(activeIndex + 1);
                }
            };
            document.addEventListener('keydown', escHandler, { signal: signal });
        }

        function updatePaginationControls(totalRows, totalPages) {
            var start = totalRows === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
            var end = Math.min(currentPage * rowsPerPage, totalRows);

            showingStart.textContent = start;
            showingEnd.textContent = end;
            totalRowsSpan.textContent = totalRows;

            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage === totalPages || totalRows === 0;

            renderPageNumbers(totalPages);
        }

        function renderPageNumbers(totalPages) {
            pageNumbersContainer.innerHTML = '';

            var range = [];
            var l;

            for (var i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    range.push(i);
                }
            }

            var rangeWithDots = [];
            for (var i = 0; i < range.length; i++) {
                if (l) {
                    if (range[i] - l === 2) {
                        rangeWithDots.push(l + 1);
                    } else if (range[i] - l > 2) {
                        rangeWithDots.push('...');
                    }
                }
                rangeWithDots.push(range[i]);
                l = range[i];
            }

            rangeWithDots.forEach(function (page) {
                if (page === '...') {
                    var dots = document.createElement('span');
                    dots.className = 'pagination-dots';
                    dots.style.padding = '4px 8px';
                    dots.style.color = '#94a3b8';
                    dots.style.fontWeight = '600';
                    dots.textContent = '...';
                    pageNumbersContainer.appendChild(dots);
                } else {
                    (function (pageIndex) {
                        var pageNum = document.createElement('button');
                        pageNum.type = 'button';
                        pageNum.className = 'page-num' + (pageIndex === currentPage ? ' active' : '');
                        pageNum.textContent = pageIndex;
                        pageNum.setAttribute('aria-label', 'Page ' + pageIndex);
                        pageNum.addEventListener('click', function () {
                            currentPage = pageIndex;
                            fetchFiles();
                        }, { signal: signal });
                        pageNumbersContainer.appendChild(pageNum);
                    })(page);
                }
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Debounce search input
        var searchTimeout = null;
        filesSearch.addEventListener('input', function () {
            if (searchTimeout) clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                currentPage = 1;
                fetchFiles();
            }, 300);
        }, { signal: signal });

        filesFilter.addEventListener('change', function () {
            currentPage = 1;
            fetchFiles();
        }, { signal: signal });

        rowsPerPageSelect.addEventListener('change', function () {
            rowsPerPage = parseInt(this.value, 10);
            currentPage = 1;
            fetchFiles();
        }, { signal: signal });

        prevPageBtn.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                fetchFiles();
            }
        }, { signal: signal });

        nextPageBtn.addEventListener('click', function () {
            currentPage++;
            fetchFiles();
        }, { signal: signal });

        // Initial fetch
        fetchFiles();
    }

    document.addEventListener('chatrox:page_load', initFilesTab);
})();
