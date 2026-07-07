<header class="content-header">
    <div class="greeting-area">
        <div class="greeting-icon">
            <i data-lucide="activity"></i>
        </div>
        <div class="greeting-text">
            <h1>Activity Log</h1>
            <p class="date">Monitor real-time workspace actions and system updates.</p>
        </div>
    </div>
    <div class="header-actions">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" id="activitySearch" placeholder="Search by member, message or IP...">
        </div>
    </div>
</header>

<div class="activity-table-container custom-scrollbar">
    <table class="activity-table" id="activityTable">
        <thead>
            <tr>
                <th class="col-time">Time</th>
                <th class="col-status">Status</th>
                <th class="col-member">Member</th>
                <th class="col-message">Message</th>
                <th class="col-activity">Activity</th>
                <th class="col-ip">IP Address</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($activities as $act): ?>
            <?php
                $isSystem = empty($act['actor_member_id']);
                $type = strtolower($act['activity_type']);
                
                $status = strtolower($act['status'] ?? 'complete');
                $badgeClass = 'complete';
                if ($status === 'failed') $badgeClass = 'failed';
                elseif ($status === 'warning') $badgeClass = 'warning';
            ?>
            <tr class="activity-card-log" data-type="<?php echo $type; ?>">
                <td class="col-time"><?php echo date('m/d/y, g:i a', strtotime($act['created_at'])); ?></td>
                <td class="col-status"><span class="status-badge <?php echo $badgeClass; ?>"><?php echo strtoupper($status); ?></span></td>
                <td class="col-member">
                    <?php if ($isSystem): ?>
                        <div class="member-info-mini">
                            <div class="mini-avatar system zap" style="width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e0f2fe; color: #0284c7; margin-right: 8px;"><i data-lucide="zap" size="12"></i></div>
                            <div class="member-text">
                                <span class="m-name"><?php echo \App\Core\View::e($act['actor_label'] ?: 'System'); ?></span>
                                <span class="m-handle">System</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="member-info-mini">
                            <?php if ($act['avatar_path']): ?>
                                <img src="<?php echo \App\Core\View::e($act['avatar_path']); ?>" alt="" class="mini-avatar" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div class="avatar-mini-circle" style="background: var(--indigo-100); color: var(--indigo-600); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; margin-right: 8px;">
                                    <?php
                                        $initials = '';
                                        $names = explode(' ', $act['actor_label']);
                                        foreach ($names as $n) { $initials .= strtoupper(substr($n, 0, 1)); }
                                        echo \App\Core\View::e(substr($initials, 0, 2));
                                    ?>
                                </div>
                            <?php endif; ?>
                            <div class="member-text">
                                <span class="m-name"><?php echo \App\Core\View::e($act['actor_label']); ?></span>
                                <span class="m-handle">@<?php echo \App\Core\View::e($act['actor_handle'] ?: 'member'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="col-message"><?php echo \App\Core\View::e($act['message']); ?></td>
                <td class="col-activity"><?php echo ucfirst(strtolower($act['activity_type'])); ?></td>
                <td class="col-ip"><?php echo \App\Core\View::e($act['ip_address'] ?: '127.0.0.1'); ?></td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- No Results State -->
<div id="noResults" class="no-results-state" style="display: none;">
    <div class="no-results-content">
        <div class="no-results-icon">
            <i data-lucide="search-x"></i>
        </div>
        <h3>No activity found</h3>
        <p>We couldn't find any activities matching your search criteria.</p>
        <button class="btn-outline" onclick="document.getElementById('activitySearch').value=''; document.getElementById('activitySearch').dispatchEvent(new Event('input'));">Clear search</button>
    </div>
</div>

<!-- Pagination Footer -->
<div class="activity-footer">
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
        <span id="showingStart">1</span>-<span id="showingEnd">10</span> of <span id="totalRows">15</span>
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
