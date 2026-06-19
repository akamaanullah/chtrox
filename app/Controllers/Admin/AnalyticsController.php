<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;

class AnalyticsController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('analytics', [
            'page_title' => 'Analytics - ChatRox',
            'stats' => AdminOverview::stats(),
        ]);
    }
}
