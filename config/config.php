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
// HIGH-12: Default APP_DEBUG to false, and force it to false if running in production
$appEnv = strtolower($_ENV['APP_ENV'] ?? 'local');
$appDebug = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
if (($appEnv === 'production' || $appEnv === 'prod') && $appDebug) {
    $appDebug = false;
}
define('APP_DEBUG', $appDebug);
define('APP_ENV', $appEnv);
define('DEFAULT_AVATAR_URL', BASE_URL . '/assets/images/default-avatar.svg');

define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_USER', $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_DATABASE'] ?? $_ENV['DB_NAME'] ?? '');
define('DB_TIMEZONE', $_ENV['DB_TIMEZONE'] ?? '+05:00');

define('GIPHY_API_KEY', $_ENV['GIPHY_API_KEY'] ?? '');
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? '');

define('MAX_FILE_SIZE_BYTES', (int)($_ENV['MAX_FILE_SIZE_MB'] ?? 40) * 1024 * 1024);

// Trusted reverse proxy IPs. Only these IPs' X-Forwarded-For headers will be trusted.
// Comma-separated list. Empty = trust no proxies (direct connection only).
$trustedProxies = array_filter(array_map('trim', explode(',', $_ENV['TRUSTED_PROXIES'] ?? '')));
define('TRUSTED_PROXIES', $trustedProxies);

define('WS_PORT', (int)($_ENV['WS_PORT'] ?? 8088));
define('WS_BIND', $_ENV['WS_BIND'] ?? '0.0.0.0');

define('FRONT_ASSETS', [
    'global' => [
        'js/shared/file-type-info.js',
        'js/shared/lucide-scope.js',
        'js/shared/toast.js',
        'js/shared/giphy.js',
        'js/shared/file-upload.js',
        'js/shared/message-media.js',
        'js/shared/message-focus.js',
        'js/shared/message-date-divider.js',
        'js/shared/app-router.js',
        'js/tabs/home/home-live.js',
        'js/script.js',
        'js/panels/profile.js',
        'js/modals/create-channel.js',
        'js/tabs/home/home-modals.js',
        'js/websocket.js',
    ],
    'home' => [
        'js/tabs/home/clocks.js',
        'js/tabs/home/focus-timer.js',
        'js/tabs/home/home-search.js',
    ],
    'dms_sidebar' => [
        'js/tabs/dms/sidebar.js',
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
    'settings' => [
        'js/tabs/settings/settings.js',
    ],
    'people' => [
        'js/tabs/people/people.js',
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
    'feedback' => [],
]);
