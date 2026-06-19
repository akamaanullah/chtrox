<header class="content-header">
    <div class="greeting-area">
        <div class="greeting-icon">
            <i data-lucide="file-text"></i>
        </div>
        <div class="greeting-text">
            <h1>Files & Media</h1>
            <p class="date">Manage And Locate All Workspace Assets</p>
        </div>
    </div>
</header>

<!-- Files Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">STORAGE USED</span>
            <span class="stat-badge neutral">25%</span>
        </div>
        <h2 class="stat-value">12.4 GB</h2>
        <div class="stat-line orange"></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">MOST FREQUENT</span>
            <span class="stat-badge positive">Images</span>
        </div>
        <h2 class="stat-value">4.2k</h2>
        <div class="stat-line purple" style="background-color: #a855f7;"></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">RECENT UPLOADS</span>
            <span class="stat-badge positive">+12</span>
        </div>
        <h2 class="stat-value">128</h2>
        <div class="stat-line green"></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">TOTAL ASSETS</span>
            <span class="stat-badge neutral">Live</span>
        </div>
        <h2 class="stat-value">5.8k</h2>
        <div class="stat-line blue"></div>
    </div>
</div>

<!-- Files Filters -->
<div class="members-filters" style="margin-top: 24px;">
    <div class="search-box">
        <i data-lucide="search"></i>
        <input type="text" id="fileSearch" placeholder="Search by name, type, or uploader...">
    </div>
    <div class="filter-group">
        <div class="filter-pills">
            <button class="filter-pill active" data-filter="all">All Files</button>
            <button class="filter-pill" data-filter="media">Media</button>
            <button class="filter-pill" data-filter="docs">Documents</button>
        </div>
    </div>
</div>

<div class="files-container" style="margin-top: 32px;">
    <!-- Media Section -->
    <div id="mediaSection">
        <div class="section-header-flex">
            <h3 class="section-heading">MEDIA ASSETS</h3>
        </div>
        
        <!-- Media Grid (Images/Videos) -->
        <div id="mediaGrid" class="media-grid">
        <!-- Mock Images -->
        <div class="file-card media-item" data-category="images">
            <div class="file-preview">
                <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&q=80" alt="Abstract Design">
                <div class="file-actions-overlay">
                    <div class="action-btns">
                        <button class="action-btn" title="Preview"><i data-lucide="eye"></i></button>
                        <button class="action-btn" title="Download"><i data-lucide="download"></i></button>
                        <button class="action-btn delete" title="Delete"><i data-lucide="trash-2"></i></button>
                    </div>
                </div>
            </div>
            <div class="file-info-compact">
                <span class="file-name">abstract_design_v2.jpg</span>
                <span class="file-meta">2.4 MB • Image</span>
            </div>
        </div>

        <div class="file-card media-item" data-category="images">
            <div class="file-preview">
                <img src="https://images.unsplash.com/photo-1633356122544-f134324a6cee?w=400&q=80" alt="Team Meeting">
                <div class="file-actions-overlay">
                    <div class="action-btns">
                        <button class="action-btn" title="Preview"><i data-lucide="eye"></i></button>
                        <button class="action-btn" title="Download"><i data-lucide="download"></i></button>
                        <button class="action-btn delete" title="Delete"><i data-lucide="trash-2"></i></button>
                    </div>
                </div>
            </div>
            <div class="file-info-compact">
                <span class="file-name">team_sync_conference.png</span>
                <span class="file-meta">4.1 MB • Image</span>
            </div>
        </div>

        <div class="file-card media-item" data-category="videos">
            <div class="file-preview video">
                <img src="https://images.unsplash.com/photo-1485846234645-a62644f84728?w=400&q=80" alt="Product Demo">
                <div class="file-actions-overlay">
                    <div class="action-btns">
                        <button class="action-btn" title="Preview"><i data-lucide="eye"></i></button>
                        <button class="action-btn" title="Download"><i data-lucide="download"></i></button>
                        <button class="action-btn delete" title="Delete"><i data-lucide="trash-2"></i></button>
                    </div>
                </div>
            </div>
            <div class="file-info-compact">
                <span class="file-name">product_demo_final.mp4</span>
                <span class="file-meta">48.5 MB • Video</span>
            </div>
        </div>

         <div class="file-card media-item" data-category="videos">
            <div class="file-preview video">
                <img src="https://images.unsplash.com/photo-1485846234645-a62644f84728?w=400&q=80" alt="Product Demo">
                <div class="file-actions-overlay">
                    <div class="action-btns">
                        <button class="action-btn" title="Preview"><i data-lucide="eye"></i></button>
                        <button class="action-btn" title="Download"><i data-lucide="download"></i></button>
                        <button class="action-btn delete" title="Delete"><i data-lucide="trash-2"></i></button>
                    </div>
                </div>
            </div>
            <div class="file-info-compact">
                <span class="file-name">product_demo_final.mp4</span>
                <span class="file-meta">48.5 MB • Video</span>
            </div>
        </div>

         <div class="file-card media-item" data-category="videos">
            <div class="file-preview video">
                <img src="https://images.unsplash.com/photo-1485846234645-a62644f84728?w=400&q=80" alt="Product Demo">
                <div class="file-actions-overlay">
                    <div class="action-btns">
                        <button class="action-btn" title="Preview"><i data-lucide="eye"></i></button>
                        <button class="action-btn" title="Download"><i data-lucide="download"></i></button>
                        <button class="action-btn delete" title="Delete"><i data-lucide="trash-2"></i></button>
                    </div>
                </div>
            </div>
            <div class="file-info-compact">
                <span class="file-name">product_demo_final.mp4</span>
                <span class="file-meta">48.5 MB • Video</span>
            </div>
        </div>
        </div>
    </div>

    <!-- Documents Section -->
    <div id="docsSection">
        <div class="section-header-flex" style="margin-top: 48px;">
            <h3 class="section-heading">DOCUMENTS & FILES</h3>
        </div>

        <!-- Documents Table -->
        <div id="docsTable" class="members-container" style="margin-top: 24px;">
        <div class="members-table-wrapper">
            <table class="members-table">
                <thead>
                    <tr>
                        <th>FILENAME</th>
                        <th>TYPE</th>
                        <th>SIZE</th>
                        <th>UPLOADED BY</th>
                        <th class="text-right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="filesTableBody">
                <tr class="file-row" data-category="docs">
                    <td>
                        <div class="file-name-cell">
                            <div class="file-icon-box doc">
                                <i data-lucide="file-text"></i>
                            </div>
                            <span class="text-dark font-600">Quarterly_Report_Q3.pdf</span>
                        </div>
                    </td>
                    <td><span class="tag-pill tag-update">PDF</span></td>
                    <td><span class="text-slate info-text">1.2 MB</span></td>
                    <td>
                        <div class="member-info-mini">
                            <span class="text-dark font-600">Mahad Bukhari</span>
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="Download"><i data-lucide="download"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="file-row" data-category="docs">
                    <td>
                        <div class="file-name-cell">
                            <div class="file-icon-box sheet">
                                <i data-lucide="file-spreadsheet"></i>
                            </div>
                            <span class="text-dark font-600">Project_Phoenix_Budget.xlsx</span>
                        </div>
                    </td>
                    <td><span class="tag-pill tag-important">EXCEL</span></td>
                    <td><span class="text-slate info-text">856 KB</span></td>
                    <td>
                        <div class="member-info-mini">
                            <span class="text-dark font-600">Emma Williams</span>
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="Download"><i data-lucide="download"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                 <tr class="file-row" data-category="docs">
                    <td>
                        <div class="file-name-cell">
                            <div class="file-icon-box doc">
                                <i data-lucide="file-text"></i>
                            </div>
                            <span class="text-dark font-600">Quarterly_Report_Q3.pdf</span>
                        </div>
                    </td>
                    <td><span class="tag-pill tag-update">PDF</span></td>
                    <td><span class="text-slate info-text">1.2 MB</span></td>
                    <td>
                        <div class="member-info-mini">
                            <span class="text-dark font-600">Mahad Bukhari</span>
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="Download"><i data-lucide="download"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="file-row" data-category="docs">
                    <td>
                        <div class="file-name-cell">
                            <div class="file-icon-box sheet">
                                <i data-lucide="file-spreadsheet"></i>
                            </div>
                            <span class="text-dark font-600">Project_Phoenix_Budget.xlsx</span>
                        </div>
                    </td>
                    <td><span class="tag-pill tag-important">EXCEL</span></td>
                    <td><span class="text-slate info-text">856 KB</span></td>
                    <td>
                        <div class="member-info-mini">
                            <span class="text-dark font-600">Emma Williams</span>
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="Download"><i data-lucide="download"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                 <tr class="file-row" data-category="docs">
                    <td>
                        <div class="file-name-cell">
                            <div class="file-icon-box doc">
                                <i data-lucide="file-text"></i>
                            </div>
                            <span class="text-dark font-600">Quarterly_Report_Q3.pdf</span>
                        </div>
                    </td>
                    <td><span class="tag-pill tag-update">PDF</span></td>
                    <td><span class="text-slate info-text">1.2 MB</span></td>
                    <td>
                        <div class="member-info-mini">
                            <span class="text-dark font-600">Mahad Bukhari</span>
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="Download"><i data-lucide="download"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Media Lightbox -->
<div class="dm-msg-lightbox" id="mediaLightbox" role="dialog" aria-label="View media" hidden>
    <div class="dm-msg-lightbox-header">
        <a href="#" class="dm-msg-lightbox-download" id="lightboxDownload" download title="Download" aria-label="Download">
            <i data-lucide="download"></i>
        </a>
        <button type="button" class="dm-msg-lightbox-close" id="closeLightbox" aria-label="Close">
            <i data-lucide="x"></i>
        </button>
    </div>
    <button type="button" class="dm-msg-lightbox-prev" id="prevLightbox" aria-label="Previous" title="Previous" hidden>
        <i data-lucide="chevron-left"></i>
    </button>
    <button type="button" class="dm-msg-lightbox-next" id="nextLightbox" aria-label="Next" title="Next" hidden>
        <i data-lucide="chevron-right"></i>
    </button>
    <div class="dm-msg-lightbox-content">
        <img src="" alt="" class="dm-msg-lightbox-img" id="lightboxImg" hidden>
        <video src="" controls class="dm-msg-lightbox-video" id="lightboxVideo" hidden></video>
    </div>
    <div class="dm-msg-lightbox-thumbnails" id="lightboxThumbnails"></div>
</div>
