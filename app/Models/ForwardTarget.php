<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;

class ForwardTarget extends Model
{
    private const DEFAULT_AVATAR = DEFAULT_AVATAR_URL;

    /**
     * People + joined channels the current user can forward a message to.
     *
     * @return array<int, array{type: string, id: string, name: string, label: string, avatar: ?string, search: string}>
     */
    public static function list(): array
    {
        $user = Session::user();
        $memberId = (int)($user['workspace_member_id'] ?? 0);
        $workspaceId = (int)($user['workspace_id'] ?? 0);

        if ($memberId === 0 || $workspaceId === 0) {
            return [];
        }

        $targets = array_merge(
            self::dmTargets($workspaceId, $memberId),
            self::channelTargets($workspaceId, $memberId)
        );

        usort($targets, static function (array $a, array $b): int {
            return strcasecmp($a['label'], $b['label']);
        });

        return $targets;
    }

    /** @return array<int, array<string, mixed>> */
    private static function dmTargets(int $workspaceId, int $memberId): array
    {
        $stmt = self::db()->prepare("
            SELECT u.username, u.first_name, u.last_name, u.avatar_path
            FROM workspace_members wm
            JOIN users u ON u.id = wm.user_id AND u.deleted_at IS NULL
            WHERE wm.workspace_id = ?
              AND wm.status = 'active'
              AND wm.left_at IS NULL
              AND wm.id != ?
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute([$workspaceId, $memberId]);
        $rows = $stmt->fetchAll();

        $targets = [];
        foreach ($rows as $row) {
            $name = trim($row['first_name'] . ' ' . $row['last_name']);
            $targets[] = [
                'type' => 'dm',
                'id' => $row['username'],
                'name' => $name,
                'label' => $name,
                'avatar' => $row['avatar_path'] ?: self::DEFAULT_AVATAR,
                'search' => strtolower($name . ' ' . $row['username']),
            ];
        }

        return $targets;
    }

    /** @return array<int, array<string, mixed>> */
    private static function channelTargets(int $workspaceId, int $memberId): array
    {
        $stmt = self::db()->prepare("
            SELECT c.slug, c.name
            FROM channels c
            JOIN channel_members cm ON cm.channel_id = c.id
                AND cm.workspace_member_id = ?
                AND cm.left_at IS NULL
            WHERE c.workspace_id = ?
              AND c.status = 'active'
            ORDER BY c.name ASC
        ");
        $stmt->execute([$memberId, $workspaceId]);
        $rows = $stmt->fetchAll();

        $targets = [];
        foreach ($rows as $row) {
            $channelName = $row['name'];
            $label = '#' . ltrim($channelName, '#');
            $targets[] = [
                'type' => 'channel',
                'id' => $row['slug'],
                'name' => $channelName,
                'label' => $label,
                'avatar' => null,
                'search' => strtolower($channelName . ' ' . $row['slug']),
            ];
        }

        return $targets;
    }

    public static function resolveConversationId(string $type, string $targetId): int
    {
        if ($type === 'dm') {
            return DmsConversation::getOrCreateConversationId($targetId);
        }

        if ($type === 'channel') {
            return self::channelConversationId($targetId);
        }

        return 0;
    }

    private static function channelConversationId(string $slug): int
    {
        $user = Session::user();
        $workspaceId = (int)($user['workspace_id'] ?? 0);
        $memberId = (int)($user['workspace_member_id'] ?? 0);

        if ($workspaceId === 0 || $memberId === 0 || $slug === '') {
            return 0;
        }

        $stmt = self::db()->prepare("
            SELECT conv.id
            FROM channels c
            JOIN conversations conv ON conv.channel_id = c.id
            JOIN channel_members cm ON cm.channel_id = c.id
                AND cm.workspace_member_id = ?
                AND cm.left_at IS NULL
            WHERE c.workspace_id = ?
              AND c.slug = ?
              AND c.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$memberId, $workspaceId, $slug]);

        return (int)$stmt->fetchColumn();
    }
}
