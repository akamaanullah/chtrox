<?php

namespace App\Helpers;

use App\Core\View;

class MessageDateDivider
{
    public static function dayKey(string $timestamp): string
    {
        if (trim($timestamp) === '') {
            return '';
        }

        $time = strtotime($timestamp);
        if ($time === false) {
            return '';
        }

        return date('Y-m-d', $time);
    }

    public static function labelForDayKey(string $dayKey): string
    {
        if ($dayKey === '') {
            return '';
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('yesterday'));

        if ($dayKey === $today) {
            return 'Today';
        }

        if ($dayKey === $yesterday) {
            return 'Yesterday';
        }

        $time = strtotime($dayKey . ' 12:00:00');
        if ($time === false) {
            return $dayKey;
        }

        return date('j/n/y', $time);
    }

    /** @param array<int, array<string, mixed>> $messages */
    public static function maybeRenderAfter(array $messages, int $index, int $initialVisible = 999999): void
    {
        if ($index >= $initialVisible) {
            return;
        }

        $current = $messages[$index] ?? null;
        if (!$current) {
            return;
        }

        $currentKey = self::dayKey((string)($current['created_at'] ?? ''));
        if ($currentKey === '') {
            return;
        }

        $next = null;
        if ($index + 1 < $initialVisible) {
            $next = $messages[$index + 1] ?? null;
        }
        $nextKey = $next ? self::dayKey((string)($next['created_at'] ?? '')) : null;

        if ($nextKey !== $currentKey) {
            self::renderDivider($currentKey);
        }
    }

    public static function renderDivider(string $dayKey): void
    {
        View::render('partials/chat/date-divider.php', [
            'label' => self::labelForDayKey($dayKey),
            'day_key' => $dayKey,
        ]);
    }
}
