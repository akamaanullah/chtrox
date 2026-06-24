<?php

if (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $baseDir . $relative . '.php';

    if (is_file($file)) {
        require $file;
    }
});

use App\Core\DotEnv;
use App\Core\ErrorHandler;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\AdminAuthMiddleware;
use App\Middleware\AdminGuestMiddleware;

$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    (new DotEnv($envPath))->load();
}

require_once dirname(__DIR__) . '/config/config.php';

ErrorHandler::register();
Session::init();

$router = new Router();

// Front routes
$router->get('/', 'Front\HomeController@index', [AuthMiddleware::class]);
$router->get('/home', 'Front\HomeController@index', [AuthMiddleware::class]);
$router->get('/dms', 'Front\DmsController@index', [AuthMiddleware::class]);
$router->get('/dms/{with}', 'Front\DmsController@index', [AuthMiddleware::class]);
$router->get('/channels', 'Front\ChannelsController@index', [AuthMiddleware::class]);
$router->get('/channels/{id}', 'Front\ChannelsController@index', [AuthMiddleware::class]);
$router->get('/people', 'Front\PeopleController@index', [AuthMiddleware::class]);
$router->get('/activity', 'Front\ActivityController@index', [AuthMiddleware::class]);
$router->get('/files', 'Front\FilesController@index', [AuthMiddleware::class]);
$router->get('/browse-channels', 'Front\BrowseChannelsController@index', [AuthMiddleware::class]);

// API Routes
$router->post('/api/messages/send', 'Front\Api\MessageController@send', [AuthMiddleware::class]);
$router->post('/api/messages/react', 'Front\Api\MessageController@react', [AuthMiddleware::class]);
$router->get('/api/messages/reactions', 'Front\Api\MessageController@reactionDetails', [AuthMiddleware::class]);
$router->post('/api/messages/delete', 'Front\Api\MessageController@delete', [AuthMiddleware::class]);
$router->post('/api/messages/edit', 'Front\Api\MessageController@edit', [AuthMiddleware::class]);
$router->post('/api/messages/read', 'Front\Api\MessageController@markRead', [AuthMiddleware::class]);
$router->post('/api/messages/forward', 'Front\Api\MessageController@forward', [AuthMiddleware::class]);
$router->post('/api/messages/pin', 'Front\Api\MessageController@pin', [AuthMiddleware::class]);
$router->get('/api/messages/history', 'Front\Api\MessageController@history', [AuthMiddleware::class]);
$router->get('/api/messages/context', 'Front\Api\MessageController@context', [AuthMiddleware::class]);

$router->post('/api/channels/create', 'Front\Api\ChannelController@create', [AuthMiddleware::class]);
$router->post('/api/channels/join', 'Front\Api\ChannelController@join', [AuthMiddleware::class]);
$router->post('/api/channels/leave', 'Front\Api\ChannelController@leave', [AuthMiddleware::class]);
$router->post('/api/channels/update', 'Front\Api\ChannelController@update', [AuthMiddleware::class]);
$router->post('/api/channels/approve-request', 'Front\Api\ChannelController@approveMemberRequest', [AuthMiddleware::class]);
$router->post('/api/channels/reject-request', 'Front\Api\ChannelController@rejectMemberRequest', [AuthMiddleware::class]);

$router->get('/api/home/summary', 'Front\Api\HomeApiController@summary', [AuthMiddleware::class]);
$router->get('/api/search', 'Front\Api\SearchController@index', [AuthMiddleware::class]);

$router->get('/api/app/page', 'Front\Api\NavigateController@page', [AuthMiddleware::class]);

$router->get('/api/giphy/gifs', 'Front\Api\GiphyController@gifs', [AuthMiddleware::class]);

$router->post('/api/files/upload', 'Front\Api\FileController@upload', [AuthMiddleware::class]);

$router->get('/files/download/{id}', 'Front\FilesController@download', [AuthMiddleware::class]);

$router->post('/api/profile/update', 'Front\Api\ProfileController@update', [AuthMiddleware::class]);
$router->post('/api/profile/theme', 'Front\Api\ProfileController@updateTheme', [AuthMiddleware::class]);
$router->post('/api/profile/avatar', 'Front\Api\ProfileController@uploadAvatar', [AuthMiddleware::class]);

// Auth routes
$router->get('/login', 'Front\AuthController@login', [GuestMiddleware::class]);
$router->post('/login', 'Front\AuthController@login', [GuestMiddleware::class]);
$router->get('/register', 'Front\AuthController@register', [GuestMiddleware::class]);
$router->post('/register', 'Front\AuthController@register', [GuestMiddleware::class]);
$router->get('/logout', 'Front\AuthController@logout');

// Admin auth routes
$router->get('/admin/login', 'Admin\AuthController@showLoginForm', [AdminGuestMiddleware::class]);
$router->post('/admin/login', 'Admin\AuthController@login', [AdminGuestMiddleware::class]);
$router->get('/admin/logout', 'Admin\AuthController@logout');

// Admin routes
$router->get('/admin', 'Admin\HomeController@index', [AdminAuthMiddleware::class]);
$router->get('/admin/profile', 'Admin\ProfileController@index', [AdminAuthMiddleware::class]);
$router->get('/admin/analytics', 'Admin\AnalyticsController@index', [AdminAuthMiddleware::class]);
$router->get('/admin/members', 'Admin\MembersController@index', [AdminAuthMiddleware::class]);
$router->get('/admin/channels', 'Admin\ChannelsController@index', [AdminAuthMiddleware::class]);
$router->get('/admin/announcements', 'Admin\AnnouncementsController@index', [AdminAuthMiddleware::class]);
$router->get('/admin/files', 'Admin\FilesController@index', [AdminAuthMiddleware::class]);
$router->get('/admin/activity', 'Admin\ActivityController@index', [AdminAuthMiddleware::class]);

$url = isset($_GET['url']) ? rtrim((string) $_GET['url'], '/') : '';
$router->dispatch($url);
