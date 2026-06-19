<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;

class AdminController extends Controller
{
    public function __construct()
    {
        if (!Session::isAdminLoggedIn()) {
            $this->redirect('/admin/login');
        }
    }

    protected function renderDashboard(string $activePage, array $viewData = [], ?string $contentView = null): void
    {
        $active_page = $activePage;
        $nav_sections = \App\Models\AdminNavigation::sections();
        $page_scripts = $this->resolveDashboardScripts($activePage);
        $contentView = $contentView ?? $activePage;

        $this->view('admin/layouts/app', [
            'active_page' => $active_page,
            'nav_sections' => $nav_sections,
            'page_scripts' => $page_scripts,
            'page_title' => $viewData['page_title'] ?? (APP_NAME . ' - Premium Dashboard'),
            'contentView' => $contentView,
            'pageData' => $viewData,
        ]);
    }

    private function resolveDashboardScripts(string $activePage): array
    {
        $assets = ADMIN_ASSETS;
        $scripts = $assets['global'];

        if ($activePage !== '' && isset($assets[$activePage])) {
            $scripts = array_merge($scripts, $assets[$activePage]);
        }

        return $scripts;
    }
}
