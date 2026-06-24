<?php

namespace App\Controllers\Front\Api;

use App\Controllers\Front\ActivityController;
use App\Controllers\Front\BrowseChannelsController;
use App\Controllers\Front\ChannelsController;
use App\Controllers\Front\DmsController;
use App\Controllers\Front\FilesController;
use App\Controllers\Front\FrontController;
use App\Controllers\Front\HomeController;
use App\Controllers\Front\PeopleController;

class NavigateController extends FrontController
{
    public function page(): void
    {
        $_SERVER['HTTP_X_CHATROX_NAVIGATE'] = '1';

        $path = trim((string)($_GET['path'] ?? ''), '/');

        if ($path === '' || $path === 'home') {
            (new HomeController())->index();
            return;
        }

        if ($path === 'dms') {
            (new DmsController())->index(null);
            return;
        }

        if (preg_match('#^dms/([^/]+)$#', $path, $matches)) {
            (new DmsController())->index($matches[1]);
            return;
        }

        if ($path === 'channels') {
            (new ChannelsController())->index(null);
            return;
        }

        if (preg_match('#^channels/([^/]+)$#', $path, $matches)) {
            (new ChannelsController())->index($matches[1]);
            return;
        }

        if ($path === 'people') {
            (new PeopleController())->index();
            return;
        }

        if ($path === 'activity') {
            (new ActivityController())->index();
            return;
        }

        if ($path === 'files') {
            (new FilesController())->index();
            return;
        }

        if ($path === 'browse-channels') {
            (new BrowseChannelsController())->index();
            return;
        }

        $this->jsonResponse(['success' => false, 'error' => 'Not found'], 404);
    }
}
