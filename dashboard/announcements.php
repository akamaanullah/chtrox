<?php
$page_title = 'Announcements - Chatrox';
include 'header.php';
?>

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
        <span class="stat-value">12</span>
        <div class="stat-line blue"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">IMPORTANT</span>
        <span class="stat-value">4</span>
        <div class="stat-line red"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">CELEBRATIONS</span>
        <span class="stat-value">3</span>
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
                <tr class="ann-row" 
                    data-id="1" 
                    data-title="Quarterly Security Audit Reminder" 
                    data-tag="IMPORTANT" 
                    data-message="All departments are required to complete their quarterly security audit by the end of this week. Please ensure all access logs are reviewed and signed off."
                    data-start="2023-10-12"
                    data-end="2023-10-19">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji">🚨</span>
                            <span class="tag-pill tag-important">IMPORTANT</span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600">Quarterly Security Audit Reminder</span></td>
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">C</div>
                            <span class="member-name admin-name">ChatroxAdmin</span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text">Oct 12, 2023</span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="ann-row"
                    data-id="2"
                    data-title="Happy Birthday, Emma Williams!"
                    data-tag="CELEBRATION"
                    data-message="Let's all take a moment to wish Emma a very Happy Birthday! There will be cake in the breakroom at 3 PM."
                    data-start="2023-10-10"
                    data-end="2023-10-10">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji">🎂</span>
                            <span class="tag-pill tag-celebration">CELEBRATION</span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600">Happy Birthday, Emma Williams!</span></td>
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">C</div>
                            <span class="member-name admin-name">ChatroxAdmin</span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text">Oct 10, 2023</span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="ann-row"
                    data-id="3"
                    data-title="System Maintenance Scheduled"
                    data-tag="UPDATE"
                    data-message="Please be advised that system maintenance is scheduled for this Sunday from 2 AM to 6 AM. Some services may be unavailable during this window."
                    data-start="2023-10-08"
                    data-end="2023-10-08">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji">📢</span>
                            <span class="tag-pill tag-update">UPDATE</span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600">System Maintenance Scheduled</span></td>
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">C</div>
                            <span class="member-name admin-name">ChatroxAdmin</span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text">Oct 08, 2023</span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="ann-row"
                    data-id="4"
                    data-title="New Office Access Policy"
                    data-tag="IMPORTANT"
                    data-message="Starting next month, all employees must use the new digital keycards for office access. Physical keys will be phased out. Please collect your new card from HR."
                    data-start="2023-10-05"
                    data-end="2023-10-31">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji">🚨</span>
                            <span class="tag-pill tag-important">IMPORTANT</span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600">New Office Access Policy</span></td>
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">C</div>
                            <span class="member-name admin-name">ChatroxAdmin</span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text">Oct 05, 2023</span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="ann-row"
                    data-id="5"
                    data-title="Project Phoenix Launch Success!"
                    data-tag="CELEBRATION"
                    data-message="We've successfully launched Project Phoenix! Huge thanks to the engineering and product teams for their hard work over the last 6 months."
                    data-start="2023-10-01"
                    data-end="2023-10-01">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji">🎂</span>
                            <span class="tag-pill tag-celebration">CELEBRATION</span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600">Project Phoenix Launch Success!</span></td>
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">C</div>
                            <span class="member-name admin-name">ChatroxAdmin</span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text">Oct 01, 2023</span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="ann-row"
                    data-id="6"
                    data-title="Frontend Dashboard v2.0 Released"
                    data-tag="UPDATE"
                    data-message="Version 2.0 of our frontend dashboard is now live! This update includes the new analytics tab, improved navigation, and significant performance optimizations."
                    data-start="2023-09-28"
                    data-end="2023-09-28">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji">📢</span>
                            <span class="tag-pill tag-update">UPDATE</span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600">Frontend Dashboard v2.0 Released</span></td>
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">C</div>
                            <span class="member-name admin-name">ChatroxAdmin</span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text">Sep 28, 2023</span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="ann-row"
                    data-id="7"
                    data-title="Company Trip Photos are Here!"
                    data-tag="UPDATE"
                    data-message="The photos from last week's company trip have been uploaded to the shared folder. Relive the fun and great moments we shared together!"
                    data-start="2023-09-25"
                    data-end="2023-09-30">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji">📢</span>
                            <span class="tag-pill tag-update">UPDATE</span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600">Company Trip Photos are Here!</span></td>
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">C</div>
                            <span class="member-name admin-name">ChatroxAdmin</span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text">Sep 25, 2023</span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="ann-row"
                    data-id="8"
                    data-title="Urgent: Password Reset Required"
                    data-tag="IMPORTANT"
                    data-message="Due to a security update, all users are required to reset their forum passwords by tomorrow end of day. Please follow the instructions sent to your email."
                    data-start="2023-09-20"
                    data-end="2023-09-21">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji">🚨</span>
                            <span class="tag-pill tag-important">IMPORTANT</span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600">Urgent: Password Reset Required</span></td>
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">C</div>
                            <span class="member-name admin-name">ChatroxAdmin</span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text">Sep 20, 2023</span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="ann-row"
                    data-id="9"
                    data-title="Welcome New Joiner: John Doe"
                    data-tag="CELEBRATION"
                    data-message="Please join us in welcoming John Doe to the engineering team as our new Lead Frontend Developer! We're excited to have him on board."
                    data-start="2023-09-15"
                    data-end="2023-09-15">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji">🎂</span>
                            <span class="tag-pill tag-celebration">CELEBRATION</span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600">Welcome New Joiner: John Doe</span></td>
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">C</div>
                            <span class="member-name admin-name">ChatroxAdmin</span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text">Sep 15, 2023</span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="ann-row"
                    data-id="10"
                    data-title="Office Holiday Announcement"
                    data-tag="IMPORTANT"
                    data-message="The office will be closed next Monday in observance of the public holiday. Please ensure all critical tasks are handled beforehand. Enjoy the long weekend!"
                    data-start="2023-09-10"
                    data-end="2023-09-11">
                    <td>
                        <div class="ann-tag">
                            <span class="ann-emoji">🚨</span>
                            <span class="tag-pill tag-important">IMPORTANT</span>
                        </div>
                    </td>
                    <td><span class="text-dark font-600">Office Holiday Announcement</span></td>
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-600); color: #fff;">C</div>
                            <span class="member-name admin-name">ChatroxAdmin</span>
                        </div>
                    </td>
                    <td><span class="text-slate info-text">Sep 10, 2023</span></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn" title="View"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-ann-modal" title="Edit"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
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

<script src="js/announcements.js"></script>
<?php include 'footer.php'; ?>
