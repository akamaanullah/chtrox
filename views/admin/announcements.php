<header class="content-header">
    <div class="greeting-area">
        <div class="greeting-icon">
            <i data-lucide="megaphone"></i>
        </div>
        <div class="greeting-text">
            <h1>Announcements</h1>
            <p class="date">Broadcasting important updates and celebrations to the team.</p>
        </div>
    </div>
    <div class="header-actions">
        <button class="btn-primary add-announcement-btn">
            <i data-lucide="plus"></i>
            <span>Add Announcement</span>
        </button>
    </div>
</header>

<!-- Announcement Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">TOTAL BROADCASTS</span>
        <span class="stat-value"><?php echo count($announcements); ?></span>
        <div class="stat-line blue"></div>
    </div>
    <div class="stat-card">
        <?php
            $importantCount = count(array_filter($announcements, fn($a) => $a['tag'] === 'IMPORTANT'));
            $celebrationCount = count(array_filter($announcements, fn($a) => $a['tag'] === 'CELEBRATION'));
        ?>
        <span class="stat-label">IMPORTANT</span>
        <span class="stat-value"><?php echo $importantCount; ?></span>
        <div class="stat-line red"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">CELEBRATIONS</span>
        <span class="stat-value"><?php echo $celebrationCount; ?></span>
        <div class="stat-line orange"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">ENGAGEMENT</span>
        <span class="stat-value">94%</span>
        <div class="stat-line green"></div>
    </div>
</div>

<!-- Filters -->
<div class="members-filters">
    <div class="search-box">
        <i data-lucide="search"></i>
        <input type="text" id="annSearch" placeholder="Search announcements by title or content...">
    </div>
    <div class="filter-group">
        <select id="tagFilter" class="filter-select">
            <option value="">All Tags</option>
            <option value="IMPORTANT">IMPORTANT</option>
            <option value="CELEBRATION">CELEBRATION</option>
            <option value="UPDATE">UPDATE</option>
        </select>
    </div>
</div>

<!-- Table Container -->
<div class="members-container">
    <div class="members-table-wrapper">
        <table class="members-table" id="announcementsTable">
            <thead>
                <tr>
                    <th>TYPE & TAG</th>
                    <th>TITLE</th>
                    <th>POSTED BY</th>
                    <th>DATE</th>
                    <th class="text-right">ACTIONS</th>
                </tr>
            </thead>
            <tbody id="announcementsTableBody">
<?php foreach ($announcements as $ann): ?>
                <?php
                    $emoji = '📢';
                    if ($ann['tag'] === 'IMPORTANT') $emoji = '🚨';
                    elseif ($ann['tag'] === 'CELEBRATION') $emoji = '🎂';
                ?>
                <tr class="ann-row" 
                    data-id="<?php echo \App\Core\View::e($ann['id']); ?>" 
                    data-title="<?php echo \App\Core\View::e($ann['title']); ?>" 
                    data-tag="<?php echo \App\Core\View::e($ann['tag']); ?>" 
                    data-message="<?php echo \App\Core\View::e($ann['message']); ?>"
                    data-start="<?php echo date('Y-m-d', strtotime($ann['start_date'])); ?>"
                    data-end="<?php echo date('Y-m-d', strtotime($ann['end_date'])); ?>">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji"><?php echo $emoji; ?></span>
                            <span class="tag-pill tag-<?php echo strtolower($ann['tag']); ?>"><?php echo \App\Core\View::e($ann['tag']); ?></span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600"><?php echo \App\Core\View::e($ann['title']); ?></span></td>
                    <td>
                        <div class="member-info-cell">
                            <?php if ($ann['avatar_path']): ?>
                                <img src="<?php echo \App\Core\View::e($ann['avatar_path']); ?>" alt="" class="avatar-mini" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">A</div>
                            <?php endif; ?>
                            <span class="member-name admin-name"><?php echo \App\Core\View::e($ann['admin_name'] ?: 'ChatRox Admin'); ?></span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>

        <!-- No Results -->
        <div id="noResults" class="no-results-state" style="display: none;">
            <div class="empty-icon">
                <i data-lucide="search-x"></i>
            </div>
            <h3>No announcements found</h3>
            <p>Try adjusting your search or tag filters.</p>
            <button class="btn-outline reset-filters-btn">Clear all filters</button>
        </div>
    </div>

    <!-- Pagination -->
    <div class="announcements-footer">
        <div class="per-page-selector">
            <span>Rows per page:</span>
            <select id="rowsPerPage" class="filter-select mini">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="20">20</option>
            </select>
        </div>
        
        <div class="pagination-info">
            <span id="showingStart">1</span>-<span id="showingEnd">10</span> of <span id="totalRows">12</span>
        </div>

        <div class="pagination-controls">
            <button class="pag-btn" id="prevPage" title="Previous Page">
                <i data-lucide="chevron-left"></i>
            </button>
            <div class="page-numbers" id="pageNumbers"></div>
            <button class="pag-btn" id="nextPage" title="Next Page">
                <i data-lucide="chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Add Announcement Modal -->
<div class="modal-overlay" id="addAnnouncementModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title-area">
                <div>
                    <h3>Post New Announcement</h3>
                    <p>SHARING UPDATES WITH YOUR WORKSPACE</p>
                </div>
            </div>
            <button class="modal-close js-close-ann-modal">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="addAnnouncementForm" class="modal-form">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>TITLE</label>
                        <input type="text" name="title" placeholder="Enter headline..." required>
                    </div>

                    <div class="form-group">
                        <label>TAG</label>
                        <select name="tag" id="newAnnTag" required>
                            <option value="IMPORTANT">IMPORTANT</option>
                            <option value="CELEBRATION">CELEBRATION</option>
                            <option value="UPDATE" selected>UPDATE</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>EMOJI PREVIEW</label>
                        <div id="emojiPreview" class="emoji-preview-box">📢</div>
                    </div>

                    <div class="form-group full-width">
                        <label>MESSAGE</label>
                        <textarea name="message" placeholder="What's the highlight?" required rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label>START DATE</label>
                        <input type="date" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label>END DATE</label>
                        <input type="date" name="end_date" required>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="submit" form="addAnnouncementForm" class="btn-primary" id="annSubmitBtn" style="width: 100%; justify-content: center;">
                <i data-lucide="send"></i>
                <span>BROADCAST NOW</span>
            </button>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal-overlay" id="editAnnouncementModal">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title-area">
                <div>
                    <h3>Edit Announcement</h3>
                    <p>MODIFY EXISTING UPDATES FOR YOUR WORKSPACE</p>
                </div>
            </div>
            <button class="modal-close js-close-edit-modal">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="editAnnouncementForm" class="modal-form">
                <input type="hidden" name="id" id="editAnnId">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>TITLE</label>
                        <input type="text" name="title" id="editAnnTitle" placeholder="Enter headline..." required>
                    </div>

                    <div class="form-group">
                        <label>TAG</label>
                        <select name="tag" id="editAnnTag" required>
                            <option value="IMPORTANT">IMPORTANT</option>
                            <option value="CELEBRATION">CELEBRATION</option>
                            <option value="UPDATE">UPDATE</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>EMOJI PREVIEW</label>
                        <div id="editEmojiPreview" class="emoji-preview-box">📢</div>
                    </div>

                    <div class="form-group full-width">
                        <label>MESSAGE</label>
                        <textarea name="message" id="editAnnMessage" placeholder="What's the highlight?" required rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label>START DATE</label>
                        <input type="date" name="start_date" id="editAnnStartDate" required>
                    </div>

                    <div class="form-group">
                        <label>END DATE</label>
                        <input type="date" name="end_date" id="editAnnEndDate" required>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="submit" form="editAnnouncementForm" class="btn-primary" id="editAnnSubmitBtn" style="width: 100%; justify-content: center;">
                <i data-lucide="check"></i>
                <span>SAVE CHANGES</span>
            </button>
        </div>
    </div>
</div>
