<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatRox Admin - Sign In</title>
    <link rel="icon" type="image/png" href="<?= \App\Core\View::asset('assets/images/logo.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= \App\Core\View::asset('css/style.css') ?>">
    <script src="https://unpkg.com/lucide@0.468.0" defer></script>
</head>

<body class="auth-page">
    <div class="auth-card">
        <div class="auth-left">
            <div class="shape shape-yellow"></div>
            <div class="shape shape-pink"></div>
            <div class="shape shape-circle-sm"></div>

            <div class="shape shape-squiggle">
                <svg width="60" height="30" viewBox="0 0 60 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 15C10 5 15 25 25 15C35 5 40 25 58 15" stroke="#94a3b8" stroke-width="3"
                        stroke-linecap="round" />
                </svg>
            </div>

            <h1 class="auth-welcome-title">Admin<br>Portal</h1>
            <p class="auth-welcome-text">Sign in to manage your ChatRox workspace.</p>

            <div class="shape shape-circle-lg"></div>
            <div class="shape shape-rect-pink"></div>
        </div>

        <div class="auth-right">
            <h2 class="auth-form-title">Admin Sign In</h2>
            <p class="auth-form-subtitle">Enter your admin credentials to continue.</p>

            <?php if (!empty($error)): ?>
                <div class="auth-error"><?= \App\Core\View::e($error) ?></div>
            <?php endif; ?>

            <form action="<?= \App\Core\View::adminUrl('login') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="auth-group">
                    <label class="auth-label" for="username">Username</label>
                    <div class="auth-input-wrapper">
                        <i data-lucide="user" class="auth-input-icon"></i>
                        <input type="text" id="username" name="username" class="auth-input" placeholder="Admin username" required autocomplete="username">
                    </div>
                </div>

                <div class="auth-group">
                    <label class="auth-label" for="password">Password</label>
                    <div class="auth-input-wrapper auth-input-wrapper--toggle">
                        <i data-lucide="lock" class="auth-input-icon"></i>
                        <input type="password" id="password" name="password" class="auth-input" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="auth-password-toggle" aria-label="Show password" aria-pressed="false">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-submit">
                    Sign In <i data-lucide="arrow-right" style="width: 18px;"></i>
                </button>
            </form>

            <div class="auth-footer">
                <a href="<?= \App\Core\View::url('login') ?>" class="auth-link">Back to ChatRox app</a>
            </div>
        </div>
    </div>

    <script src="<?= \App\Core\View::asset('js/auth.js') ?>" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) { lucide.createIcons(); }
        });
    </script>
</body>

</html>
