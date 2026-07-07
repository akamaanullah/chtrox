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
        $this->respond($activeTab, $viewData, $contentView);
    }

    protected function respond(string $activeTab, array $viewData = [], ?string $contentView = null): void
    {
        if ($this->isNavigateRequest()) {
            $this->jsonResponse($this->buildPageFragment($activeTab, $viewData, $contentView));
        }

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
            'integrations' => [
                'giphy_enabled' => GIPHY_API_KEY !== '',
            ],
        ]);
    }

    protected function isNavigateRequest(): bool
    {
        return isset($_SERVER['HTTP_X_CHATROX_NAVIGATE']) && $_SERVER['HTTP_X_CHATROX_NAVIGATE'] === '1';
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPageFragment(string $activeTab, array $viewData, ?string $contentView): array
    {
        $sharedData = array_merge($viewData, [
            'active_tab' => $activeTab,
            'sidebar_tabs' => Navigation::sidebarTabs(),
            'integrations' => [
                'giphy_enabled' => GIPHY_API_KEY !== '',
            ],
        ]);

        $subNavView = null;
        if ($activeTab !== 'browse-channels') {
            $candidate = "tabs/{$activeTab}/sub_nav.php";
            if (View::exists($candidate)) {
                $subNavView = $candidate;
            }
        }

        $contentView = $contentView ?? "tabs/{$activeTab}/main.php";

        return [
            'success' => true,
            'active_tab' => $activeTab,
            'path' => $this->buildPathFromContext($activeTab, $viewData),
            'title' => APP_NAME,
            'sub_nav_html' => $subNavView ? View::capture($subNavView, $sharedData) : '',
            'main_html' => View::exists($contentView)
                ? View::capture($contentView, $sharedData)
                : '<h1>Section coming soon...</h1>',
            'scripts' => array_map(function($script) {
                return \App\Core\View::asset($script);
            }, array_values(array_unique($this->resolvePageScripts($activeTab, $viewData)))),
            'meta' => $this->extractPageMeta($activeTab, $viewData),
        ];
    }

    /**
     * @param array<string, mixed> $viewData
     */
    private function buildPathFromContext(string $activeTab, array $viewData): string
    {
        if ($activeTab === 'dms' && !empty($viewData['with_id'])) {
            return 'dms/' . $viewData['with_id'];
        }
        if ($activeTab === 'channels' && !empty($viewData['channel_id'])) {
            return 'channels/' . $viewData['channel_id'];
        }
        if ($activeTab === 'home') {
            return 'home';
        }

        return $activeTab;
    }

    /**
     * @param array<string, mixed> $viewData
     * @return array<string, mixed>
     */
    private function extractPageMeta(string $activeTab, array $viewData): array
    {
        $meta = ['active_tab' => $activeTab];

        if ($activeTab === 'dms' && !empty($viewData['with_id'])) {
            $meta['with_username'] = $viewData['with_id'];
            $meta['conversation_id'] = $viewData['conversation_id'] ?? null;
        }

        if ($activeTab === 'channels' && !empty($viewData['channel_id'])) {
            $meta['channel_id'] = $viewData['channel_id'];
        }

        return $meta;
    }

    private function resolvePageScripts(string $activeTab, array $viewData): array
    {
        $assets = FRONT_ASSETS;
        $scripts = $assets['global'];

        if ($activeTab === 'home') {
            $scripts = array_merge($scripts, $assets['home']);
        } elseif ($activeTab === 'channels' && !empty($viewData['channel_id'])) {
            $scripts = array_merge($scripts, $assets['channels_chat']);
        } elseif ($activeTab === 'dms') {
            $scripts = array_merge($scripts, $assets['dms_sidebar']);
            if (!empty($viewData['with_id'])) {
                $scripts = array_merge($scripts, $assets['dms_chat']);
            }
        } elseif ($activeTab === 'activity') {
            $scripts = array_merge($scripts, $assets['activity']);
        } elseif ($activeTab === 'files') {
            $scripts = array_merge($scripts, $assets['files']);
        } elseif ($activeTab === 'settings') {
            $scripts = array_merge($scripts, $assets['settings']);
        } elseif ($activeTab === 'people') {
            $scripts = array_merge($scripts, $assets['people']);
        }

        return $scripts;
    }
}
