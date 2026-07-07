<?php
    $totalBytes = 0;
    foreach ($files as $f) {
        $totalBytes += $f['size_bytes'];
    }
    if ($totalBytes >= 1073741824) {
        $storageLabel = number_format($totalBytes / 1073741824, 1) . ' GB';
    } elseif ($totalBytes >= 1048576) {
        $storageLabel = number_format($totalBytes / 1048576, 1) . ' MB';
    } elseif ($totalBytes >= 1024) {
        $storageLabel = number_format($totalBytes / 1024, 1) . ' KB';
    } else {
        $storageLabel = $totalBytes . ' B';
    }
    $percentage = $totalBytes > 0 ? min(100, round(($totalBytes / (10 * 1024 * 1024 * 1024)) * 100, 1)) : 0;

    $exts = array_map(fn($f) => strtolower($f['extension']), $files);
    $counts = array_count_values($exts);
    arsort($counts);
    $mostFrequent = !empty($counts) ? strtoupper(key($counts)) : 'N/A';

    $recentCount = 0;
    foreach ($files as $f) {
        if (strtotime($f['date']) >= strtotime('-7 days')) {
            $recentCount++;
        }
    }

    $mediaFiles = array_filter($files, function($f) {
        return in_array(strtolower($f['extension']), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'mp4', 'mov', 'avi', 'mpeg']);
    });

    $docFiles = array_filter($files, function($f) {
        return !in_array(strtolower($f['extension']), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'mp4', 'mov', 'avi', 'mpeg']);
    });
?>
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
            <span class="stat-badge neutral"><?php echo $percentage; ?>%</span>
        </div>
        <h2 class="stat-value"><?php echo $storageLabel; ?></h2>
        <div class="stat-line orange"></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">MOST FREQUENT</span>
            <span class="stat-badge positive"><?php echo \App\Core\View::e($mostFrequent); ?></span>
        </div>
        <h2 class="stat-value"><?php echo count($files) > 0 ? count($counts) : 0; ?> ext</h2>
        <div class="stat-line purple" style="background-color: #a855f7;"></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">RECENT UPLOADS</span>
            <span class="stat-badge positive">+<?php echo $recentCount; ?></span>
        </div>
        <h2 class="stat-value"><?php echo $recentCount; ?></h2>
        <div class="stat-line green"></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">TOTAL ASSETS</span>
            <span class="stat-badge neutral">Live</span>
        </div>
        <h2 class="stat-value"><?php echo count($files); ?></h2>
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
            <?php foreach ($mediaFiles as $mf): ?>
                <?php
                    $isVid = in_array(strtolower($mf['extension']), ['mp4', 'mov', 'avi', 'mpeg']);
                    $fileUrl = BASE_URL . '/files/download/' . $mf['id'];
                ?>
                <div class="file-card media-item" data-category="media" data-id="<?php echo \App\Core\View::e($mf['id']); ?>" data-name="<?php echo \App\Core\View::e($mf['name']); ?>">
                    <div class="file-preview <?php echo $isVid ? 'video' : ''; ?>">
                        <?php if ($isVid): ?>
                            <video src="<?php echo $fileUrl; ?>" style="width: 100%; height: 100%; object-fit: cover;"></video>
                        <?php else: ?>
                            <img src="<?php echo $fileUrl; ?>" alt="<?php echo \App\Core\View::e($mf['name']); ?>">
                        <?php endif; ?>
                        <div class="file-actions-overlay">
                            <div class="action-btns">
                                <button class="action-btn js-preview-media" data-id="<?php echo \App\Core\View::e($mf['id']); ?>" data-type="<?php echo $isVid ? 'video' : 'image'; ?>" data-url="<?php echo $fileUrl; ?>" title="Preview"><i data-lucide="eye"></i></button>
                                <a href="<?php echo $fileUrl; ?>" class="action-btn" download title="Download"><i data-lucide="download"></i></a>
                                <button class="action-btn delete" data-id="<?php echo \App\Core\View::e($mf['id']); ?>" title="Delete"><i data-lucide="trash-2"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="file-info-compact">
                        <span class="file-name"><?php echo \App\Core\View::e($mf['name']); ?></span>
                        <span class="file-meta"><?php echo $mf['size']; ?> • <?php echo ucfirst($mf['category'] ?: 'file'); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
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
                    <?php foreach ($docFiles as $df): ?>
                        <?php
                            $fileUrl = BASE_URL . '/files/download/' . $df['id'];
                            $docClass = 'doc';
                            if ($df['icon'] === 'file-spreadsheet') $docClass = 'sheet';
                            elseif ($df['icon'] === 'file-archive') $docClass = 'archive';
                            elseif ($df['icon'] === 'file-code') $docClass = 'code';
                        ?>
                        <tr class="file-row" data-category="docs" data-id="<?php echo \App\Core\View::e($df['id']); ?>" data-name="<?php echo \App\Core\View::e($df['name']); ?>">
                            <td>
                                <div class="file-name-cell">
                                    <div class="file-icon-box <?php echo $docClass; ?>">
                                        <i data-lucide="<?php echo $df['icon']; ?>"></i>
                                    </div>
                                    <span class="text-dark font-600"><?php echo \App\Core\View::e($df['name']); ?></span>
                                </div>
                            </td>
                            <td><span class="tag-pill tag-update"><?php echo strtoupper($df['extension']); ?></span></td>
                            <td><span class="text-slate info-text"><?php echo $df['size']; ?></span></td>
                            <td>
                                <div class="member-info-mini">
                                    <img src="<?php echo \App\Core\View::e($df['shared_avatar']); ?>" alt="" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; margin-right: 6px;">
                                    <span class="text-dark font-600"><?php echo \App\Core\View::e($df['shared_by']); ?></span>
                                </div>
                            </td>
                            <td class="text-right">
                                <div class="action-btns">
                                    <a href="<?php echo $fileUrl; ?>" class="action-btn" download title="Download"><i data-lucide="download"></i></a>
                                    <button class="action-btn delete" data-id="<?php echo \App\Core\View::e($df['id']); ?>" title="Remove"><i data-lucide="trash-2"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
