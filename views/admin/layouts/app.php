<?php

use App\Core\View;

$pageData = $pageData ?? [];
$active_page = $active_page ?? '';
$nav_sections = $nav_sections ?? [];
$page_title = $page_title ?? '';
$page_scripts = $page_scripts ?? [];
$contentView = $contentView ?? '';

$sharedData = array_merge($pageData, [
    'active_page' => $active_page,
    'nav_sections' => $nav_sections,
    'page_title' => $page_title,
]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ChatRox Admin — Manage members, channels, analytics and workspace settings.">
    <title><?php echo View::e($page_title); ?></title>
    <meta name="csrf-token" content="<?php echo \App\Core\Session::csrfToken(); ?>">
    <link rel="icon" type="image/png" href="<?php echo View::asset('assets/images/logo.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo View::asset('assets/images/logo.png'); ?>">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" id="metaThemeColor" content="#4f46e5">
    <script>
        window.CHATROX_ADMIN = {
            baseUrl: '<?php echo BASE_URL; ?>',
            apiUrl: '<?php echo BASE_URL; ?>/api/admin',
            csrfToken: '<?php echo \App\Core\Session::csrfToken(); ?>',
            wsPort: '<?php echo WS_PORT; ?>'
        };
    </script>
    <script src="<?php echo View::asset('js/themes-shared.js'); ?>"></script>
    <script>if (window.ChatroxTheme) { ChatroxTheme.applySaved(); }</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.468.0" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@4.7.0" defer></script>
    <link rel="stylesheet" href="<?php echo View::adminAsset('css/style.css'); ?>">
</head>

<body>
    <div class="dashboard-wrapper">
        <?php View::renderAdmin('partials/sidebar.php', $sharedData); ?>
        <main class="main-content">
            <?php if (View::adminExists($contentView . '.php')): ?>
                <?php View::renderAdmin($contentView . '.php', $sharedData); ?>
            <?php else: ?>
                <div class="content-inner error-not-found">
                    <h1>404 — Page Not Found</h1>
                    <p>The requested dashboard page does not exist.</p>
                    <a href="<?php echo View::adminUrl('home'); ?>" class="btn-dark">Back to Dashboard</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php foreach ($page_scripts as $script): ?>
        <script src="<?php echo View::adminAsset($script); ?>" defer></script>
    <?php endforeach; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) { lucide.createIcons(); }
            if (window.ChatroxTheme) { ChatroxTheme.init(); }
        });
    </script>
    <script>
        (function () {
            // Global listener for image loading errors (capturing phase to catch non-bubbling 'error' events on IMG tags)
            window.addEventListener('error', function (e) {
                if (!e.target || e.target.tagName !== 'IMG') return;
                var img = e.target;

                // 1. Avatars
                if (img.classList.contains('avatar') || img.classList.contains('member-avatar') || img.src.indexOf('avatar') !== -1) {
                    img.onerror = null;
                    img.src = (window.CHATROX_ADMIN ? window.CHATROX_ADMIN.baseUrl : '') + '/assets/images/default-avatar.svg';
                    return;
                }

                // 2. Admin File Card Previews
                if (img.classList.contains('file-thumbnail') || img.closest('.media-item')) {
                    var parent = img.parentElement;
                    if (!parent) return;
                    var placeholder = document.createElement('div');
                    placeholder.style.display = 'flex';
                    placeholder.style.flexDirection = 'column';
                    placeholder.style.alignItems = 'center';
                    placeholder.style.justifyContent = 'center';
                    placeholder.style.background = '#f1f5f9';
                    placeholder.style.border = '1px dashed #cbd5e1';
                    placeholder.style.color = '#94a3b8';
                    placeholder.style.width = '100%';
                    placeholder.style.height = '100px';
                    placeholder.innerHTML = '<i data-lucide="image-off" size="24"></i><span style="font-size: 10px; margin-top: 4px;">No image found</span>';
                    parent.replaceChild(placeholder, img);
                    if (window.lucide) window.lucide.createIcons({ nodes: [placeholder] });
                    return;
                }
            }, true);
        })();
    </script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo BASE_URL; ?>/service-worker.js')
                    .then(function(registration) {
                        console.log('ChatRox Admin ServiceWorker registered successfully with scope: ', registration.scope);
                    })
                    .catch(function(err) {
                        console.log('ChatRox Admin ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script>
</body>

</html>
