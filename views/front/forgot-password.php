<div class="auth-card">
    <!-- Left Panel: Welcome Branding -->
    <div class="auth-left">
        <!-- Decorative Shapes -->
        <div class="shape shape-yellow"></div>
        <div class="shape shape-pink"></div>
        <div class="shape shape-circle-sm"></div>

        <div class="shape shape-squiggle">
            <svg width="60" height="30" viewBox="0 0 60 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 15C10 5 15 25 25 15C35 5 40 25 58 15" stroke="#94a3b8" stroke-width="3"
                    stroke-linecap="round" />
            </svg>
        </div>

        <h1 class="auth-welcome-title">Reset<br>Password</h1>
        <p class="auth-welcome-text">Submit a password reset request to the system administrator.</p>

        <div class="shape shape-circle-lg"></div>
        <div class="shape shape-rect-pink"></div>

        <div class="shape shape-squiggle-bottom">
            <svg width="40" height="20" viewBox="0 0 40 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 10C8 2 12 18 20 10C28 2 32 18 38 10" stroke="#94a3b8" stroke-width="2"
                    stroke-linecap="round" />
            </svg>
        </div>

    </div>

    <!-- Right Panel: Forgot Password Form -->
    <div class="auth-right">
        <h2 class="auth-form-title">Forgot Password</h2>
        <p class="auth-form-subtitle">Enter your username or email address below.</p>

        <?php if (!empty($error)): ?>
            <div class="auth-error"><?php echo \App\Core\View::e($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="background: #dcfce7; color: #15803d; border-radius: 12px; padding: 16px; margin-bottom: 24px; font-size: 14px; font-weight: 500; line-height: 1.5; border: 1px solid #bbf7d0;">
                <?php echo \App\Core\View::e($success); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo \App\Core\View::url('forgot-password'); ?>" method="POST">
            <?php echo \App\Core\Session::csrfField(); ?>
            <div class="auth-group">
                <label class="auth-label">Username or Email</label>
                <div class="auth-input-wrapper">
                    <i data-lucide="mail" class="auth-input-icon"></i>
                    <input type="text" name="identity" class="auth-input" placeholder="Enter username or email" required>
                </div>
            </div>

            <button type="submit" class="auth-submit">
                Send Request <i data-lucide="send" style="width: 18px; margin-left: 8px;"></i>
            </button>
        </form>

        <div class="auth-footer">
            Remember your password? <a href="login" class="auth-link">Sign In</a>
        </div>
    </div>
</div>
