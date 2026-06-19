<?php

namespace App\Controllers\Front;

use App\Models\ActivityFeed;

class ActivityController extends FrontController
{
    public function index(): void
    {
        $this->renderApp('activity', [
            'activity_items' => ActivityFeed::items(),
        ]);
    }
}
