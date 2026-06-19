<?php

use App\Core\View;

$sharedData = array_merge($pageData, [
    'active_tab' => $active_tab,
    'sidebar_tabs' => $sidebar_tabs,
    'integrations' => $integrations ?? [],
]);

View::render('partials/header.php', compact('active_tab'));
?>
<div id="app">
    <?php View::render('partials/sidebar.php', compact('active_tab', 'sidebar_tabs')); ?>
    <?php if ($subNavView): ?>
        <?php View::render($subNavView, $sharedData); ?>
    <?php endif; ?>
    <main class="main-content">
        <?php if (View::exists($contentView)): ?>
            <?php View::render($contentView, $sharedData); ?>
        <?php else: ?>
            <h1>Section for <?php echo View::e($active_tab); ?> coming soon...</h1>
        <?php endif; ?>
    </main>
</div>
<?php View::render('partials/footer.php', compact('active_tab', 'page_scripts', 'integrations')); ?>
