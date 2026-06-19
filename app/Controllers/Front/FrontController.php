<?php

namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Navigation;

class FrontController extends Controller
{
    public function __construct()
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
        }
    }

    protected function renderApp(string $activeTab, array $viewData = [], ?string $contentView = null): void
    {
        $active_tab = $activeTab;
        $sidebar_tabs = Navigation::sidebarTabs();
        $page_scripts = $this->resolvePageScripts($activeTab, $viewData);

        $subNavView = null;
        if ($active_tab !== 'browse-channels') {
            $candidate = "tabs/{$active_tab}/sub_nav.php";
            if (View::exists($candidate)) {
                $subNavView = $candidate;
            }
        }

        $contentView = $contentView ?? "tabs/{$active_tab}/main.php";

        $this->view('front/layouts/app', [
            'active_tab' => $active_tab,
            'sidebar_tabs' => $sidebar_tabs,
            'page_scripts' => $page_scripts,
            'subNavView' => $subNavView,
            'contentView' => $contentView,
            'pageData' => $viewData,
            'integrations' => ['giphy_api_key' => GIPHY_API_KEY],
        ]);
    }

    private function resolvePageScripts(string $activeTab, array $viewData): array
    {
        $assets = FRONT_ASSETS;
        $scripts = $assets['global'];

        if ($activeTab === 'home') {
            $scripts = array_merge($scripts, $assets['home']);
        } elseif ($activeTab === 'channels' && !empty($viewData['channel_id'])) {
            $scripts = array_merge($scripts, $assets['channels_chat']);
        } elseif ($activeTab === 'dms' && !empty($viewData['with_id'])) {
            $scripts = array_merge($scripts, $assets['dms_chat']);
        } elseif ($activeTab === 'activity') {
            $scripts = array_merge($scripts, $assets['activity']);
        } elseif ($activeTab === 'files') {
            $scripts = array_merge($scripts, $assets['files']);
        }

        return $scripts;
    }
}
