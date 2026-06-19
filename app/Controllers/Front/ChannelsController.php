<?php

namespace App\Controllers\Front;

use App\Models\ChannelConversation;
use App\Models\ForwardTarget;

class ChannelsController extends FrontController
{
    public function index(?string $id = null): void
    {
        $channelId = $id ?? '';
        $viewData = [
            'channel_sidebar_items' => ChannelConversation::sidebarDisplayItems(),
            'active_channel_id' => $channelId,
            'channel_hero_cards' => ChannelConversation::heroCards(),
            'forward_targets' => ForwardTarget::list(),
        ];

        if ($channelId !== '') {
            $resolved = ChannelConversation::resolveChannel($channelId);
            $viewData = array_merge($viewData, [
                'channel_id' => $resolved['channel_id'],
                'active_channel' => $resolved['active_channel'],
                'common_media' => ChannelConversation::commonMedia(),
                'messages' => ChannelConversation::messages($resolved['channel_id']),
                'initial_visible' => ChannelConversation::INITIAL_VISIBLE,
                'name' => htmlspecialchars($resolved['active_channel']['name']),
            ]);
        }

        $contentView = $channelId !== '' ? 'tabs/channels/chat.php' : 'tabs/channels/main.php';

        $this->renderApp('channels', $viewData, $contentView);
    }
}
