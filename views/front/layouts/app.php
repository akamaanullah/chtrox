<?php

use App\Core\View;

$pageData = $pageData ?? [];
$active_tab = $active_tab ?? '';
$sidebar_tabs = $sidebar_tabs ?? [];

$sharedData = array_merge($pageData, [
    'active_tab' => $active_tab,
    'sidebar_tabs' => $sidebar_tabs,
    'integrations' => $integrations ?? [],
]);

View::render('partials/header.php', compact('active_tab'));
?>
<div id="app" data-spa="1">
    <?php View::render('partials/sidebar.php', compact('active_tab', 'sidebar_tabs')); ?>
    <div id="app-shell" class="app-shell">
        <?php if ($subNavView): ?>
            <div id="app-sub-nav" class="app-sub-nav">
                <?php View::render($subNavView, $sharedData); ?>
            </div>
        <?php else: ?>
            <div id="app-sub-nav" class="app-sub-nav" hidden></div>
        <?php endif; ?>
        <main id="app-main" class="main-content">
            <?php if (View::exists($contentView)): ?>
                <?php View::render($contentView, $sharedData); ?>
            <?php else: ?>
                <h1>Section for <?php echo View::e($active_tab); ?> coming soon...</h1>
            <?php endif; ?>
        </main>
    </div>
</div>
<div id="app-nav-loader" class="app-nav-loader" hidden aria-live="polite" aria-busy="false">
    <span class="app-nav-loader-spinner"></span>
</div>
<?php View::render('partials/footer.php', compact('active_tab', 'page_scripts', 'integrations')); ?>
