<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;

class HomeController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('home', [
            'page_title' => 'ChatRox - Premium Dashboard',
            'greeting' => AdminOverview::greeting(),
            'stats' => AdminOverview::stats(),
        ]);
    }
}
