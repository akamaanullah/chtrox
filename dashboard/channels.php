<?php
$page_title = 'Manage Channels - Chatrox';
include 'header.php';
?>

<header class="content-header">
    <div class="greeting-area">
        <div class="greeting-icon">
            <i data-lucide="hash"></i>
        </div>
        <div class="greeting-text">
            <h1>Manage Channels</h1>
            <p class="date">Create, manage and monitor your workspace channels.</p>
        </div>
    </div>
    <div class="header-actions">
        <button class="btn-primary create-channel-btn">
            <i data-lucide="plus"></i>
            <span>Create Channel</span>
        </button>
    </div>
</header>

<!-- Channel Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">TOTAL CHANNELS</span>
        <span class="stat-value">18</span>
        <div class="stat-line green"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">PUBLIC</span>
        <span class="stat-value">12</span>
        <div class="stat-line blue"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">PRIVATE</span>
        <span class="stat-value">6</span>
        <div class="stat-line orange"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">MOST ACTIVE</span>
        <span class="stat-value">#general</span>
        <div class="stat-line red"></div>
    </div>
</div>

<!-- Channel Filters -->
<div class="members-filters">
    <div class="search-box">
        <i data-lucide="search"></i>
        <input type="text" id="channelSearch" placeholder="Search channels by name or topic...">
    </div>
    <div class="filter-group">
        <select id="typeFilter" class="filter-select">
            <option value="">All Types</option>
            <option value="Public">Public</option>
            <option value="Private">Private</option>
        </select>
        <select id="statusFilter" class="filter-select">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Archived">Archived</option>
        </select>
    </div>
</div>

<!-- Channels Table Container -->
<div class="members-container">
    <div class="members-table-wrapper">
        <table class="members-table" id="channelsTable">
            <thead>
                <tr>
                    <th>CHANNEL NAME</th>
                    <th>TOPIC</th>
                    <th>PRIVACY</th>
                    <th>MEMBERS</th>
                    <th>STATUS</th>
                    <th class="text-right">ACTIONS</th>
                </tr>
            </thead>
            <tbody id="channelsTableBody">
                <!-- Channel 1 -->
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details">
                                <span class="member-name">general</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="text-slate">Main workspace discussions</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">12</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-channel-detail" title="View Details"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Channel 2 -->
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: #fee2e2; color: #ef4444;"><i data-lucide="lock" size="14"></i></div>
                            <div class="member-details">
                                <span class="member-name">admin-only</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="text-slate">Confidential administrative talks</span></td>
                    <td><span class="role-badge" style="background: #eff6ff; color: #2563eb;">Private</span></td>
                    <td><span class="text-dark font-700">3</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Channel 3 -->
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details">
                                <span class="member-name">marketing</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="text-slate">Campaigns and social media sync</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">8</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Additional Mock Channels -->
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details"><span class="member-name">design-team</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">UI/UX assets and reviews</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">6</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: #fee2e2; color: #ef4444;"><i data-lucide="lock" size="14"></i></div>
                            <div class="member-details"><span class="member-name">secret-project</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Top secret development</span></td>
                    <td><span class="role-badge" style="background: #eff6ff; color: #2563eb;">Private</span></td>
                    <td><span class="text-dark font-700">4</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details"><span class="member-name">random</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Off-topic fun and memes</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">25</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details"><span class="member-name">help-desk</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Tech support and feedback</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">15</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: #fee2e2; color: #ef4444;"><i data-lucide="lock" size="14"></i></div>
                            <div class="member-details"><span class="member-name">hr-portal</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Human resources announcements</span></td>
                    <td><span class="role-badge" style="background: #eff6ff; color: #2563eb;">Private</span></td>
                    <td><span class="text-dark font-700">5</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details"><span class="member-name">announcements</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Company-wide news and updates</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">40</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details"><span class="member-name">frontend-dev</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">React and CSS discussions</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">10</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: #fee2e2; color: #ef4444;"><i data-lucide="lock" size="14"></i></div>
                            <div class="member-details"><span class="member-name">backend-core</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">API and database infrastructure</span></td>
                    <td><span class="role-badge" style="background: #eff6ff; color: #2563eb;">Private</span></td>
                    <td><span class="text-dark font-700">7</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details"><span class="member-name">devops</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">CI/CD and server management</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">3</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details"><span class="member-name">sales-crm</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Leads and customer tracking</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">12</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: #fee2e2; color: #ef4444;"><i data-lucide="lock" size="14"></i></div>
                            <div class="member-details"><span class="member-name">legal-review</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Contracts and compliance files</span></td>
                    <td><span class="role-badge" style="background: #eff6ff; color: #2563eb;">Private</span></td>
                    <td><span class="text-dark font-700">2</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details"><span class="member-name">social-media</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Posting schedules and viral trends</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">6</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details"><span class="member-name">feedback-loop</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Product feedback from beta users</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">30</span></td>
                    <td><div class="status-indicator offline"><span class="dot"></span><span>Archived</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: #fee2e2; color: #ef4444;"><i data-lucide="lock" size="14"></i></div>
                            <div class="member-details"><span class="member-name">leadership</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Executive strategy and planning</span></td>
                    <td><span class="role-badge" style="background: #eff6ff; color: #2563eb;">Private</span></td>
                    <td><span class="text-dark font-700">5</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <tr class="channel-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <div class="member-details"><span class="member-name">legacy-chat</span></div>
                        </div>
                    </td>
                    <td><span class="text-slate">Old messages from previous platform</span></td>
                    <td><span class="role-badge">Public</span></td>
                    <td><span class="text-dark font-700">0</span></td>
                    <td><div class="status-indicator offline"><span class="dot"></span><span>Archived</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- No Results State -->
        <div id="noResults" class="no-results-state" style="display: none;">
            <div class="empty-icon">
                <i data-lucide="search-x"></i>
            </div>
            <h3>No channels found</h3>
            <p>We couldn't find any channels matching your search or filters.</p>
            <button class="btn-outline reset-filters-btn">Clear all filters</button>
        </div>
    </div>

    <!-- Pagination Footer -->
    <div class="channels-footer">
        <div class="per-page-selector">
            <span>Rows per page:</span>
            <select id="rowsPerPage" class="filter-select mini">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
        </div>
        
        <div class="pagination-info">
            <span id="showingStart">1</span>-<span id="showingEnd">10</span> of <span id="totalRows">18</span>
        </div>

        <div class="pagination-controls">
            <button class="pag-btn" id="prevPage" title="Previous Page">
                <i data-lucide="chevron-left"></i>
            </button>
            <div class="page-numbers" id="pageNumbers">
                <!-- Dynamic page numbers -->
            </div>
            <button class="pag-btn" id="nextPage" title="Next Page">
                <i data-lucide="chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Create Channel Modal - Premium UI (Synced from Chat Tool) -->
<div class="modal-overlay" id="createChannelModal">
    <div class="modal-content modal-content--create-channel">
        <div class="cc-modal-header">
            <div class="cc-modal-titles">
                <h3>Create Channel</h3>
                <span class="cc-modal-subtitle">START A NEW SPACE FOR YOUR TEAM</span>
            </div>
            <button type="button" class="modal-close js-close-create-channel-modal">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        <div class="cc-modal-body custom-scrollbar">
            <form id="createChannelForm" class="cc-form">
                <div class="cc-field">
                    <label class="cc-label">CHANNEL NAME</label>
                    <div class="cc-input-wrap cc-input-wrap--hash">
                        <span class="cc-input-prefix">#</span>
                        <input type="text" name="channel_name" id="ccChannelName" placeholder="e.g. design-sync"
                            required maxlength="80" class="cc-input">
                    </div>
                </div>

                <div class="cc-field">
                    <label class="cc-label">VISIBILITY</label>
                    <div class="cc-visibility-pills">
                        <label class="cc-pill cc-pill--public">
                            <input type="radio" name="visibility" value="public" checked>
                            <i data-lucide="megaphone" size="16"></i>
                            <span>PUBLIC</span>
                        </label>
                        <label class="cc-pill cc-pill--private">
                            <input type="radio" name="visibility" value="private">
                            <i data-lucide="lock" size="16"></i>
                            <span>PRIVATE</span>
                        </label>
                    </div>
                    <p class="cc-visibility-desc" id="visibilityDesc">Anyone in the workspace can find and join this
                        channel.</p>
                </div>

                <div class="cc-field cc-field--toggle">
                    <div class="cc-toggle-block">
                        <div class="cc-toggle-label-wrap">
                            <span class="cc-toggle-title">ADD ALL MEMBERS OF CHATROX</span>
                            <span class="cc-toggle-desc">Automatically add everyone in the company</span>
                        </div>
                        <label class="cc-toggle">
                            <input type="checkbox" name="add_all_members" id="addAllMembers">
                            <span class="cc-toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="cc-field" id="ccSpecificPeopleField">
                    <div class="cc-specific-header">
                        <label class="cc-label">ADD SPECIFIC PEOPLE</label>
                        <span class="cc-selected-count" id="selectedCount">0 selected</span>
                    </div>
                    <div class="cc-search-wrap">
                        <i data-lucide="search" size="18"></i>
                        <input type="text" class="cc-search" placeholder="Search people..." id="searchPeople">
                    </div>
                    <div class="cc-members-list custom-scrollbar" id="ccMembersList">
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Emma Williams</span>
                                <span class="cc-member-handle">@emmawilliams</span>
                            </div>
                            <input type="checkbox" name="members[]" value="emma" class="cc-member-check">
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Oliver Mitchell</span>
                                <span class="cc-member-handle">@olivermitchell</span>
                            </div>
                            <input type="checkbox" name="members[]" value="oliver" class="cc-member-check">
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Charlotte Anderson</span>
                                <span class="cc-member-handle">@charlotteanderson</span>
                            </div>
                            <input type="checkbox" name="members[]" value="charlotte" class="cc-member-check">
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Sophia Reynolds</span>
                                <span class="cc-member-handle">@sophiareynolds</span>
                            </div>
                            <input type="checkbox" name="members[]" value="sophia" class="cc-member-check">
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Liam Carter</span>
                                <span class="cc-member-handle">@liamcarter</span>
                            </div>
                            <input type="checkbox" name="members[]" value="liam" class="cc-member-check">
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1522071823991-b9671f9d7f1f?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Design Team</span>
                                <span class="cc-member-handle">@designteam</span>
                            </div>
                            <input type="checkbox" name="members[]" value="design" class="cc-member-check">
                        </label>
                    </div>
                </div>

                <button type="submit" class="cc-submit-btn" id="ccSubmitBtn" disabled>FINALIZE & CREATE CHANNEL</button>
            </form>
        </div>
    </div>
</div>

<!-- Edit Channel Modal - Premium UI (Synced style) -->
<div class="modal-overlay" id="editChannelModal">
    <div class="modal-content modal-content--create-channel">
        <div class="cc-modal-header">
            <div class="cc-modal-titles">
                <h3>Edit Channel</h3>
                <span class="cc-modal-subtitle">MODIFY SETTINGS FOR THIS CHANNEL</span>
            </div>
            <button type="button" class="modal-close js-close-edit-channel-modal">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        <div class="cc-modal-body custom-scrollbar">
            <form id="editChannelForm" class="cc-form">
                <input type="hidden" id="editOriginalName">
                <div class="cc-field">
                    <label class="cc-label">CHANNEL NAME</label>
                    <div class="cc-input-wrap cc-input-wrap--hash">
                        <span class="cc-input-prefix">#</span>
                        <input type="text" name="channel_name" id="editChannelName" placeholder="e.g. design-sync"
                            required maxlength="80" class="cc-input">
                    </div>
                </div>

                <div class="cc-field">
                    <label class="cc-label">VISIBILITY</label>
                    <div class="cc-visibility-pills">
                        <label class="cc-pill cc-pill--public" id="editPillPublic">
                            <input type="radio" name="edit_visibility" value="public" id="editVisPublic">
                            <i data-lucide="megaphone" size="16"></i>
                            <span>PUBLIC</span>
                        </label>
                        <label class="cc-pill cc-pill--private" id="editPillPrivate">
                            <input type="radio" name="edit_visibility" value="private" id="editVisPrivate">
                            <i data-lucide="lock" size="16"></i>
                            <span>PRIVATE</span>
                        </label>
                    </div>
                    <p class="cc-visibility-desc" id="editVisibilityDesc">Anyone in the workspace can find and join this channel.</p>
                </div>


                <div class="cc-field cc-field--toggle">
                    <div class="cc-toggle-block">
                        <div class="cc-toggle-label-wrap">
                            <span class="cc-toggle-title">ADD ALL MEMBERS OF CHATROX</span>
                            <span class="cc-toggle-desc">Automatically add everyone in the company</span>
                        </div>
                        <label class="cc-toggle">
                            <input type="checkbox" name="edit_add_all_members" id="editAddAllMembers">
                            <span class="cc-toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="cc-field" id="editMemberField">
                    <div class="cc-specific-header">
                        <label class="cc-label">MANAGE MEMBERS</label>
                        <span class="cc-selected-count" id="editSelectedCount">0 selected</span>
                    </div>
                    <div class="cc-search-wrap">
                        <i data-lucide="search" size="18"></i>
                        <input type="text" class="cc-search" placeholder="Search people..." id="editSearchPeople">
                    </div>
                    <div class="cc-members-list custom-scrollbar" id="editMembersList">
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Emma Williams</span>
                                <span class="cc-member-handle">@emmawilliams</span>
                            </div>
                            <input type="checkbox" name="edit_members[]" value="emma" class="cc-member-check edit-member-check">
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Oliver Mitchell</span>
                                <span class="cc-member-handle">@olivermitchell</span>
                            </div>
                            <input type="checkbox" name="edit_members[]" value="oliver" class="cc-member-check edit-member-check">
                        </label>
                        <label class="cc-member-row">
                            <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=150"
                                alt="" class="cc-member-avatar">
                            <div class="cc-member-info">
                                <span class="cc-member-name">Charlotte Anderson</span>
                                <span class="cc-member-handle">@charlotteanderson</span>
                            </div>
                            <input type="checkbox" name="edit_members[]" value="charlotte" class="cc-member-check edit-member-check">
                        </label>
                    </div>
                </div>

                <button type="submit" class="cc-submit-btn" id="editSubmitBtn">SAVE CHANGES</button>
            </form>
        </div>
    </div>
</div>
<!-- Channel Detail Modal -->
<div class="modal-overlay" id="channelDetailModal">
    <div class="modal-content modal-content--detail">
        <div class="modal-header">
            <div class="modal-title-area">
                <div class="modal-text">
                    <h3 id="detailChannelName">channel-name</h3>
                    <p id="detailChannelTopic">Channel topic description goes here</p>
                </div>
            </div>
            <button type="button" class="modal-close js-close-channel-detail">
                <i data-lucide="x"></i>
            </button>
        </div>
        
        <div class="modal-tabs">
            <button class="modal-tab active" data-tab="about">About</button>
            <button class="modal-tab" data-tab="members">Members <span class="tab-badge" id="detailMemberCount">0</span></button>
        </div>

        <div class="modal-body custom-scrollbar">
            <!-- About Tab -->
            <div class="tab-pane active" id="tabAbout">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>PRIVACY</label>
                        <div class="detail-value" id="detailPrivacy">Public</div>
                    </div>
                    <div class="detail-item">
                        <label>STATUS</label>
                        <div class="detail-value" id="detailStatus">Active</div>
                    </div>
                    <div class="detail-item">
                        <label>CREATED BY</label>
                        <div class="detail-value">Mahad Bukhari</div>
                    </div>
                    <div class="detail-item">
                        <label>CREATED ON</label>
                        <div class="detail-value">Mar 12, 2026</div>
                    </div>
                </div>
            </div>

            <!-- Members Tab -->
            <div class="tab-pane" id="tabMembers" style="display: none;">
                <div class="member-search-wrap">
                    <i data-lucide="search" size="18"></i>
                    <input type="text" id="memberSearchModal" placeholder="Search members in channel..." class="member-search-input">
                </div>
                <div class="detail-members-list custom-scrollbar" id="detailMembersList">
                    <!-- Members will be populated here -->
                </div>
            </div>
        </div>
        
        <div class="modal-footer" style="justify-content: center;">
            <button class="btn-primary js-switch-to-edit" style="width: 100%; max-width: 280px;">EDIT SETTINGS</button>
        </div>
    </div>
</div>

<script src="js/channels.js"></script>
<?php include 'footer.php'; ?>
