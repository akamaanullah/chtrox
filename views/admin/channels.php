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
        <span class="stat-value"><?php echo count($channels); ?></span>
        <div class="stat-line green"></div>
    </div>
    <div class="stat-card">
        <?php
            $publicCount = count(array_filter($channels, fn($c) => $c['visibility'] === 'public'));
            $privateCount = count(array_filter($channels, fn($c) => $c['visibility'] === 'private'));
            $mostActive = !empty($channels) ? '#' . $channels[0]['name'] : '#general';
        ?>
        <span class="stat-label">PUBLIC</span>
        <span class="stat-value"><?php echo $publicCount; ?></span>
        <div class="stat-line blue"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">PRIVATE</span>
        <span class="stat-value"><?php echo $privateCount; ?></span>
        <div class="stat-line orange"></div>
    </div>
    <div class="stat-card">
        <span class="stat-label">MOST ACTIVE</span>
        <span class="stat-value"><?php echo \App\Core\View::e($mostActive); ?></span>
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
<?php foreach ($channels as $c): ?>
                <tr class="channel-row" 
                    data-id="<?php echo \App\Core\View::e($c['id']); ?>" 
                    data-name="<?php echo \App\Core\View::e($c['name']); ?>" 
                    data-topic="<?php echo \App\Core\View::e($c['description'] ?: 'No description provided'); ?>" 
                    data-visibility="<?php echo \App\Core\View::e($c['visibility']); ?>"
                    data-status="Active"
                    data-members="<?php echo \App\Core\View::e($c['member_ids'] ?? ''); ?>"
                    data-created-on="<?php echo date('M d, Y', strtotime($c['created_at'])); ?>">
                    <td>
                        <div class="member-info-cell">
                            <?php if ($c['visibility'] === 'private'): ?>
                                <div class="avatar-mini" style="background: #fee2e2; color: #ef4444;"><i data-lucide="lock" size="14"></i></div>
                            <?php else: ?>
                                <div class="avatar-mini" style="background: var(--indigo-100); color: var(--indigo-600);">#</div>
                            <?php endif; ?>
                            <div class="member-details">
                                <span class="member-name"><?php echo \App\Core\View::e($c['name']); ?></span>
                            </div>
                        </div>
                    </td>
                    <td><span class="text-slate"><?php echo \App\Core\View::e($c['description'] ?: 'No topic provided'); ?></span></td>
                    <td><span class="role-badge <?php echo $c['visibility'] === 'private' ? 'private' : 'public'; ?>"><?php echo ucfirst($c['visibility']); ?></span></td>
                    <td><span class="text-dark font-700"><?php echo \App\Core\View::e($c['member_count']); ?></span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn js-open-channel-detail" title="View Details"><i data-lucide="eye"></i></button>
                            <button class="action-btn js-open-edit-channel-modal" title="Edit Channel"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Delete Channel"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
<?php endforeach; ?>
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
                        <?php foreach ($members as $m): ?>
                            <label class="cc-member-row cc-member-row-check">
                                <img src="<?php echo \App\Core\View::e($m['avatar']); ?>" alt="" class="cc-member-avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                <div class="cc-member-info">
                                    <span class="cc-member-name cc-member-display-name"><?php echo \App\Core\View::e($m['name']); ?></span>
                                    <span class="cc-member-handle">@<?php echo \App\Core\View::e($m['username']); ?></span>
                                </div>
                                <input type="checkbox" name="members[]" value="<?php echo \App\Core\View::e($m['id']); ?>" class="cc-member-check">
                            </label>
                        <?php endforeach; ?>
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
                        <?php foreach ($members as $m): ?>
                            <label class="cc-member-row cc-member-row-check">
                                <img src="<?php echo \App\Core\View::e($m['avatar']); ?>" alt="" class="cc-member-avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                <div class="cc-member-info">
                                    <span class="cc-member-name cc-member-display-name"><?php echo \App\Core\View::e($m['name']); ?></span>
                                    <span class="cc-member-handle">@<?php echo \App\Core\View::e($m['username']); ?></span>
                                </div>
                                <input type="checkbox" name="edit_members[]" value="<?php echo \App\Core\View::e($m['id']); ?>" class="cc-member-check edit-member-check">
                            </label>
                        <?php endforeach; ?>
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
