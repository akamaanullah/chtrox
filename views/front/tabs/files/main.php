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
            <div class="files-cards-grid" id="filesCardsGrid">
                <!-- Loaded dynamically via AJAX -->
            </div>

            <div class="files-footer">
                <div class="per-page-selector">
                    <span>Rows per page:</span>
                    <select id="filesPerPage" class="files-per-page-select">
                        <option value="8">8</option>
                        <option value="16" selected>16</option>
                        <option value="32">32</option>
                    </select>
                </div>

                <div class="pagination-info">
                    <span id="filesShowingStart">0</span>-<span id="filesShowingEnd">0</span> of <span id="filesTotalRows">0</span>
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

<!-- Gallery Lightbox Modal -->
<div class="files-lightbox" id="filesLightbox" hidden>
    <div class="files-lightbox-overlay"></div>
    <div class="files-lightbox-content">
        <button type="button" class="files-lightbox-close" id="filesLightboxClose" aria-label="Close lightbox">&times;</button>
        
        <!-- Navigation arrows -->
        <button type="button" class="files-lightbox-nav files-lightbox-prev" id="filesLightboxPrev" aria-label="Previous file">
            <i data-lucide="chevron-left" size="28"></i>
        </button>
        
        <!-- View containers -->
        <div class="files-lightbox-body">
            <!-- Image View -->
            <img src="" alt="Lightbox preview" class="files-lightbox-img" id="filesLightboxImg" hidden>
            
            <!-- Fallback Document View -->
            <div class="files-lightbox-doc" id="filesLightboxDoc" hidden>
                <div class="files-lightbox-doc-icon-box" id="filesLightboxDocIconBox">
                    <i data-lucide="file-text" size="72"></i>
                </div>
                <h3 class="files-lightbox-doc-name" id="filesLightboxDocName">Document.pdf</h3>
                <span class="files-lightbox-doc-meta" id="filesLightboxDocMeta">PDF &bull; 1.2 MB</span>
                <a href="" class="files-lightbox-doc-dl-btn" id="filesLightboxDocDlBtn" download>
                    <i data-lucide="download" size="18"></i> Download File
                </a>
            </div>
        </div>

        <button type="button" class="files-lightbox-nav files-lightbox-next" id="filesLightboxNext" aria-label="Next file">
            <i data-lucide="chevron-right" size="28"></i>
        </button>
    </div>
</div>

<style>
.files-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
    padding: 24px;
    background: var(--bg-surface, #ffffff);
    min-height: 350px;
}

/* Premium Card Design */
.file-card {
    background: #ffffff;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    height: fit-content;
}

.file-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.12), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    border-color: var(--indigo-300, #a5b4fc);
}

.file-card-preview {
    height: 150px;
    background: #f8fafc;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    cursor: pointer;
}

.file-card-preview::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.03);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.file-card:hover .file-card-preview::after {
    opacity: 1;
}

.file-card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.file-card:hover .file-card-img {
    transform: scale(1.06);
}

.file-card-icon-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    transition: transform 0.4s ease;
}

.file-card:hover .file-card-icon-placeholder {
    transform: scale(1.04);
}

.file-card-icon-placeholder.bg-gray { background: linear-gradient(135deg, #94a3b8, #475569); }
.file-card-icon-placeholder.bg-orange { background: linear-gradient(135deg, #f97316, #c2410c); }
.file-card-icon-placeholder.bg-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.file-card-icon-placeholder.bg-green { background: linear-gradient(135deg, #10b981, #047857); }

/* Download button overlay */
.file-card-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0;
    transform: translateY(-4px);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10;
}

.file-card:hover .file-card-actions {
    opacity: 1;
    transform: translateY(0);
}

.file-action-btn {
    width: 28px !important;
    height: 28px !important;
    border-radius: 50% !important;
    background: rgba(255, 255, 255, 0.95) !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    color: #334155 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
    margin: 0 !important;
    cursor: pointer !important;
    box-sizing: border-box !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.1) !important;
    backdrop-filter: blur(4px) !important;
    text-decoration: none !important;
}

.file-action-btn:hover {
    background: var(--indigo-600, #4f46e5) !important;
    color: #ffffff !important;
    border-color: var(--indigo-600, #4f46e5) !important;
    transform: scale(1.08) !important;
    box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3) !important;
}

.file-action-btn svg,
.file-action-btn i {
    width: 13px !important;
    height: 13px !important;
    min-width: 13px !important;
    min-height: 13px !important;
    display: block !important;
    margin: 0 !important;
    padding: 0 !important;
}

.file-card-info {
    padding: 12px 14px 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.file-card-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary, #0f172a);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-card-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    color: var(--text-secondary, #64748b);
}

.file-card-dot {
    color: #cbd5e1;
}

.file-card-user {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 2px;
    border-top: 1px solid var(--border-color, #f1f5f9);
    padding-top: 10px;
}

.file-card-avatar {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    object-fit: cover;
}

.file-card-username {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--text-secondary, #475569);
}

/* Lightbox styles */
.files-lightbox {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.3s ease;
}

.files-lightbox[hidden] {
    display: none !important;
}

.files-lightbox-overlay {
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.9);
    backdrop-filter: blur(8px);
}

.files-lightbox-content {
    position: relative;
    width: 100%;
    height: 100%;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: scale(0.95);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.files-lightbox.active .files-lightbox-content {
    transform: scale(1);
}

.files-lightbox-body {
    max-width: 80%;
    max-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.files-lightbox-img {
    max-width: 100%;
    max-height: 80vh;
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    border: 3px solid rgba(255, 255, 255, 0.1);
}

.files-lightbox-img[hidden] {
    display: none !important;
}

/* Lightbox fallback document view */
.files-lightbox-doc {
    background: #ffffff;
    border-radius: 20px;
    padding: 40px;
    width: 420px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.files-lightbox-doc[hidden] {
    display: none !important;
}

.files-lightbox-doc-icon-box {
    width: 120px;
    height: 120px;
    border-radius: 24px;
    background: #f1f5f9;
    color: var(--indigo-600, #4f46e5);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
}

.files-lightbox-doc-name {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
    width: 100%;
    word-break: break-all;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.files-lightbox-doc-meta {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 28px;
}

.files-lightbox-doc-dl-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--indigo-600, #4f46e5);
    color: #ffffff !important;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none !important;
    transition: all 0.2s ease;
}

.files-lightbox-doc-dl-btn:hover {
    background: var(--indigo-700, #4338ca);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

/* Lightbox controls styling */
.files-lightbox-close {
    position: absolute;
    top: 30px;
    right: 30px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #ffffff;
    font-size: 28px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    padding-bottom: 4px;
    transition: all 0.2s;
    z-index: 100;
}

.files-lightbox-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.05);
}

.files-lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #ffffff;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    z-index: 100;
}

.files-lightbox-nav:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-50%) scale(1.05);
}

.files-lightbox-nav:disabled {
    opacity: 0.25;
    cursor: not-allowed;
}

.files-lightbox-prev {
    left: 40px;
}

.files-lightbox-next {
    right: 40px;
}
</style>
