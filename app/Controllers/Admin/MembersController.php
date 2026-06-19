<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;

class MembersController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('members', [
            'page_title' => 'Manage Members - ChatRox',
            'members' => AdminOverview::members(),
        ]);
    }
}
