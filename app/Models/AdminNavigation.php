<?php

namespace App\Models;

use App\Core\Model;

class AdminNavigation extends Model
{
    public static function sections(): array
    {
        return [
            [
                'title' => 'ACCOUNT',
                'links' => [
                    ['id' => 'home', 'icon' => 'home', 'label' => 'Home', 'short_label' => 'Home'],
                    ['id' => 'profile', 'icon' => 'user', 'label' => 'Account & profile', 'short_label' => 'Profile'],
                    ['id' => 'analytics', 'icon' => 'bar-chart-2', 'label' => 'Analytics', 'short_label' => 'Stats'],
                ],
            ],
            [
                'title' => 'ADMINISTRATION',
                'links' => [
                    ['id' => 'members', 'icon' => 'users', 'label' => 'Manage members', 'short_label' => 'Members'],
                    ['id' => 'channels', 'icon' => 'hash', 'label' => 'Channels', 'short_label' => 'Channels'],
                    ['id' => 'announcements', 'icon' => 'megaphone', 'label' => 'Announcements', 'short_label' => 'News'],
                    ['id' => 'files', 'icon' => 'file-text', 'label' => 'Files & Media', 'short_label' => 'Files'],
                    ['id' => 'activity', 'icon' => 'activity', 'label' => 'Recent Activities', 'short_label' => 'Activity'],
                    ['id' => 'feedback', 'icon' => 'message-square', 'label' => 'Feedbacks & Reports', 'short_label' => 'Feedbacks'],
                    ['id' => 'resets', 'icon' => 'key', 'label' => 'Reset Requests', 'short_label' => 'Resets'],
                ],
            ],
        ];
    }
}
