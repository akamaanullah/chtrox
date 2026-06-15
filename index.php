<?php
// User-friendly URL Routing
$base_path = '/chatrox/';
$uri = $_SERVER['REQUEST_URI'];

// Remove query string if any
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

// Remove base path from URI
if (substr($uri, 0, strlen($base_path)) == $base_path) {
    $uri = substr($uri, strlen($base_path));
}

// Clean up slashes
$uri = trim($uri, '/');
$parts = explode('/', $uri);

// Default tab
$active_tab = !empty($parts[0]) ? $parts[0] : 'home';

// Handle standalone Auth pages (Login/Register)
if ($active_tab === 'login') {
    include 'login.php';
    exit;
}
if ($active_tab === 'register') {
    include 'register.php';
    exit;
}

// Handle sub-parameters (e.g., /dms/emma or /channels/general)
if ($active_tab == 'dms' && isset($parts[1])) {
    $_GET['with'] = $parts[1];
} elseif ($active_tab == 'channels' && isset($parts[1])) {
    $_GET['id'] = $parts[1];
}

// Path configuration
$tab_path = "includes/tabs/$active_tab/";
$sub_nav_file = $tab_path . "sub_nav.php";
$main_content_file = $tab_path . "main.php";

include 'includes/header.php';
?>

<div id="app">
    <?php include 'includes/sidebar.php'; ?>

    <?php
    // Dynamically load sub-navigation if it exists (skip for browse-channels — main screen only)
    if (file_exists($sub_nav_file) && $active_tab !== 'browse-channels') {
        include $sub_nav_file;
    }
    ?>

    <main class="main-content">
        <?php
        // Dynamically load main content if it exists
        if (file_exists($main_content_file)) {
            include $main_content_file;
        } else {
            echo "<h1>Section for $active_tab coming soon...</h1>";
        }
        ?>
    </main>
</div>

<?php include 'includes/footer.php'; ?>