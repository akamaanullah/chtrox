<?php

use App\Core\View;

/** @var string $error_code */
/** @var string $error_title */
/** @var string $error_description */
/** @var string $error_detail */
/** @var string $error_icon */

$error_code = $error_code ?? '404';
$error_title = $error_title ?? 'Something went wrong';
$error_description = $error_description ?? 'An unexpected error occurred.';
$error_detail = $error_detail ?? '';
$error_icon = $error_icon ?? 'alert-circle';
$page_title = $error_code . ' — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo View::e($page_title); ?></title>
    <link rel="icon" type="image/png" href="<?php echo View::asset('assets/images/logo.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo View::asset('assets/images/logo.png'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo View::asset('css/style.css'); ?>">
    <script src="https://unpkg.com/lucide@0.468.0" defer></script>
    <script>
        (function () {
            const savedTheme = localStorage.getItem('chatrox_theme');
            if (!savedTheme) return;
            const themes = {
                indigo: { '--indigo-50': '#eef2ff', '--indigo-100': '#e0e7ff', '--indigo-200': '#c7d2fe', '--indigo-300': '#a5b4fc', '--indigo-400': '#818cf8', '--indigo-500': '#6366f1', '--indigo-600': '#4f46e5', '--indigo-700': '#4338ca', '--indigo-800': '#3730a3', '--indigo-900': '#312e81', '--indigo-950': '#1e1b4b' },
                blue: { '--indigo-50': '#eff6ff', '--indigo-100': '#dbeafe', '--indigo-200': '#bfdbfe', '--indigo-300': '#93c5fd', '--indigo-400': '#60a5fa', '--indigo-500': '#3b82f6', '--indigo-600': '#2563eb', '--indigo-700': '#1d4ed8', '--indigo-800': '#1e40af', '--indigo-900': '#1e3a8a', '--indigo-950': '#172554' },
                violet: { '--indigo-50': '#f5f3ff', '--indigo-100': '#ede9fe', '--indigo-200': '#ddd6fe', '--indigo-300': '#c4b5fd', '--indigo-400': '#a78bfa', '--indigo-500': '#8b5cf6', '--indigo-600': '#7c3aed', '--indigo-700': '#6d28d9', '--indigo-800': '#5b21b6', '--indigo-900': '#4c1d95', '--indigo-950': '#2e1065' },
                emerald: { '--indigo-50': '#ecfdf5', '--indigo-100': '#d1fae5', '--indigo-200': '#a7f3d0', '--indigo-300': '#6ee7b7', '--indigo-400': '#34d399', '--indigo-500': '#10b981', '--indigo-600': '#059669', '--indigo-700': '#047857', '--indigo-800': '#065f46', '--indigo-900': '#064e3b', '--indigo-950': '#022c22' },
                rose: { '--indigo-50': '#fff1f2', '--indigo-100': '#ffe4e6', '--indigo-200': '#fecdd3', '--indigo-300': '#fda4af', '--indigo-400': '#fb7185', '--indigo-500': '#f43f5e', '--indigo-600': '#e11d48', '--indigo-700': '#be123c', '--indigo-800': '#9f1239', '--indigo-900': '#881337', '--indigo-950': '#4c0519' },
                sky: { '--indigo-50': '#f0f9ff', '--indigo-100': '#e0f2fe', '--indigo-200': '#bae6fd', '--indigo-300': '#7dd3fc', '--indigo-400': '#38bdf8', '--indigo-500': '#0ea5e9', '--indigo-600': '#0284c7', '--indigo-700': '#0369a1', '--indigo-800': '#075985', '--indigo-900': '#0c4a6e', '--indigo-950': '#082f49' },
                teal: { '--indigo-50': '#f0fdfa', '--indigo-100': '#ccfbf1', '--indigo-200': '#99f6e4', '--indigo-300': '#5eead4', '--indigo-400': '#2dd4bf', '--indigo-500': '#14b8a6', '--indigo-600': '#0d9488', '--indigo-700': '#0f766e', '--indigo-800': '#115e59', '--indigo-900': '#134e4a', '--indigo-950': '#042f2e' },
                amber: { '--indigo-50': '#fffbeb', '--indigo-100': '#fef3c7', '--indigo-200': '#fde68a', '--indigo-300': '#fcd34d', '--indigo-400': '#fbbf24', '--indigo-500': '#f59e0b', '--indigo-600': '#d97706', '--indigo-700': '#b45309', '--indigo-800': '#92400e', '--indigo-900': '#78350f', '--indigo-950': '#451a03' },
                cyan: { '--indigo-50': '#ecfeff', '--indigo-100': '#cffafe', '--indigo-200': '#a5f3fc', '--indigo-300': '#67e8f9', '--indigo-400': '#22d3ee', '--indigo-500': '#06b6d4', '--indigo-600': '#0891b2', '--indigo-700': '#0e7490', '--indigo-800': '#155e75', '--indigo-900': '#164e63', '--indigo-950': '#083344' },
                fuchsia: { '--indigo-50': '#fdf4ff', '--indigo-100': '#fae8ff', '--indigo-200': '#f5d0fe', '--indigo-300': '#f0abfc', '--indigo-400': '#e879f9', '--indigo-500': '#d946ef', '--indigo-600': '#c026d3', '--indigo-700': '#a21caf', '--indigo-800': '#86198f', '--indigo-900': '#701a75', '--indigo-950': '#4a044e' },
                lime: { '--indigo-50': '#f7fee7', '--indigo-100': '#ecfccb', '--indigo-200': '#d9f99d', '--indigo-300': '#bef264', '--indigo-400': '#a3e635', '--indigo-500': '#84cc16', '--indigo-600': '#65a30d', '--indigo-700': '#4d7c0f', '--indigo-800': '#3f6212', '--indigo-900': '#365314', '--indigo-950': '#1a2e05' },
                orange: { '--indigo-50': '#fff7ed', '--indigo-100': '#ffedd5', '--indigo-200': '#fed7aa', '--indigo-300': '#fdba74', '--indigo-400': '#fb923c', '--indigo-500': '#f97316', '--indigo-600': '#ea580c', '--indigo-700': '#c2410c', '--indigo-800': '#9a3412', '--indigo-900': '#7c2d12', '--indigo-950': '#431407' }
            };
            const colors = themes[savedTheme];
            if (colors) {
                for (const [key, value] of Object.entries(colors)) {
                    document.documentElement.style.setProperty(key, value);
                }
            }
        })();
    </script>
</head>

<body class="error-page">
    <div class="error-page__bg" aria-hidden="true">
        <div class="error-page__orb error-page__orb--1"></div>
        <div class="error-page__orb error-page__orb--2"></div>
    </div>

    <main class="error-card" role="main">
        <a href="<?php echo View::url('home'); ?>" class="error-card__brand" title="<?php echo View::e(APP_NAME); ?>">
            <img src="<?php echo View::asset('assets/images/logo.png'); ?>" alt="<?php echo View::e(APP_NAME); ?>" width="48" height="48">
            <span><?php echo View::e(APP_NAME); ?></span>
        </a>

        <div class="error-card__icon-wrap error-card__icon-wrap--<?php echo View::e($error_code); ?>">
            <i data-lucide="<?php echo View::e($error_icon); ?>" aria-hidden="true"></i>
        </div>

        <p class="error-card__code"><?php echo View::e($error_code); ?></p>
        <h1 class="error-card__title"><?php echo View::e($error_title); ?></h1>
        <p class="error-card__description"><?php echo View::e($error_description); ?></p>

        <?php if ($error_detail !== ''): ?>
            <div class="error-card__detail" role="status">
                <span class="error-card__detail-label">Details</span>
                <code><?php echo View::e($error_detail); ?></code>
            </div>
        <?php endif; ?>

        <div class="error-card__actions">
            <a href="<?php echo View::url('home'); ?>" class="error-btn error-btn--primary">
                <i data-lucide="home" aria-hidden="true"></i>
                Back to Home
            </a>
            <a href="<?php echo View::url('login'); ?>" class="error-btn error-btn--secondary">
                <i data-lucide="log-in" aria-hidden="true"></i>
                Sign In
            </a>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>

</html>
