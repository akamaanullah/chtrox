<?php
$page_title = 'Account & Profile - Chatrox';
include 'header.php';
?>

<header class="content-header">
    <div class="greeting-area">
        <div class="greeting-icon">
            <i data-lucide="user"></i>
        </div>
        <div class="greeting-text">
            <h1>Account & Profile</h1>
            <p class="date">Manage your personal information and security settings.</p>
        </div>
    </div>
    <div class="header-actions">
        <button class="btn-save">Save Changes</button>
    </div>
</header>

<div class="profile-container">
    <!-- Profile Card -->
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar-large">
                <span>C</span>
                <button class="edit-avatar"><i data-lucide="camera"></i></button>
            </div>
            <div class="profile-title">
                <h2>ChatroxAdmin</h2>
                <p>Administrator • @chatrox_admin</p>
            </div>
        </div>

        <div class="profile-sections">
            <!-- Admin Personal Info -->
            <div class="profile-section collapsible collapsed">
                <div class="section-header">
                    <h3 class="section-subtitle">Admin Information</h3>
                    <i data-lucide="chevron-down" class="toggle-icon"></i>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" value="Chatrox" placeholder="Enter first name">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" value="Admin" placeholder="Enter last name">
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="chatrox_admin" placeholder="Choose a username">
                        </div>
                        <div class="form-group">
                            <label>Personal Email</label>
                            <input type="email" value="admin@chatrox.com" placeholder="Enter personal email">
                        </div>
                        <div class="form-group">
                            <label>Personal Phone</label>
                            <input type="tel" value="+1 (555) 000-0000" placeholder="Enter phone number">
                        </div>
                    </div>
                </div>
            </div>

            <hr class="divider">

            <!-- Company Information -->
            <div class="profile-section collapsible collapsed">
                <div class="section-header">
                    <h3 class="section-subtitle">Company Details</h3>
                    <i data-lucide="chevron-down" class="toggle-icon"></i>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" value="Nexus Tech Inc." placeholder="Enter company name">
                        </div>
                        <div class="form-group">
                            <label>Industry</label>
                            <select>
                                <option value="technology" selected>Technology</option>
                                <option value="healthcare">Healthcare</option>
                                <option value="finance">Finance</option>
                                <option value="education">Education</option>
                                <option value="manufacturing">Manufacturing</option>
                                <option value="retail">Retail</option>
                                <option value="services">Services</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Organization Type</label>
                            <select>
                                <option value="corporation" selected>Corporation</option>
                                <option value="llc">LLC</option>
                                <option value="partnership">Partnership</option>
                                <option value="sole_proprietorship">Sole Proprietorship</option>
                                <option value="non_profit">Non-Profit</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Company Email</label>
                            <input type="email" value="contact@nexus.com" placeholder="Enter company email">
                        </div>
                        <div class="form-group">
                            <label>Company Phone</label>
                            <input type="tel" value="+1 234 567 890" placeholder="Enter company phone">
                        </div>
                    </div>
                </div>
            </div>

            <hr class="divider">

            <!-- Location Details -->
            <div class="profile-section collapsible collapsed">
                <div class="section-header">
                    <h3 class="section-subtitle">Location & Address</h3>
                    <i data-lucide="chevron-down" class="toggle-icon"></i>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Address</label>
                            <input type="text" value="123 Silicon Valley Road" placeholder="Enter address">
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" value="San Francisco" placeholder="Enter city">
                        </div>
                        <div class="form-group">
                            <label>State (Optional)</label>
                            <input type="text" value="California" placeholder="Enter state">
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" value="United States" placeholder="Enter country">
                        </div>
                        <div class="form-group">
                            <label>ZIP Code (Optional)</label>
                            <input type="text" value="94103" placeholder="Enter ZIP code">
                        </div>
                    </div>
                </div>
            </div>

            <hr class="divider">

            <!-- Security -->
            <div class="profile-section collapsible collapsed">
                <div class="section-header">
                    <h3 class="section-subtitle">Security Settings</h3>
                    <i data-lucide="chevron-down" class="toggle-icon"></i>
                </div>
                <div class="section-content">
                    <div class="security-items">
                        <div class="security-item">
                            <div class="item-info">
                                <div class="item-icon"><i data-lucide="lock"></i></div>
                                <div class="item-text">
                                    <h4>Update Password</h4>
                                    <p>Change your password periodically to keep your account secure.</p>
                                </div>
                            </div>
                            <button class="btn-outline">Change Password</button>
                        </div>
                        <div class="security-item">
                            <div class="item-info">
                                <div class="item-icon"><i data-lucide="shield-check"></i></div>
                                <div class="item-text">
                                    <h4>Two-Factor Authentication</h4>
                                    <p>Add an extra layer of security to your account.</p>
                                </div>
                            </div>
                            <div class="toggle-switch">
                                <input type="checkbox" id="2fa-toggle" checked>
                                <label for="2fa-toggle"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="divider">

            <!-- Theme Color -->
            <div class="profile-section collapsible collapsed">
                <div class="section-header">
                    <h3 class="section-subtitle">Theme Color</h3>
                    <i data-lucide="chevron-down" class="toggle-icon"></i>
                </div>
                <div class="section-content">
                    <div class="color-grid">
                        <div class="color-option">
                            <span class="color-dot indigo"></span>
                            <span class="color-label">Indigo</span>
                        </div>
                        <div class="color-option">
                            <span class="color-dot blue"></span>
                            <span class="color-label">Blue</span>
                        </div>
                        <div class="color-option">
                            <span class="color-dot violet"></span>
                            <span class="color-label">Violet</span>
                        </div>
                        <div class="color-option">
                            <span class="color-dot emerald"></span>
                            <span class="color-label">Emerald</span>
                        </div>
                        <div class="color-option">
                            <span class="color-dot rose"></span>
                            <span class="color-label">Rose</span>
                        </div>
                        <div class="color-option">
                            <span class="color-dot sky"></span>
                            <span class="color-label">Sky</span>
                        </div>
                        <div class="color-option">
                            <span class="color-dot teal"></span>
                            <span class="color-label">Teal</span>
                        </div>
                        <div class="color-option">
                            <span class="color-dot amber"></span>
                            <span class="color-label">Amber</span>
                        </div>
                        <div class="color-option">
                            <span class="color-dot cyan"></span>
                            <span class="color-label">Cyan</span>
                        </div>
                        <div class="color-option">
                            <span class="color-dot fuchsia"></span>
                            <span class="color-label">Fuchsia</span>
                        </div>
                        <div class="color-option active">
                            <span class="color-dot lime"></span>
                            <span class="color-label">Lime</span>
                        </div>
                        <div class="color-option">
                            <span class="color-dot orange"></span>
                            <span class="color-label">Orange</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Sidebar Info -->
    <div class="profile-info-aside">
        <div class="info-card">
            <h3>Account Status</h3>
            <div class="status-badge active">Verified Account</div>
            <p class="info-date">Member since July 2024</p>
        </div>
        <div class="info-card">
            <h3>Recent Online Activity</h3>
            <ul class="activity-list">
                <li>
                    <div class="activity-icon blue"><i data-lucide="monitor"></i></div>
                    <div class="activity-details">
                        <span class="device">Chrome on Windows</span>
                        <span class="time">Active Now</span>
                    </div>
                </li>
                <li>
                    <div class="activity-icon"><i data-lucide="smartphone"></i></div>
                    <div class="activity-details">
                        <span class="device">Chatrox Mobile App</span>
                        <span class="time">2 hours ago</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>