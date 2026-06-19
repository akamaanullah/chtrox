<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;

class AnnouncementsController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('announcements', [
            'page_title' => 'Announcements - ChatRox',
            'announcements' => AdminOverview::announcements(),
        ]);
    }
}
