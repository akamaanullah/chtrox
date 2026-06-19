<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;

class ActivityController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('activity', [
            'page_title' => 'Recent Activities - ChatRox',
            'activities' => AdminOverview::activity(),
        ]);
    }
}
