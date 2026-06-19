<?php

use App\Core\View;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ChatRox — Sign in to your team workspace.">
    <title>ChatRox - <?php echo View::e($auth_title); ?></title>
    <link rel="icon" type="image/png" href="<?php echo View::asset('assets/images/logo.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo View::asset('assets/images/logo.png'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo View::asset('css/style.css'); ?>">
    <script src="https://unpkg.com/lucide@0.468.0" defer></script>
</head>

<body class="auth-page">
    <?php include VIEW_DIR . '/' . ltrim($auth_view, '/'); ?>
    <script src="<?php echo View::asset('js/auth.js'); ?>" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) { lucide.createIcons(); }
        });
    </script>
</body>

</html>
