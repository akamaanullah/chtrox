<?php

namespace App\Controllers\Admin;

use App\Models\AdminOverview;

class FilesController extends AdminController
{
    public function index(): void
    {
        $this->renderDashboard('files', [
            'page_title' => 'Files & Media - ChatRox',
            'files' => AdminOverview::files(),
        ]);
    }
}
