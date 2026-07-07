<?php

namespace App\Helpers;

class SystemMessage
{
    public const ACTION_CHANNEL_CREATED = 'channel_created';
    public const ACTION_MEMBER_ADDED = 'member_added';
    public const ACTION_MEMBER_REMOVED = 'member_removed';
    public const ACTION_MEMBER_JOINED = 'member_joined';
    public const ACTION_MEMBER_LEFT = 'member_left';

    public static function bodyChannelCreated(): string
    {
        return self::ACTION_CHANNEL_CREATED;
    }

    public static function bodyMemberAdded(string $memberName): string
    {
        return self::ACTION_MEMBER_ADDED . ':' . trim($memberName);
    }

    public static function bodyMemberRemoved(string $memberName): string
    {
        return self::ACTION_MEMBER_REMOVED . ':' . trim($memberName);
    }

    public static function bodyMemberJoined(): string
    {
        return self::ACTION_MEMBER_JOINED;
    }

    public static function bodyMemberLeft(): string
    {
        return self::ACTION_MEMBER_LEFT;
    }

    public static function isSystemType(string $messageType): bool
    {
        return $messageType === 'system';
    }

    public static function post(\PDO $db, int $workspaceId, int $conversationId, string $body, int $senderId, ?string $fallback = null): void
    {
        $stmt = $db->prepare("
            INSERT INTO messages (workspace_id, conversation_id, sender_id, body, message_type)
            VALUES (?, ?, ?, ?, 'system')
        ");
        $stmt->execute([$workspaceId, $conversationId, $senderId, $body]);
    }

    public static function formatDisplay(string $body, string $actorName, string $createdAt): string
    {
        $actorName = trim($actorName) !== '' ? trim($actorName) : 'Someone';
        $date = self::formatEventDate($createdAt);

        if ($body === self::ACTION_CHANNEL_CREATED || str_starts_with($body, self::ACTION_CHANNEL_CREATED . ':')) {
            return $date !== ''
                ? "{$actorName} created this channel on {$date}"
                : "{$actorName} created this channel";
        }

        if (str_starts_with($body, self::ACTION_MEMBER_ADDED . ':')) {
            $memberName = trim(substr($body, strlen(self::ACTION_MEMBER_ADDED . ':')));
            return $memberName !== ''
                ? "{$actorName} added {$memberName}"
                : "{$actorName} added a member";
        }

        if (str_starts_with($body, self::ACTION_MEMBER_REMOVED . ':')) {
            $memberName = trim(substr($body, strlen(self::ACTION_MEMBER_REMOVED . ':')));
            return $memberName !== ''
                ? "{$actorName} removed {$memberName}"
                : "{$actorName} removed a member";
        }

        if ($body === self::ACTION_MEMBER_JOINED) {
            return "{$actorName} joined the channel";
        }

        if ($body === self::ACTION_MEMBER_LEFT) {
            return "{$actorName} left the channel";
        }

        // Legacy bodies stored before structured format
        if (preg_match('/^created channel #/i', $body)) {
            return $date !== ''
                ? "{$actorName} created this channel on {$date}"
                : "{$actorName} created this channel";
        }

        if (preg_match('/^added (.+) to the channel$/i', $body, $matches)) {
            return "{$actorName} added {$matches[1]}";
        }

        if (preg_match('/^removed (.+) from the channel$/i', $body, $matches)) {
            return "{$actorName} removed {$matches[1]}";
        }

        if (preg_match('/^joined the channel$/i', $body)) {
            return "{$actorName} joined the channel";
        }

        if (preg_match('/^left the channel$/i', $body)) {
            return "{$actorName} left the channel";
        }

        return $body;
    }

    private static function formatEventDate(string $createdAt): string
    {
        if (trim($createdAt) === '') {
            return '';
        }

        $timestamp = strtotime($createdAt);
        if ($timestamp === false) {
            return '';
        }

        return date('j/n/y', $timestamp);
    }
}
