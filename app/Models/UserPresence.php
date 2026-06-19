<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class UserPresence extends Model
{
    public static function initialize(int $userId): bool
    {
        $stmt = self::db()->prepare('
            INSERT INTO user_presence (user_id, status, last_seen_at)
            VALUES (:user_id, "offline", NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ');
        return $stmt->execute(['user_id' => $userId]);
    }

    public static function setOnline(int $userId): bool
    {
        $stmt = self::db()->prepare('
            INSERT INTO user_presence (user_id, status, last_seen_at)
            VALUES (:user_id, "online", NOW())
            ON DUPLICATE KEY UPDATE status = "online", last_seen_at = NOW()
        ');
        return $stmt->execute(['user_id' => $userId]);
    }

    public static function setOffline(int $userId): bool
    {
        $stmt = self::db()->prepare('
            INSERT INTO user_presence (user_id, status, last_seen_at)
            VALUES (:user_id, "offline", NOW())
            ON DUPLICATE KEY UPDATE status = "offline", last_seen_at = NOW()
        ');
        return $stmt->execute(['user_id' => $userId]);
    }
}
