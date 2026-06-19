<?php

namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\HomeDashboard;

class HomeController extends FrontController
{
    public function index(): void
    {
        $this->renderApp('home', [
            'home_greeting' => HomeDashboard::greeting(),
            'home_stats' => HomeDashboard::stats(),
            'home_search_tags' => HomeDashboard::searchTags(),
            'home_world_clocks' => HomeDashboard::worldClocks(),
            'home_announcements' => HomeDashboard::announcements(),
            'home_chat_card' => HomeDashboard::chatInboxCard(),
            'home_sidebar_dms' => HomeDashboard::sidebarDmPreview(),
            'home_sidebar_channels' => HomeDashboard::sidebarChannelPreview(),
            'home_sidebar_activity' => HomeDashboard::sidebarRecentActivity(),
        ]);
    }
}
