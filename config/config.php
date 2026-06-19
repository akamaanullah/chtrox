<?php

date_default_timezone_set('Asia/Karachi');

define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', ROOT_DIR . '/app');
define('VIEW_DIR', ROOT_DIR . '/views');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/chatrox/public'));
$basePath = str_replace('/public', '', $scriptName);
$dynamicBaseUrl = rtrim($protocol . '://' . $host . $basePath, '/');

define('BASE_URL', $_ENV['BASE_URL'] ?? $dynamicBaseUrl);
define('APP_NAME', $_ENV['APP_NAME'] ?? 'ChatRox');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN));

define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_USER', $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_DATABASE'] ?? $_ENV['DB_NAME'] ?? '');

define('GIPHY_API_KEY', $_ENV['GIPHY_API_KEY'] ?? '');

define('FRONT_ASSETS', [
    'global' => [
        'js/script.js',
        'js/panels/profile.js',
        'js/modals/create-channel.js',
        'js/tabs/home/home-modals.js',
        'js/websocket.js',
    ],
    'home' => [
        'js/tabs/home/clocks.js',
        'js/tabs/home/focus-timer.js',
    ],
    'dms_chat' => [
        'js/tabs/dms/chat.js',
    ],
    'channels_chat' => [
        'js/tabs/channels/chat.js',
    ],
    'activity' => [
        'js/tabs/activity/activity.js',
    ],
    'files' => [
        'js/tabs/files/files.js',
    ],
]);

define('ADMIN_ASSETS', [
    'global' => [
        'js/script.js',
    ],
    'home' => [],
    'profile' => ['js/profile.js'],
    'analytics' => ['js/analytics.js'],
    'members' => ['js/members.js'],
    'channels' => ['js/channels.js'],
    'announcements' => ['js/announcements.js'],
    'files' => ['js/files.js'],
    'activity' => ['js/activity.js'],
]);
