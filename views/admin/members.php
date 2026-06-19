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
    <div class="header-actions">
        <button class="btn-primary invite-btn">
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
                <!-- Row 1 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini">MB</div>
                            <div class="member-details">
                                <span class="member-name">Mahad Bukhari</span>
                                <span class="member-email">mahad@example.com</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge admin">Admin</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Mar 09, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 2 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-indigo">JS</div>
                            <div class="member-details">
                                <span class="member-name">John Smith</span>
                                <span class="member-email">john@chatrox.com</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator offline"><span class="dot"></span><span>Offline</span></div></td>
                    <td>Feb 28, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 3 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-pink">SR</div>
                            <div class="member-details">
                                <span class="member-name">Sarah Ross</span>
                                <span class="member-email">sarah.r@work.com</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Jan 15, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 4 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-orange">AL</div>
                            <div class="member-details">
                                <span class="member-name">Alex Lee</span>
                                <span class="member-email">alee@startup.io</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Mar 05, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 5 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-purple">EW</div>
                            <div class="member-details">
                                <span class="member-name">Emma Williams</span>
                                <span class="member-email">emma.w@chatrox.com</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge admin">Admin</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Dec 10, 2025</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 6 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-green">OM</div>
                            <div class="member-details">
                                <span class="member-name">Oliver Mitchell</span>
                                <span class="member-email">oliver.m@workspace.net</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Feb 15, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 7 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-cyan">DC</div>
                            <div class="member-details">
                                <span class="member-name">David Chen</span>
                                <span class="member-email">david.c@tech.org</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator offline"><span class="dot"></span><span>Offline</span></div></td>
                    <td>Jan 20, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 8 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-yellow">SG</div>
                            <div class="member-details">
                                <span class="member-name">Sophia Garcia</span>
                                <span class="member-email">sophia.g@design.co</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Mar 01, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 9 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-red">MB</div>
                            <div class="member-details">
                                <span class="member-name">Michael Brown</span>
                                <span class="member-email">mike.b@corp.com</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator offline"><span class="dot"></span><span>Offline</span></div></td>
                    <td>Feb 10, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 10 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-blue">IW</div>
                            <div class="member-details">
                                <span class="member-name">Isabella White</span>
                                <span class="member-email">isabella.w@chatrox.com</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Dec 22, 2025</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 11 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-emerald">AT</div>
                            <div class="member-details">
                                <span class="member-name">Alex Turner</span>
                                <span class="member-email">alex.t@music.io</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Mar 06, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 12 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-slate">CK</div>
                            <div class="member-details">
                                <span class="member-name">Chloe King</span>
                                <span class="member-email">chloe.k@lifestyle.com</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Jan 30, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 13 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-amber">LH</div>
                            <div class="member-details">
                                <span class="member-name">Liam Hudson</span>
                                <span class="member-email">liam.h@travel.net</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator offline"><span class="dot"></span><span>Offline</span></div></td>
                    <td>Feb 20, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 14 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-lime">NP</div>
                            <div class="member-details">
                                <span class="member-name">Noah Park</span>
                                <span class="member-email">noah.p@dev.org</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Mar 02, 2026</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                <!-- Row 15 -->
                <tr class="member-row">
                    <td>
                        <div class="member-info-cell">
                            <div class="avatar-mini bg-rose">MW</div>
                            <div class="member-details">
                                <span class="member-name">Mia Wilson</span>
                                <span class="member-email">mia.w@chatrox.com</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="role-badge member">Member</span></td>
                    <td><div class="status-indicator active"><span class="dot"></span><span>Active</span></div></td>
                    <td>Dec 15, 2025</td>
                    <td class="text-right">
                        <div class="action-btns">
                            <button class="action-btn edit-member-btn" title="Edit Member"><i data-lucide="edit-2"></i></button>
                            <button class="action-btn delete" title="Remove Member"><i data-lucide="trash-2"></i></button>
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
