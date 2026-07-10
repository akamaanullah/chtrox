<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="ChatRox — Modern team communication workspace for messaging, channels, and collaboration.">
    <title>ChatRox - <?php echo ucfirst($active_tab); ?></title>
    <link rel="icon" type="image/png" href="<?php echo \App\Core\View::asset('assets/images/logo.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo \App\Core\View::asset('assets/images/logo.png'); ?>">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" id="metaThemeColor" content="#4f46e5">
    <base href="<?php echo BASE_URL; ?>/">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo \App\Core\View::asset('css/style.css'); ?>">
    <script src="<?php echo \App\Core\View::asset('js/themes-shared.js'); ?>"></script>
    <script>if (window.ChatroxTheme) { ChatroxTheme.applySaved(); }</script>
    <script src="https://unpkg.com/lucide@0.468.0" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
</head>

<body>