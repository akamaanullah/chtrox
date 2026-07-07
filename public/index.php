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
use App\Middleware\CsrfMiddleware;

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
$router->get('/settings', 'Front\SettingsController@index', [AuthMiddleware::class]);
$router->get('/browse-channels', 'Front\BrowseChannelsController@index', [AuthMiddleware::class]);

// API V1 Routes
$router->post('/api/v1/messages', 'Front\Api\MessageController@send', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/messages/react', 'Front\Api\MessageController@react', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/api/v1/messages/reactions', 'Front\Api\MessageController@reactionDetails', [AuthMiddleware::class]);
$router->delete('/api/v1/messages', 'Front\Api\MessageController@delete', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->patch('/api/v1/messages', 'Front\Api\MessageController@edit', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/messages/read', 'Front\Api\MessageController@markRead', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/messages/forward', 'Front\Api\MessageController@forward', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/messages/pin', 'Front\Api\MessageController@pin', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/api/v1/messages/history', 'Front\Api\MessageController@history', [AuthMiddleware::class]);
$router->get('/api/v1/messages/context', 'Front\Api\MessageController@context', [AuthMiddleware::class]);

$router->post('/api/v1/channels', 'Front\Api\ChannelController@create', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/channels/join', 'Front\Api\ChannelController@join', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/channels/leave', 'Front\Api\ChannelController@leave', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->patch('/api/v1/channels', 'Front\Api\ChannelController@update', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/channels/approve-request', 'Front\Api\ChannelController@approveMemberRequest', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/channels/reject-request', 'Front\Api\ChannelController@rejectMemberRequest', [AuthMiddleware::class, CsrfMiddleware::class]);

$router->get('/api/v1/home/summary', 'Front\Api\HomeApiController@summary', [AuthMiddleware::class]);
$router->get('/api/v1/search', 'Front\Api\SearchController@index', [AuthMiddleware::class]);
$router->get('/api/v1/ws-ticket', 'Front\Api\WsTicketController@getTicket', [AuthMiddleware::class]);

$router->get('/api/v1/app/page', 'Front\Api\NavigateController@page', [AuthMiddleware::class]);

$router->get('/api/v1/giphy/gifs', 'Front\Api\GiphyController@gifs', [AuthMiddleware::class]);

$router->get('/api/v1/files', 'Front\Api\FileController@list', [AuthMiddleware::class]);
$router->post('/api/v1/files/upload', 'Front\Api\FileController@upload', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->delete('/api/v1/activity/{id}', 'Front\Api\ActivityApiController@delete', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/activity/clear', 'Front\Api\ActivityApiController@clear', [AuthMiddleware::class, CsrfMiddleware::class]);

$router->get('/files/download/{id}', 'Front\FilesController@download', [AuthMiddleware::class]);

$router->patch('/api/v1/profile', 'Front\Api\ProfileController@update', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/api/v1/settings', 'Front\Api\SettingsController@getSettings', [AuthMiddleware::class]);
$router->patch('/api/v1/settings', 'Front\Api\SettingsController@updateSettings', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/settings/change-password', 'Front\Api\SettingsController@changePassword', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/api/v1/settings/sessions', 'Front\Api\SettingsController@getSessions', [AuthMiddleware::class]);
$router->delete('/api/v1/settings/sessions', 'Front\Api\SettingsController@deleteSession', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/settings/sessions/revoke', 'Front\Api\SettingsController@deleteSession', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/settings/presence', 'Front\Api\SettingsController@updatePresence', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->put('/api/v1/profile/theme', 'Front\Api\ProfileController@updateTheme', [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/profile/avatar', 'Front\Api\ProfileController@uploadAvatar', [AuthMiddleware::class, CsrfMiddleware::class]);

// Auth routes
$router->get('/login', 'Front\AuthController@login', [GuestMiddleware::class]);
$router->post('/login', 'Front\AuthController@login', [GuestMiddleware::class, CsrfMiddleware::class]);
$router->get('/register', 'Front\AuthController@register', [GuestMiddleware::class]);
$router->post('/register', 'Front\AuthController@register', [GuestMiddleware::class, CsrfMiddleware::class]);
$router->get('/join/{token}', 'Front\InviteController@show', [GuestMiddleware::class]);
$router->post('/join/{token}', 'Front\InviteController@process', [GuestMiddleware::class, CsrfMiddleware::class]);
$router->get('/logout', 'Front\AuthController@logout');

// Admin auth routes
$router->get('/admin/login', 'Admin\AuthController@showLoginForm', [AdminGuestMiddleware::class]);
$router->post('/admin/login', 'Admin\AuthController@login', [AdminGuestMiddleware::class, CsrfMiddleware::class]);
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

// Admin API endpoints
$router->post('/api/admin/members', 'Admin\MembersController@add', [AdminAuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/admin/members/generate-invite', 'Admin\MembersController@generateInviteLink', [AdminAuthMiddleware::class, CsrfMiddleware::class]);
$router->patch('/api/admin/members', 'Admin\MembersController@edit', [AdminAuthMiddleware::class, CsrfMiddleware::class]);
$router->delete('/api/admin/members', 'Admin\MembersController@delete', [AdminAuthMiddleware::class, CsrfMiddleware::class]);

$router->post('/api/admin/channels', 'Admin\ChannelsController@create', [AdminAuthMiddleware::class, CsrfMiddleware::class]);
$router->patch('/api/admin/channels', 'Admin\ChannelsController@edit', [AdminAuthMiddleware::class, CsrfMiddleware::class]);
$router->delete('/api/admin/channels', 'Admin\ChannelsController@delete', [AdminAuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/api/admin/channels/members', 'Admin\ChannelsController@members', [AdminAuthMiddleware::class]);

$router->post('/api/admin/announcements', 'Admin\AnnouncementsController@add', [AdminAuthMiddleware::class, CsrfMiddleware::class]);
$router->patch('/api/admin/announcements', 'Admin\AnnouncementsController@edit', [AdminAuthMiddleware::class, CsrfMiddleware::class]);
$router->delete('/api/admin/announcements', 'Admin\AnnouncementsController@delete', [AdminAuthMiddleware::class, CsrfMiddleware::class]);

$router->delete('/api/admin/files', 'Admin\FilesController@delete', [AdminAuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/api/admin/analytics/data', 'Admin\AnalyticsController@data', [AdminAuthMiddleware::class]);
$router->post('/api/admin/profile', 'Admin\ProfileController@save', [AdminAuthMiddleware::class, CsrfMiddleware::class]);

$url = isset($_GET['url']) ? rtrim((string) $_GET['url'], '/') : '';
$router->dispatch($url);
