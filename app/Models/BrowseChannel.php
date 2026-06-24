<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;

class BrowseChannel extends Model
{
    public static function all(): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;
        $memberId = $user['workspace_member_id'] ?? 0;

        if ($workspaceId === 0 || $memberId === 0) {
            return [];
        }

        $db = self::db();
        $stmt = $db->prepare("
            SELECT 
                c.*,
                EXISTS (
                    SELECT 1 FROM channel_members cm 
                    WHERE cm.channel_id = c.id AND cm.workspace_member_id = :member_id AND cm.left_at IS NULL
                ) as joined,
                COALESCE((
                    SELECT status FROM channel_join_requests r
                    WHERE r.channel_id = c.id AND r.workspace_member_id = :member_id
                    LIMIT 1
                ), 'none') as request_status
            FROM channels c
            WHERE c.workspace_id = :workspace_id AND c.status = 'active'
            ORDER BY (c.visibility = 'public') DESC, c.name ASC
        ");
        $stmt->execute([
            'workspace_id' => $workspaceId,
            'member_id' => $memberId
        ]);
        $rows = $stmt->fetchAll();

        $channels = [];
        $iconMap = [
            'general' => 'hash',
            'development-announcements' => 'code',
            'engineering' => 'code',
            'design' => 'palette',
            'design-huddle' => 'palette',
            'marketing' => 'megaphone',
            'random' => 'message-circle'
        ];

        foreach ($rows as $row) {
            $metaPrefix = $row['visibility'] === 'private' ? 'Private · ' : '';
            $description = $row['description'] ?: 'No description provided';

            $channels[] = [
                'id' => $row['id'],
                'slug' => $row['slug'],
                'icon' => $iconMap[$row['slug']] ?? 'hash',
                'name' => '#' . $row['name'],
                'meta' => $metaPrefix . $description . ' · ' . $row['member_count'] . ' members',
                'visibility' => $row['visibility'],
                'joined' => (bool) $row['joined'],
                'request_pending' => $row['request_status'] === 'pending'
            ];
        }

        return $channels;
    }
}
