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
        <button class="btn-save" id="profileSaveBtn">Save Changes</button>
    </div>
</header>

<div class="profile-container">
    <!-- Profile Card -->
    <form id="profileForm" class="profile-card" enctype="multipart/form-data">
        <div class="profile-header">
            <div class="profile-avatar-large" id="profileAvatarLarge">
                <div class="profile-avatar-media">
                    <span class="profile-avatar-initial" id="profileAvatarInitial" style="<?php echo $profile_user['avatar_path'] ? 'display: none;' : ''; ?>">
                        <?php echo substr(strtoupper($profile_user['first_name'] ?: 'C'), 0, 1); ?>
                    </span>
                    <img src="<?php echo $profile_user['avatar_path'] ? \App\Core\View::e($profile_user['avatar_path']) : ''; ?>" alt="Profile" class="profile-avatar-img <?php echo $profile_user['avatar_path'] ? '' : 'profile-avatar-img--hidden'; ?>" id="profileAvatarImg" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </div>
                <label class="edit-avatar" for="profileAvatarInput" title="Change photo">
                    <i data-lucide="camera" size="14"></i>
                </label>
                <input type="file" id="profileAvatarInput" name="avatar" accept="image/*" hidden>
            </div>
            <div class="profile-title">
                <h2><?php echo \App\Core\View::e($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?></h2>
                <p><?php echo ucfirst(\App\Core\View::e($member['role'] ?? 'Administrator')); ?> • @<?php echo \App\Core\View::e($profile_user['username']); ?></p>
            </div>
        </div>

        <div class="profile-sections">
            <!-- Admin Personal Info -->
            <div class="profile-section collapsible">
                <div class="section-header">
                    <h3 class="section-subtitle">Admin Information</h3>
                    <i data-lucide="chevron-down" class="toggle-icon"></i>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo \App\Core\View::e($profile_user['first_name']); ?>" placeholder="Enter first name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo \App\Core\View::e($profile_user['last_name']); ?>" placeholder="Enter last name" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo \App\Core\View::e($profile_user['username']); ?>" placeholder="Choose a username" required>
                        </div>
                        <div class="form-group">
                            <label>Personal Email</label>
                            <input type="email" name="personal_email" value="<?php echo \App\Core\View::e($profile_user['email']); ?>" placeholder="Enter personal email" required>
                        </div>
                        <div class="form-group">
                            <label>Personal Phone</label>
                            <input type="tel" name="personal_phone" value="<?php echo \App\Core\View::e($profile_user['phone'] ?? ''); ?>" placeholder="Enter phone number">
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
                            <input type="text" name="company_name" value="<?php echo \App\Core\View::e($workspace['name']); ?>" placeholder="Enter company name">
                        </div>
                        <div class="form-group">
                            <label>Industry</label>
                            <select name="industry">
                                <?php
                                $industries = ['technology' => 'Technology', 'healthcare' => 'Healthcare', 'finance' => 'Finance', 'education' => 'Education', 'manufacturing' => 'Manufacturing', 'retail' => 'Retail', 'services' => 'Services', 'other' => 'Other'];
                                foreach ($industries as $val => $lbl): ?>
                                    <option value="<?php echo $val; ?>" <?php echo $workspace['industry'] === $val ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Organization Type</label>
                            <select name="org_type">
                                <?php
                                $orgTypes = ['corporation' => 'Corporation', 'llc' => 'LLC', 'partnership' => 'Partnership', 'sole_proprietorship' => 'Sole Proprietorship', 'non_profit' => 'Non-Profit', 'other' => 'Other'];
                                foreach ($orgTypes as $val => $lbl): ?>
                                    <option value="<?php echo $val; ?>" <?php echo $workspace['organization_type'] === $val ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Company Email</label>
                            <input type="email" name="company_email" value="<?php echo \App\Core\View::e($workspace['email'] ?? ''); ?>" placeholder="Enter company email">
                        </div>
                        <div class="form-group">
                            <label>Company Phone</label>
                            <input type="tel" name="company_phone" value="<?php echo \App\Core\View::e($workspace['phone'] ?? ''); ?>" placeholder="Enter company phone">
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
                            <input type="text" name="address" value="<?php echo \App\Core\View::e($address['address_line1'] ?? ''); ?>" placeholder="Enter address">
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?php echo \App\Core\View::e($address['city'] ?? ''); ?>" placeholder="Enter city">
                        </div>
                        <div class="form-group">
                            <label>State (Optional)</label>
                            <input type="text" name="state" value="<?php echo \App\Core\View::e($address['state'] ?? ''); ?>" placeholder="Enter state">
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" value="<?php echo \App\Core\View::e($address['country'] ?? ''); ?>" placeholder="Enter country">
                        </div>
                        <div class="form-group">
                            <label>ZIP Code (Optional)</label>
                            <input type="text" name="zip_code" value="<?php echo \App\Core\View::e($address['postal_code'] ?? ''); ?>" placeholder="Enter ZIP code">
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
                        <div class="security-item password-update-block" style="flex-direction: column; align-items: stretch; gap: 16px; width: 100%;">
                            <div class="item-info" style="margin-bottom: 8px;">
                                <div class="item-icon"><i data-lucide="lock"></i></div>
                                <div class="item-text">
                                    <h4>Update Password</h4>
                                    <p>Change your password periodically to keep your account secure.</p>
                                </div>
                            </div>
                            <div class="form-grid" style="grid-template-columns: repeat(2, 1fr); gap: 16px; width: 100%;">
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" placeholder="Enter new password">
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <input type="password" name="confirm_password" placeholder="Confirm new password">
                                </div>
                            </div>
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
                                <input type="checkbox" id="2fa-toggle" name="two_factor_enabled" value="1" <?php echo !empty($security['two_factor_enabled']) ? 'checked' : ''; ?>>
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
                        <?php
                        $theme_colors = [
                            'indigo' => 'Indigo',
                            'blue' => 'Blue',
                            'violet' => 'Violet',
                            'emerald' => 'Emerald',
                            'rose' => 'Rose',
                            'sky' => 'Sky',
                            'teal' => 'Teal',
                            'amber' => 'Amber',
                            'cyan' => 'Cyan',
                            'fuchsia' => 'Fuchsia',
                            'lime' => 'Lime',
                            'orange' => 'Orange',
                        ];
                        foreach ($theme_colors as $slug => $label): ?>
                            <label class="color-option">
                                <input type="radio" name="theme_color" value="<?php echo $slug; ?>" <?php echo ($preferences['theme_color'] ?? 'indigo') === $slug ? 'checked' : ''; ?>>
                                <span class="color-dot <?php echo $slug; ?>"></span>
                                <span class="color-label"><?php echo $label; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Profile Sidebar Info -->
    <div class="profile-info-aside">
        <div class="info-card">
            <h3>Account Status</h3>
            <div class="status-badge active">Verified Account</div>
            <p class="info-date">Member since <?php echo date('F Y', strtotime($member['joined_at'] ?? $profile_user['created_at'])); ?></p>
        </div>
        <div class="info-card">
            <h3>Recent Online Activity</h3>
            <ul class="activity-list">
                <?php if (empty($sessions)): ?>
                    <li style="color: var(--text-slate); font-size: 13px;">No recent sessions found</li>
                <?php else: ?>
                    <?php foreach ($sessions as $sess): ?>
                        <?php
                        $ua = $sess['user_agent'] ?: 'Unknown Browser';
                        $icon = 'monitor';
                        if (stripos($ua, 'mobile') !== false || stripos($ua, 'phone') !== false || stripos($ua, 'android') !== false || stripos($ua, 'iphone') !== false) {
                            $icon = 'smartphone';
                        }
                        ?>
                        <li>
                            <div class="activity-icon <?php echo $icon === 'monitor' ? 'blue' : ''; ?>"><i data-lucide="<?php echo $icon; ?>"></i></div>
                            <div class="activity-details">
                                <span class="device" title="<?php echo \App\Core\View::e($ua); ?>"><?php echo \App\Core\View::e(substr($ua, 0, 30)); ?><?php echo strlen($ua) > 30 ? '...' : ''; ?></span>
                                <span class="time"><?php echo date('m/d/y, g:i a', strtotime($sess['created_at'])); ?> (IP: <?php echo \App\Core\View::e($sess['ip_address']); ?>)</span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
