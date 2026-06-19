<?php

namespace App\Controllers\Front;

use App\Models\DmsConversation;
use App\Models\ForwardTarget;

class DmsController extends FrontController
{
    public function index(?string $with = null): void
    {
        $withId = $with ?? '';
        $viewData = [
            'dm_sidebar_items' => DmsConversation::sidebarDisplayItems(),
            'active_with' => $withId,
            'dm_welcome_cards' => DmsConversation::welcomeCards(),
            'forward_targets' => ForwardTarget::list(),
        ];

        if ($withId !== '') {
            $resolved = DmsConversation::resolveUser($withId);
            $conversationId = DmsConversation::getOrCreateConversationId($resolved['with_id']);
            $viewData = array_merge($viewData, [
                'with_id' => $resolved['with_id'],
                'with_user' => $resolved['with_user'],
                'conversation_id' => $conversationId,
                'common_media' => DmsConversation::commonMedia(),
                'messages' => DmsConversation::messages($resolved['with_id']),
                'initial_visible' => DmsConversation::INITIAL_VISIBLE,
                'name' => htmlspecialchars($resolved['with_user']['name']),
            ]);
        }

        $contentView = $withId !== '' ? 'tabs/dms/chat.php' : 'tabs/dms/main.php';

        $this->renderApp('dms', $viewData, $contentView);
    }
}
