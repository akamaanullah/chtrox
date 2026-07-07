<?php

use App\Core\View;

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
    <script>
        window.CHATROX_ADMIN = {
            baseUrl: '<?php echo BASE_URL; ?>',
            apiUrl: '<?php echo BASE_URL; ?>/api/admin',
            csrfToken: '<?php echo \App\Core\Session::csrfToken(); ?>'
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
            lucide.createIcons();
            if (window.ChatroxTheme) { ChatroxTheme.init(); }
        });
    </script>
</body>

</html>
