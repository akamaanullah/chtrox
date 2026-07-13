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
        $this->ensureAiUserExists();
    }

    protected function ensureAiUserExists(): void
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        if ($workspaceId === 0) {
            return;
        }

        $db = \App\Core\Model::db();
        
        try {
            // 1. Ensure User exists in 'users' table
            $stmtUser = $db->prepare("SELECT id FROM users WHERE username = 'ai' LIMIT 1");
            $stmtUser->execute();
            $aiUserId = $stmtUser->fetchColumn();

            if (!$aiUserId) {
                // Create the AI user
                $stmtInsertUser = $db->prepare("
                    INSERT INTO users (email, username, password_hash, first_name, last_name, bio, avatar_path)
                    VALUES ('ai@chatrox.com', 'ai', '*', 'ChatRox', 'AI', 'Your intelligent workspace assistant, powered by Gemini.', 'assets/images/logo.png')
                ");
                $stmtInsertUser->execute();
                $aiUserId = (int)$db->lastInsertId();

                // Set AI preference
                $stmtPrefs = $db->prepare("
                    INSERT INTO user_preferences (user_id, theme_color, timezone)
                    VALUES (?, 'indigo', 'UTC')
                ");
                $stmtPrefs->execute([$aiUserId]);

                // Set presence online
                $stmtPresence = $db->prepare("
                    INSERT INTO user_presence (user_id, status)
                    VALUES (?, 'online')
                    ON DUPLICATE KEY UPDATE status = 'online'
                ");
                $stmtPresence->execute([$aiUserId]);
            }

            // 2. Ensure AI is in workspace_members
            $stmtMember = $db->prepare("SELECT id FROM workspace_members WHERE workspace_id = ? AND user_id = ? LIMIT 1");
            $stmtMember->execute([$workspaceId, $aiUserId]);
            $aiMemberId = $stmtMember->fetchColumn();

            if (!$aiMemberId) {
                $stmtInsertMember = $db->prepare("
                    INSERT INTO workspace_members (workspace_id, user_id, role, job_title, status)
                    VALUES (?, ?, 'member', 'AI Assistant', 'active')
                ");
                $stmtInsertMember->execute([$workspaceId, $aiUserId]);
            }
        } catch (\Throwable $e) {
            \App\Core\ErrorHandler::logError($e);
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
