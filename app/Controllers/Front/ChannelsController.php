<?php

namespace App\Controllers\Front;

use App\Models\ChannelConversation;
use App\Models\ForwardTarget;
use App\Models\ChannelJoinRequest;
use App\Core\Session;

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
            
            // Redirect to the new slug if they accessed the channel via a former slug
            if ($resolved['active_channel']['slug'] !== $channelId) {
                $this->redirect('/channels/' . $resolved['active_channel']['slug']);
                return;
            }

            $conversationId = (int)$resolved['active_channel']['conversation_id'];
            $memberId = (int)(Session::user()['workspace_member_id'] ?? 0);
            $workspaceId = (int)(Session::user()['workspace_id'] ?? 0);
            $messages = ChannelConversation::messages($resolved['channel_id']);
            $oldestMessageId = !empty($messages) ? (int)end($messages)['id'] : 0;

            // Fetch pending join requests only for channel admins/creators
            $userRole = Session::user()['role'] ?? 'member';
            $isCreator = ((int)($resolved['active_channel']['created_by'] ?? 0) === $memberId);
            $isAdmin = in_array($userRole, ['owner', 'admin'], true);
            $channelIntId = (int)($resolved['active_channel']['id'] ?? 0);
            
            // Fetch current user's role in the channel
            $stmt = \App\Core\Model::db()->prepare("
                SELECT role FROM channel_members
                WHERE channel_id = ? AND workspace_member_id = ? AND left_at IS NULL
            ");
            $stmt->execute([$channelIntId, $memberId]);
            $currentUserChannelRole = $stmt->fetchColumn() ?: 'member';

            $pendingRequests = ($isCreator || $isAdmin) 
                ? ChannelJoinRequest::getPendingByChannel($channelIntId, $workspaceId) 
                : [];

            $viewData = array_merge($viewData, [
                'channel_id' => $resolved['channel_id'],
                'active_channel' => $resolved['active_channel'],
                'conversation_id' => $conversationId,
                'conversation_media' => ChannelConversation::conversationMedia($conversationId, $memberId),
                'conversation_files' => ChannelConversation::conversationFiles($conversationId, $memberId),
                'messages' => $messages,
                'initial_visible' => ChannelConversation::INITIAL_VISIBLE,
                'has_older_messages' => \App\Models\DmsConversation::hasOlderMessages($conversationId, $memberId, $oldestMessageId),
                'oldest_message_id' => $oldestMessageId,
                'channel_members' => ChannelConversation::channelMembers($resolved['active_channel']['slug']),
                'workspace_members' => ChannelConversation::getWorkspaceMembers(),
                'pending_join_requests' => $pendingRequests,
                'current_user_channel_role' => $currentUserChannelRole,
                'name' => htmlspecialchars($resolved['active_channel']['name']),
            ]);
        }

        $contentView = $channelId !== '' ? 'tabs/channels/chat.php' : 'tabs/channels/main.php';

        $this->renderApp('channels', $viewData, $contentView);
    }
}
