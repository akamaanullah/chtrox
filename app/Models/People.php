<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Session;

class People extends Model
{
    public static function directory(): array
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;

        if ($workspaceId === 0) {
            return [];
        }

        $stmt = self::db()->prepare("
            SELECT p.*, pref.timezone
            FROM v_people_directory p
            LEFT JOIN user_preferences pref ON p.user_id = pref.user_id
            WHERE p.workspace_id = ?
            ORDER BY p.display_name ASC
        ");
        $stmt->execute([$workspaceId]);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $timezone = $row['timezone'] ?: 'UTC';
            try {
                $dt = new \DateTime('now', new \DateTimeZone($timezone));
                $timeLabel = $dt->format('h:i A');
            } catch (\Exception $e) {
                $timeLabel = date('h:i A');
            }

            $items[] = [
                'username' => $row['username'],
                'name' => $row['display_name'],
                'role' => strtoupper($row['job_title'] ?: ($row['workspace_role'] ?: 'member')),
                'email' => $row['email'],
                'time' => $timeLabel,
                'avatar' => \App\Core\View::avatar($row['avatar_path']),
                'status' => $row['presence_status'],
            ];
        }

        return $items;
    }

    public static function displayGrid(): array
    {
        return self::directory();
    }

    public static function count(): int
    {
        $user = Session::user();
        $workspaceId = $user['workspace_id'] ?? 0;

        if ($workspaceId === 0) {
            return 0;
        }

        $stmt = self::db()->prepare("
            SELECT COUNT(*) 
            FROM v_people_directory 
            WHERE workspace_id = ?
        ");
        $stmt->execute([$workspaceId]);
        return (int) $stmt->fetchColumn();
    }
}
