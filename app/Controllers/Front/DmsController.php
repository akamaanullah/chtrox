<?php

namespace App\Controllers\Front;

use App\Core\Session;
use App\Models\DmsConversation;
use App\Models\ForwardTarget;

class DmsController extends FrontController
{
    public function index(?string $with = null): void
    {
        $withId = $with ?? '';
        $memberId = (int)(Session::user()['workspace_member_id'] ?? 0);
        $viewData = [
            'dm_sidebar_items' => DmsConversation::sidebarDisplayItems(),
            'active_with' => $withId,
            'dm_welcome_cards' => DmsConversation::welcomeCards(),
            'forward_targets' => ForwardTarget::list(),
        ];

        if ($withId !== '') {
            $resolved = DmsConversation::resolveUser($withId);
            $conversationId = DmsConversation::getOrCreateConversationId($resolved['with_id']);
            $messages = DmsConversation::messages($resolved['with_id']);
            $oldestMessageId = !empty($messages) ? (int)end($messages)['id'] : 0;
            $viewData = array_merge($viewData, [
                'with_id' => $resolved['with_id'],
                'with_user' => $resolved['with_user'],
                'conversation_id' => $conversationId,
                'contact_profile' => DmsConversation::contactProfile($resolved['with_id']),
                'conversation_media' => DmsConversation::conversationMedia($conversationId, $memberId),
                'conversation_files' => DmsConversation::conversationFiles($conversationId, $memberId),
                'messages' => $messages,
                'initial_visible' => DmsConversation::INITIAL_VISIBLE,
                'has_older_messages' => DmsConversation::hasOlderMessages($conversationId, $memberId, $oldestMessageId),
                'oldest_message_id' => $oldestMessageId,
                'name' => htmlspecialchars($resolved['with_user']['name']),
            ]);
        }

        $contentView = $withId !== '' ? 'tabs/dms/chat.php' : 'tabs/dms/main.php';

        $this->renderApp('dms', $viewData, $contentView);
    }
}
