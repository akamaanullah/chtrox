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

        // Determine client IP respecting trusted proxy config
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        if ($remoteAddr !== null && !filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            $remoteAddr = null;
        }
        $ip = $remoteAddr;

        $trustedProxies = defined('TRUSTED_PROXIES') ? TRUSTED_PROXIES : [];
        if (!empty($trustedProxies) && $remoteAddr !== null && in_array($remoteAddr, $trustedProxies, true)) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
                foreach ($ips as $candidate) {
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        $ip = $candidate;
                        break;
                    }
                }
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
        $db = self::db();
        $stmt = $db->prepare('
            SELECT id, last_seen_at 
            FROM user_sessions 
            WHERE session_token = ? AND revoked_at IS NULL
            LIMIT 1
        ');
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        // Update last_seen_at at most once every hour to minimize database writes
        if (time() - strtotime($row['last_seen_at']) > 3600) {
            $updateStmt = $db->prepare('
                UPDATE user_sessions 
                SET last_seen_at = NOW() 
                WHERE id = ?
            ');
            $updateStmt->execute([$row['id']]);
        }

        return true;
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

        if (preg_match('/edg|edge/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opr|opera/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent)) {
            $browser = 'Safari';
        }

        return "{$browser} on {$os}";
    }
}
