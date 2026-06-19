<?php

namespace App\Controllers\Front;

use App\Models\BrowseChannel;

class BrowseChannelsController extends FrontController
{
    public function index(): void
    {
        $this->renderApp('browse-channels', [
            'browse_channels' => BrowseChannel::all(),
        ]);
    }
}
