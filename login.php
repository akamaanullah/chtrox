<?php
$active_tab = 'login';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatRox - Sign In</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="auth-page">

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

            <h1 class="auth-welcome-title">Welcome<br>back!</h1>
            <p class="auth-welcome-text">You can sign in to access with your existing account.</p>

            <div class="shape shape-circle-lg"></div>
            <div class="shape shape-rect-pink"></div>

            <div class="shape shape-squiggle-bottom">
                <svg width="40" height="20" viewBox="0 0 40 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 10C8 2 12 18 20 10C28 2 32 18 38 10" stroke="#94a3b8" stroke-width="2"
                        stroke-linecap="round" />
                </svg>
            </div>

        </div>

        <!-- Right Panel: Login Form -->
        <div class="auth-right">
            <h2 class="auth-form-title">Sign In</h2>
            <p class="auth-form-subtitle">Enter your credentials to access your workspace.</p>

            <form action="index.php" method="GET">
                <div class="auth-group">
                    <label class="auth-label">Username</label>
                    <div class="auth-input-wrapper">
                        <i data-lucide="user" class="auth-input-icon"></i>
                        <input type="text" class="auth-input" placeholder="Johndoe" required>
                    </div>
                </div>

                <div class="auth-group">
                    <label class="auth-label">Password</label>
                    <a href="#" class="auth-forgot">Forgot password?</a>
                    <div class="auth-input-wrapper">
                        <i data-lucide="lock" class="auth-input-icon"></i>
                        <input type="password" class="auth-input" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="auth-submit">
                    Sign In <i data-lucide="arrow-right" style="width: 18px;"></i>
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register" class="auth-link">Create one now</a>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>