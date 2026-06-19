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
                ) as joined
            FROM channels c
            WHERE c.workspace_id = :workspace_id AND c.visibility = 'public' AND c.status = 'active'
            ORDER BY c.name ASC
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
            $channels[] = [
                'id' => $row['id'],
                'slug' => $row['slug'],
                'icon' => $iconMap[$row['slug']] ?? 'hash',
                'name' => '#' . $row['name'],
                'meta' => ($row['description'] ?: 'No description provided') . ' · ' . $row['member_count'] . ' members',
                'joined' => (bool) $row['joined']
            ];
        }

        return $channels;
    }
}
