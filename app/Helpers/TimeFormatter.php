<?php

namespace App\Helpers;

class TimeFormatter
{
    /**
     * Format presence label and online status.
     */
    public static function formatPresence(string $status, ?string $lastSeenAt): array
    {
        if ($status === 'online') {
            return ['label' => 'Active now', 'online' => true];
        }

        if (!$lastSeenAt) {
            return ['label' => 'Offline', 'online' => false];
        }

        $diff = time() - strtotime($lastSeenAt);
        if ($diff < 60) {
            return ['label' => 'Active just now', 'online' => false];
        }
        if ($diff < 3600) {
            return ['label' => 'Active ' . (int)floor($diff / 60) . 'm ago', 'online' => false];
        }
        if ($diff < 86400) {
            return ['label' => 'Active ' . (int)floor($diff / 3600) . 'h ago', 'online' => false];
        }

        return ['label' => 'Active ' . (int)floor($diff / 86400) . 'd ago', 'online' => false];
    }

    /**
     * Format timestamp of messages for sidebar preview.
     */
    public static function formatMessageTime(string $timestamp): string
    {
        $time = strtotime($timestamp);
        $date = date('Y-m-d', $time);
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('yesterday'));

        if ($date === $today) {
            return date('h:i A', $time);
        } elseif ($date === $yesterday) {
            return 'Yesterday ' . date('h:i A', $time);
        } else {
            return date('M j, h:i A', $time);
        }
    }
}
