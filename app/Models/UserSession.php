<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class UserSession extends Model
{
    public static function create(int $userId, string $token): int
    {
        $db = self::db();
        $stmt = $db->prepare('
            INSERT INTO user_sessions (user_id, session_token, device_name, ip_address, user_agent)
            VALUES (:user_id, :session_token, :device_name, :ip_address, :user_agent)
        ');
        $deviceName = self::getDeviceName();
        $ip = null;
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            foreach ($ips as $candidate) {
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    $ip = $candidate;
                    break;
                }
            }
        }
        if ($ip === null && !empty($_SERVER['REMOTE_ADDR'])) {
            if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $stmt->execute([
            'user_id' => $userId,
            'session_token' => $token,
            'device_name' => $deviceName,
            'ip_address' => $ip,
            'user_agent' => $userAgent
        ]);
        return (int) $db->lastInsertId();
    }

    public static function revoke(string $token): bool
    {
        $stmt = self::db()->prepare('
            UPDATE user_sessions 
            SET revoked_at = NOW() 
            WHERE session_token = ? AND revoked_at IS NULL
        ');
        return $stmt->execute([$token]);
    }

    public static function isValid(string $token): bool
    {
        $stmt = self::db()->prepare('
            SELECT COUNT(*) 
            FROM user_sessions 
            WHERE session_token = ? AND revoked_at IS NULL
        ');
        $stmt->execute([$token]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private static function getDeviceName(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($userAgent === '') {
            return 'Unknown Device';
        }

        $os = 'Unknown OS';
        $browser = 'Unknown Browser';

        if (preg_match('/windows|win32/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $os = 'iOS';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
        }

        if (preg_match('/chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edge/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        }

        return "{$browser} on {$os}";
    }
}
