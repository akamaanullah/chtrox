<?php

namespace App\Models;

use App\Core\Model;

class ActivityFeed extends Model
{
    public static function items(): array
    {
        return [
            [
                'id' => 1,
                'type' => 'user',
                'name' => 'Emma Williams',
                'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150',
                'time' => '12:12 PM',
                'body' => 'mentioned you in a comment: "Please review the latest mockups. mentioned you in a comment. Please review the latest mockups. mentioned you in a comment. Please review the latest mockups. mentioned you in a comment. Please review the latest mockups. mentioned you in a comment. Please review the latest mockups. mentioned you in a comment. Please review the latest mockups. mentioned you in a comment. Please review the latest mockups. mentioned you in a comment. Please review the latest mockups. mentioned you in a comment. Please review the latest mockups. mentioned you in a comment. Please review the latest mockups. mentioned you in a comment. Please review the latest mockups. mentioned you in a comment."',
            ],
            [
                'id' => 2,
                'type' => 'user',
                'name' => 'Oliver Mitchell',
                'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=150',
                'time' => '11:32 PM',
                'body' => 'shared a document: "Q3_Strategy_v2.pdf"',
                'body_html' => 'shared a document: <span class="text-primary">"Q3_Strategy_v2.pdf"</span>',
            ],
            [
                'id' => 3,
                'type' => 'user',
                'name' => 'Charlotte Anderson',
                'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=150',
                'time' => '12:32 AM',
                'body' => 'reacted with 🔥 to your message "Let\'s ship it!"',
            ],
            [
                'id' => 4,
                'type' => 'system',
                'name' => 'Chatrox System',
                'time' => '12:32 AM',
                'body' => 'New security patch deployed to Chatrox.',
            ],
            [
                'id' => 5,
                'type' => 'user',
                'name' => 'Sophia Reynolds',
                'avatar' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=150',
                'time' => '09:15 AM',
                'body' => 'uploaded 4 new design assets to the #branding-assets channel.',
                'body_html' => 'uploaded 4 new design assets to the <span class="text-primary">#branding-assets</span> channel. "Logo variants for Q4 are ready for review."',
            ],
            [
                'id' => 6,
                'type' => 'user',
                'name' => 'Liam Carter',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=150',
                'time' => '08:45 AM',
                'body' => 'started a new project: Nexus Interface Redesign and added you as a collaborator.',
                'body_html' => 'started a new project: <span class="text-primary">"Nexus Interface Redesign"</span> and added you as a collaborator.',
            ],
            [
                'id' => 7,
                'type' => 'missed-call',
                'name' => 'Missed Call',
                'time' => '08:20 AM',
                'body' => 'You missed a huddle with Engineering Team. The huddle lasted 15 minutes.',
                'body_html' => 'You missed a huddle with <b>Engineering Team</b>. The huddle lasted 15 minutes.',
            ],
            [
                'id' => 8,
                'type' => 'user',
                'name' => 'Noah Bennett',
                'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=150',
                'time' => '07:55 AM',
                'body' => 'joined the workspace! Give them a warm welcome in #general.',
                'body_html' => 'joined the workspace! Give them a warm welcome in <span class="text-primary">#general</span>. 👋',
            ],
        ];
    }
}
