<?php
$page_title = 'Activity Log - Chatrox';
include 'header.php';
?>

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
            <!-- Row 1 -->
            <tr class="activity-card-log" data-type="mention">
                <td class="col-time">08/26/26, 4:21 pm</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150" alt="Emma" class="mini-avatar">
                        <div class="member-text"><span class="m-name">Emma Williams</span><span class="m-handle">@emma_w</span></div>
                    </div>
                </td>
                <td class="col-message">Mentioned you in #design-mockups: "Please review the latest dashboard iterations..."</td>
                <td class="col-activity">Mention</td>
                <td class="col-ip">192.168.1.45</td>
            </tr>
            <!-- Row 2 -->
            <tr class="activity-card-log" data-type="file">
                <td class="col-time">08/26/26, 4:01 pm</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150" alt="Oliver" class="mini-avatar">
                        <div class="member-text"><span class="m-name">Oliver Mitchell</span><span class="m-handle">@oliver_m</span></div>
                    </div>
                </td>
                <td class="col-message">Uploaded document: Q3_Strategy_v2.pdf (4.2 MB)</td>
                <td class="col-activity">File Upload</td>
                <td class="col-ip">103.255.44.12</td>
            </tr>
            <!-- Row 3 -->
            <tr class="activity-card-log" data-type="security">
                <td class="col-time">08/26/26, 2:01 pm</td>
                <td class="col-status"><span class="status-badge failed">FAILED</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <div class="mini-avatar system security"><i data-lucide="shield-alert"></i></div>
                        <div class="member-text"><span class="m-name">Security System</span><span class="m-handle">System</span></div>
                    </div>
                </td>
                <td class="col-message">Suspicious login attempt blocked from unauthorized device in London, UK.</td>
                <td class="col-activity">Blocked Login</td>
                <td class="col-ip">45.122.3.1</td>
            </tr>
            <!-- Row 4 -->
            <tr class="activity-card-log" data-type="system">
                <td class="col-time">08/26/26, 1:37 pm</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <div class="mini-avatar system zap"><i data-lucide="zap"></i></div>
                        <div class="member-text"><span class="m-name">System Update</span><span class="m-handle">Auto-Bot</span></div>
                    </div>
                </td>
                <td class="col-message">Deployment of v2.4.0 successfully completed on production environment.</td>
                <td class="col-activity">Deployment</td>
                <td class="col-ip">localhost</td>
            </tr>
            <!-- Row 5 -->
            <tr class="activity-card-log" data-type="security">
                <td class="col-time">Yesterday, 9:20 pm</td>
                <td class="col-status"><span class="status-badge warning">WARNING</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=150" alt="John" class="mini-avatar">
                        <div class="member-text"><span class="m-name">John Smith</span><span class="m-handle">@john_s</span></div>
                    </div>
                </td>
                <td class="col-message">Failed login attempt (Incorrect password) from unrecognized location.</td>
                <td class="col-activity">Security</td>
                <td class="col-ip">110.33.22.4</td>
            </tr>
            <!-- Row 6 -->
            <tr class="activity-card-log" data-type="mention">
                <td class="col-time">Yesterday, 5:45 pm</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=150" alt="Avatar" class="mini-avatar">
                        <div class="member-text"><span class="m-name">David Chen</span><span class="m-handle">@dchen</span></div>
                    </div>
                </td>
                <td class="col-message">Replied to your thread in #marketing: "The campaign analysis looks solid."</td>
                <td class="col-activity">Reply</td>
                <td class="col-ip">172.16.254.1</td>
            </tr>
            <!-- Row 7 -->
            <tr class="activity-card-log" data-type="system">
                <td class="col-time">Yesterday, 11:30 am</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <div class="mini-avatar system zap"><i data-lucide="database"></i></div>
                        <div class="member-text"><span class="m-name">Backup Service</span><span class="m-handle">System</span></div>
                    </div>
                </td>
                <td class="col-message">Weekly database backup completed successfully. (Size: 1.2 GB)</td>
                <td class="col-activity">Maintenance</td>
                <td class="col-ip">10.0.0.5</td>
            </tr>
            <!-- Row 8 -->
            <tr class="activity-card-log" data-type="file">
                <td class="col-time">Aug 24, 6:15 pm</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=150" alt="Avatar" class="mini-avatar">
                        <div class="member-text"><span class="m-name">Sophia Garcia</span><span class="m-handle">@sophia_g</span></div>
                    </div>
                </td>
                <td class="col-message">Deleted file: Old_Project_Plan.docx from shared folder.</td>
                <td class="col-activity">File Action</td>
                <td class="col-ip">185.44.110.3</td>
            </tr>
            <!-- Row 9 -->
            <tr class="activity-card-log" data-type="security">
                <td class="col-time">Aug 24, 2:10 pm</td>
                <td class="col-status"><span class="status-badge warning">WARNING</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <div class="mini-avatar system security"><i data-lucide="shield-check"></i></div>
                        <div class="member-text"><span class="m-name">Access Control</span><span class="m-handle">Security</span></div>
                    </div>
                </td>
                <td class="col-message">User permissions modified for #finance channel by Admin Emma.</td>
                <td class="col-activity">Permission</td>
                <td class="col-ip">192.168.1.10</td>
            </tr>
            <!-- Row 10 -->
            <tr class="activity-card-log" data-type="mention">
                <td class="col-time">Aug 23, 9:00 am</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=150" alt="Avatar" class="mini-avatar">
                        <div class="member-text"><span class="m-name">Michael Brown</span><span class="m-handle">@mbrown</span></div>
                    </div>
                </td>
                <td class="col-message">Added 3 new members to the #development workspace.</td>
                <td class="col-activity">Workspace</td>
                <td class="col-ip">92.10.150.22</td>
            </tr>
            <!-- Row 11 -->
            <tr class="activity-card-log" data-type="file">
                <td class="col-time">Aug 22, 11:45 pm</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=150" alt="Avatar" class="mini-avatar">
                        <div class="member-text"><span class="m-name">Isabella White</span><span class="m-handle">@bella_w</span></div>
                    </div>
                </td>
                <td class="col-message">Downloaded 15 attachments from #general-resources.</td>
                <td class="col-activity">Bulk Action</td>
                <td class="col-ip">88.22.33.44</td>
            </tr>
            <!-- Row 12 -->
            <tr class="activity-card-log" data-type="system">
                <td class="col-time">Aug 22, 4:30 pm</td>
                <td class="col-status"><span class="status-badge failed">FAILED</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <div class="mini-avatar system zap"><i data-lucide="alert-triangle"></i></div>
                        <div class="member-text"><span class="m-name">Email Service</span><span class="m-handle">System</span></div>
                    </div>
                </td>
                <td class="col-message">Failed to send invitation emails to 2 pending members. (Timeout error)</td>
                <td class="col-activity">Notification</td>
                <td class="col-ip">127.0.0.1</td>
            </tr>
             <!-- Row 13 -->
             <tr class="activity-card-log" data-type="mention">
                <td class="col-time">Aug 21, 6:20 pm</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&q=80&w=150" alt="Avatar" class="mini-avatar">
                        <div class="member-text"><span class="m-name">Alex Turner</span><span class="m-handle">@aturner</span></div>
                    </div>
                </td>
                <td class="col-message">Created a new announcement: "Office reopens next week!"</td>
                <td class="col-activity">Announcement</td>
                <td class="col-ip">192.168.5.12</td>
            </tr>
            <!-- Row 14 -->
            <tr class="activity-card-log" data-type="security">
                <td class="col-time">Aug 21, 1:15 pm</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <div class="mini-avatar system security"><i data-lucide="unlock"></i></div>
                        <div class="member-text"><span class="m-name">Account Recovery</span><span class="m-handle">Security</span></div>
                    </div>
                </td>
                <td class="col-message">Password recovery link requested for member @mbrown.</td>
                <td class="col-activity">Access</td>
                <td class="col-ip">10.20.30.40</td>
            </tr>
            <!-- Row 15 -->
            <tr class="activity-card-log" data-type="file">
                <td class="col-time">Aug 20, 10:45 am</td>
                <td class="col-status"><span class="status-badge complete">COMPLETE</span></td>
                <td class="col-member">
                    <div class="member-info-mini">
                        <img src="https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&q=80&w=150" alt="Avatar" class="mini-avatar">
                        <div class="member-text"><span class="m-name">Chloe King</span><span class="m-handle">@chloe_k</span></div>
                    </div>
                </td>
                <td class="col-message">Uploaded image: Website_Mockup_Final.jpg (12.5 MB)</td>
                <td class="col-activity">Media</td>
                <td class="col-ip">172.20.10.5</td>
            </tr>
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

<script src="js/activity.js"></script>
<?php include 'footer.php'; ?>