<?php

namespace App\Models;

use App\Core\Model;

class AdminOverview extends Model
{
    public static function greeting(): array
    {
        return [
            'user_name' => 'ChatroxAdmin',
            'date_label' => date('l, M j, Y'),
        ];
    }

    public static function stats(): array
    {
        $people = People::directory();
        $channels = BrowseChannel::all();
        $online = count(array_filter($people, fn (array $person): bool => ($person['status'] ?? '') === 'online'));

        return [
            'members' => count($people),
            'channels' => count($channels),
            'online' => $online,
            'unread' => 2,
            'files' => count(WorkspaceFile::all()),
            'activity_new' => count(ActivityFeed::items()),
        ];
    }

    public static function announcements(): array
    {
        return HomeDashboard::announcements();
    }

    public static function members(): array
    {
        return People::directory();
    }

    public static function channels(): array
    {
        return BrowseChannel::all();
    }

    public static function files(): array
    {
        return WorkspaceFile::all();
    }

    public static function activity(): array
    {
        return ActivityFeed::items();
    }
}
