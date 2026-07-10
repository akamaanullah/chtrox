<header class="content-header">
    <div class="greeting-area">
        <div class="greeting-icon">
            <i data-lucide="users"></i>
        </div>
        <div class="greeting-text">
            <h1>Manage Members</h1>
            <p class="date">Add, remove or manage workspace members and roles.</p>
        </div>
    </div>
    <div class="header-actions" style="display: flex; gap: 8px;">
        <button class="btn-primary generate-invite-btn" style="background: var(--indigo-50, rgba(99, 102, 241, 0.08)); color: var(--indigo-600, #4f46e5); border: 1px solid var(--indigo-100, #e2e8f0); display: flex; align-items: center; gap: 8px; font-weight: 600;">
            <i data-lucide="link"></i>
            <span>Generate Invite Link</span>
        </button>
        <button class="btn-primary invite-btn" style="display: flex; align-items: center; gap: 8px;">
            <i data-lucide="user-plus"></i>
            <span>Add Member</span>
        </button>
    </div>
</header>

<!-- Members Filters -->
<div class="members-filters">
    <div class="search-box">
        <i data-lucide="search"></i>
        <input type="text" id="memberSearch" placeholder="Search members by name or role...">
    </div>
    <div class="filter-group">
        <select id="roleFilter" class="filter-select">
            <option value="">All Roles</option>
            <option value="Admin">Admin</option>
            <option value="Member">Member</option>
        </select>
        <select id="statusFilter" class="filter-select">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Offline">Offline</option>
        </select>
    </div>
</div>

<!-- Members Table Container -->
<div class="members-container">
    <div class="members-table-wrapper">
        <table class="members-table" id="membersTable">
            <thead>
                <tr>
                    <th>MEMBER</th>
                    <th>ROLE</th>
                    <th>STATUS</th>
                    <th>JOIN DATE</th>
                    <th class="text-right">ACTIONS</th>
                </tr>
            </thead>
            <tbody id="membersTableBody">
<?php foreach ($members as $member): ?>
                <tr class="member-row" data-id="<?php echo \App\Core\View::e($member['id']); ?>" data-username="<?php echo \App\Core\View::e($member['username']); ?>" data-email="<?php echo \App\Core\View::e($member['email']); ?>" data-role="<?php echo \App\Core\View::e($member['role']); ?>">
                    <td>
                        <div class="member-info-cell">
                            <?php if ($member['avatar'] && $member['avatar'] !== DEFAULT_AVATAR_URL): ?>
                                <img src="<?php echo \App\Core\View::e($member['avatar']); ?>" alt="Avatar" class="avatar-mini" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <?php
                                    $initials = '';
                                    $names = explode(' ', $member['name']);
                                    foreach ($names as $n) {
                                        $initials .= strtoupper(substr($n, 0, 1));
                                    }
                                    $initials = substr($initials, 0, 2);
                                    $colors = ['bg-indigo', 'bg-pink', 'bg-orange', 'bg-purple', 'bg-green', 'bg-cyan', 'bg-yellow', 'bg-red', 'bg-blue', 'bg-emerald', 'bg-slate', 'bg-amber', 'bg-rose'];
                                    $colorClass = $colors[ord(substr($member['name'], 0, 1)) % count($colors)];
                                ?>
                                <div class="avatar-mini <?php echo $colorClass; ?>"><?php echo \App\Core\View::e($initials); ?></div>
                            <?php endif; ?>
                            <div class="member-details">
                                <span class="member-name"><?php echo \App\Core\View::e($member['name']); ?></span>
                                <span class="member-email"><?php echo \App\Core\View::e($member['email']); ?></span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge <?php echo strtolower($member['role']); ?>"><?php echo \App\Core\View::e($member['role']); ?></span></td>
                    <td>
                        <?php 
                            $statusClass = strtolower($member['status']) === 'active' || strtolower($member['status']) === 'online' ? 'active' : 'offline';
                            $statusLabel = strtolower($member['status']) === 'active' || strtolower($member['status']) === 'online' ? 'Active' : 'Offline';
                        ?>
                        <div class="status-indicator <?php echo $statusClass; ?>">
                            <span class="dot"></span>
                            <span><?php echo $statusLabel; ?></span>
                        </div>
                    </td>
                    <td><?php echo \App\Core\View::e($member['join_date']); ?></td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
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
            <h3>No members found</h3>
            <p>We couldn't find any members matching your search or filters.</p>
            <button class="btn-outline reset-filters-btn">Clear all filters</button>
        </div>
    </div>

    <!-- Pagination Footer -->
    <div class="members-footer">
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
</div>

<!-- Edit Member Modal -->
<div id="editMemberModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title-area">
                <div>
                    <h3>Edit Member Details</h3>
                    <p>UPDATE MEMBER INFORMATION AND WORKSPACE ROLE</p>
                </div>
            </div>
            <button class="modal-close" id="closeModalBtn">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="editMemberForm" class="modal-form">
                <input type="hidden" id="editMemberId">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="editName">Full Name</label>
                        <input type="text" id="editName" placeholder="e.g. Mahad Bukhari" required>
                    </div>
                    <div class="form-group full-width">
                        <label for="editEmail">Email Address</label>
                        <input type="email" id="editEmail" placeholder="e.g. user@example.com" required>
                    </div>
                    <div class="form-group full-width">
                        <label for="editPassword">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="editPassword" placeholder="••••••••">
                            <i data-lucide="eye" class="toggle-password"></i>
                        </div>
                        <p class="field-hint">Leave blank to keep current password.</p>
                    </div>
                    <div class="form-group full-width">
                        <label for="editRole">Workspace Role</label>
                        <select id="editRole" class="filter-select" style="width: 100%;">
                            <option value="Admin">Admin</option>
                            <option value="Member">Member</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-primary" id="saveEditBtn" style="width: 100%; justify-content: center;">
                <i data-lucide="check"></i>
                <span>Save Changes</span>
            </button>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div id="addMemberModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title-area">
                <div>
                    <h3>Add New Member</h3>
                    <p>CREATE A NEW WORKSPACE ACCOUNT FOR YOUR TEAM</p>
                </div>
            </div>
            <button class="modal-close" id="closeAddModalBtn">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="addMemberForm" class="modal-form">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="addUsername">Username</label>
                        <input type="text" id="addUsername" placeholder="e.g. mahad_admin" required>
                    </div>
                    <div class="form-group full-width">
                        <label for="addEmail">Email Address</label>
                        <input type="email" id="addEmail" placeholder="e.g. user@example.com" required>
                    </div>
                    <div class="form-group full-width">
                        <label for="addPassword">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="addPassword" placeholder="••••••••" required>
                            <i data-lucide="eye" class="toggle-password"></i>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="confirmPassword" placeholder="••••••••" required>
                            <i data-lucide="eye" class="toggle-password"></i>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-primary" id="saveAddBtn" style="width: 100%; justify-content: center;">
                <i data-lucide="plus"></i>
                <span>Add Member</span>
            </button>
        </div>
    </div>
</div>

<!-- Generate Invite Link Modal -->
<div class="modal-overlay" id="generateInviteModal">
    <div class="modal-card" style="max-width: 480px;">
        <div class="modal-header">
            <div class="modal-header-title" style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--indigo-50); color: var(--indigo-600); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="link" size="18"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-primary);">Generate Invite Link</h3>
                    <p style="margin: 0; font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Self-Registration Token Link</p>
                </div>
            </div>
            <button class="modal-close" id="closeInviteModalBtn" style="background: none; border: none; cursor: pointer; color: var(--text-secondary);">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <form id="generateInviteForm" class="modal-form" style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="inviteEmail" style="font-size: 13px; font-weight: 600; color: var(--text-primary);">Email (Optional)</label>
                    <input type="email" id="inviteEmail" placeholder="Leave blank for generic self-registration link" class="filter-select" style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; font-size: 14px;">
                </div>
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="inviteRole" style="font-size: 13px; font-weight: 600; color: var(--text-primary);">Role</label>
                    <select id="inviteRole" class="filter-select" style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; font-size: 14px;">
                        <option value="member">Member (Default)</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div id="inviteResultArea" style="display: none; flex-direction: column; gap: 8px; margin-top: 10px; padding: 12px; background: var(--indigo-50, rgba(99, 102, 241, 0.04)); border: 1px solid var(--indigo-100, #a5b4fc); border-radius: 8px;">
                    <label style="font-size: 12px; font-weight: 700; color: var(--indigo-600); text-transform: uppercase; letter-spacing: 0.05em;">Your Invite Link</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="generatedInviteUrl" readonly style="flex: 1; padding: 8px 10px; font-size: 13px; border: 1px solid var(--border-color, #e2e8f0); border-radius: 6px; background: #ffffff; color: var(--text-primary);" onclick="this.select();">
                        <button type="button" id="btnCopyInviteUrl" class="btn-primary" style="padding: 8px 12px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <i data-lucide="copy" size="14"></i> Copy
                        </button>
                    </div>
                    <span style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">This link expires in 7 days.</span>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="padding: 15px 20px; display: flex; justify-content: flex-end;">
            <button class="btn-primary" id="btnSubmitGenerateInvite" style="width: 100%; justify-content: center; font-weight: 600; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <i data-lucide="sparkles"></i>
                <span>Generate Invite Link</span>
            </button>
        </div>
    </div>
</div>

