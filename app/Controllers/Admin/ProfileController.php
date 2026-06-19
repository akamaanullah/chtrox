<?php

namespace App\Controllers\Admin;

class ProfileController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('profile', [
            'page_title' => 'Account & Profile - ChatRox',
        ]);
    }
}
