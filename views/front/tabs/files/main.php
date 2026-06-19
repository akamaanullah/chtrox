<div class="content-inner">
    <div class="files-page-header">
        <div class="aph-left">
            <div class="aph-icon-box" style="background: var(--indigo-50); color: var(--indigo-600);">
                <i data-lucide="file" size="20"></i>
            </div>
            <div class="aph-titles">
                <h3>Shared Workspace Files</h3>
                <span class="label-tiny">ALL FILES SHARED WITH YOU</span>
            </div>
        </div>
        <div class="aph-right" style="display: flex; gap: 12px; align-items: center;">
            <div class="search-box" style="width: 350px;">
                <i data-lucide="search" size="18"></i>
                <input type="text" id="filesSearch" placeholder="Search shared files...">
            </div>
            <select id="filesFilter" class="files-filter-select" aria-label="Filter files">
                <option value="all">All</option>
                <option value="shared_by_me">Shared by me</option>
                <option value="shared_by_others">Shared by others</option>
            </select>
        </div>
    </div>

    <div class="files-grid-area">
        <div class="files-table-container">
            <div class="files-table-scroll">
                <table id="filesTable" class="files-table">
                    <thead>
                        <tr>
                            <th>NAME</th>
                            <th>SHARED BY</th>
                            <th>DATE</th>
                            <th>SIZE</th>
                            <th>DOWNLOAD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workspace_files as $file): ?>
                            <tr class="file-row"
                                data-shared-by-you="<?php echo $file['shared_by_you'] ? '1' : '0'; ?>">
                                <td>
                                    <div class="file-name-cell">
                                        <div class="file-icon-box <?php echo htmlspecialchars($file['icon_class']); ?>">
                                            <i data-lucide="<?php echo htmlspecialchars($file['icon']); ?>" size="16"></i>
                                        </div>
                                        <span class="file-name-text"><?php echo htmlspecialchars($file['name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="shared-by-cell">
                                        <img src="<?php echo htmlspecialchars($file['shared_avatar']); ?>"
                                            alt="<?php echo htmlspecialchars($file['shared_by']); ?>">
                                        <?php if ($file['shared_by_you']): ?>
                                            <span class="shared-by-text" style="color: var(--indigo-600); font-weight: 800;">Shared by You</span>
                                        <?php else: ?>
                                            <span class="shared-by-text"><?php echo htmlspecialchars($file['shared_by']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($file['date']); ?></td>
                                <td><?php echo htmlspecialchars($file['size']); ?></td>
                                <td class="files-download-cell">
                                    <a href="<?php echo BASE_URL; ?>/files/download/<?php echo $file['id']; ?>" class="action-icon" title="Download file" aria-label="Download file">
                                        <i data-lucide="download"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="files-footer">
                <div class="per-page-selector">
                    <span>Rows per page:</span>
                    <select id="filesPerPage" class="files-per-page-select">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                    </select>
                </div>

                <div class="pagination-info">
                    <span id="filesShowingStart">1</span>-<span id="filesShowingEnd">10</span> of <span id="filesTotalRows"><?php echo count($workspace_files); ?></span>
                </div>

                <div class="pagination-controls">
                    <button type="button" class="pag-btn" id="filesPrevPage" title="Previous Page" aria-label="Previous page">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <div class="page-numbers" id="filesPageNumbers"></div>
                    <button type="button" class="pag-btn" id="filesNextPage" title="Next Page" aria-label="Next page">
                        <i data-lucide="chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
