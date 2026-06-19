<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;

class ChannelsController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('channels', [
            'page_title' => 'Channels - ChatRox',
            'channels' => AdminOverview::channels(),
        ]);
    }
}
