<div class="auth-card" style="max-width: 900px;">
    <!-- Left Panel: Welcome Branding -->
    <div class="auth-left">
        <div class="shape shape-yellow"></div>
        <div class="shape shape-pink"></div>
        <div class="shape shape-circle-sm"></div>

        <div class="shape shape-squiggle">
            <svg width="60" height="30" viewBox="0 0 60 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 15C10 5 15 25 25 15C35 5 40 25 58 15" stroke="#94a3b8" stroke-width="3" stroke-linecap="round" />
            </svg>
        </div>

        <h1 class="auth-welcome-title" style="font-size: 36px; line-height: 1.2;">Workspace<br>Invitation</h1>
        <p class="auth-welcome-text">You have been invited to join the <strong><?php echo \App\Core\View::e($workspaceName); ?></strong> workspace on ChatRox.</p>

        <div class="shape shape-circle-lg"></div>
        <div class="shape shape-rect-pink"></div>
    </div>

    <!-- Right Panel: Invite Registration Form -->
    <div class="auth-right" style="padding: 40px 48px;">
        <h2 class="auth-form-title">Join Workspace</h2>
        <p class="auth-form-subtitle">Create your personal account to get started.</p>

        <?php if (!empty($error)): ?>
            <div class="auth-error" style="color: #ef4444; background-color: #fef2f2; border: 1px solid #fee2e2; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                <?php echo \App\Core\View::e($error); ?>
            </div>
        <?php endif; ?>

        <form id="inviteRegisterForm" action="" method="POST">
            <?php echo \App\Core\Session::csrfField(); ?>
            <div class="auth-grid">
                <div class="auth-group">
                    <label class="auth-label">First Name *</label>
                    <input type="text" name="first_name" class="auth-input" style="padding-left: 20px;" placeholder="John" value="<?php echo \App\Core\View::e($_POST['first_name'] ?? ''); ?>" required>
                </div>
                <div class="auth-group">
                    <label class="auth-label">Last Name *</label>
                    <input type="text" name="last_name" class="auth-input" style="padding-left: 20px;" placeholder="Doe" value="<?php echo \App\Core\View::e($_POST['last_name'] ?? ''); ?>" required>
                </div>
                <div class="auth-group auth-grid-full">
                    <label class="auth-label">Username *</label>
                    <input type="text" name="username" class="auth-input" style="padding-left: 20px;" placeholder="johndoe123" value="<?php echo \App\Core\View::e($_POST['username'] ?? ''); ?>" required>
                </div>
                <div class="auth-group">
                    <label class="auth-label">Email Address *</label>
                    <input type="email" name="email" class="auth-input" style="padding-left: 20px;" placeholder="john@company.com" value="<?php echo \App\Core\View::e($email); ?>" <?php echo $isGeneric ? '' : 'readonly style="background-color: #f8fafc;"'; ?> required>
                </div>
                <div class="auth-group">
                    <label class="auth-label">Phone Number *</label>
                    <input type="tel" name="phone" class="auth-input" style="padding-left: 20px;" placeholder="+1 987 654 321" value="<?php echo \App\Core\View::e($_POST['phone'] ?? ''); ?>" required>
                </div>
                <div class="auth-group">
                    <label class="auth-label">Password *</label>
                    <div class="auth-input-wrapper auth-input-wrapper--toggle">
                        <input type="password" name="password" class="auth-input auth-input--plain" placeholder="••••••••" required>
                        <button type="button" class="auth-password-toggle" aria-label="Show password" aria-pressed="false">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </div>
                <div class="auth-group">
                    <label class="auth-label">Confirm Password *</label>
                    <div class="auth-input-wrapper auth-input-wrapper--toggle">
                        <input type="password" name="confirm_password" class="auth-input auth-input--plain" placeholder="••••••••" required>
                        <button type="button" class="auth-password-toggle" aria-label="Show password" aria-pressed="false">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="auth-nav-btns" style="margin-top: 24px;">
                <button type="submit" class="auth-submit" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span>Join <?php echo \App\Core\View::e($workspaceName); ?></span>
                    <i data-lucide="arrow-right" style="width: 18px;"></i>
                </button>
            </div>
        </form>

        <div class="auth-footer" style="margin-top: 24px;">
            Already have an account? <a href="<?php echo \App\Core\View::url('login'); ?>" class="auth-link">Sign In instead</a>
        </div>
    </div>
</div>
