<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            // Only ping in CLI mode (important for long-running processes like WebSockets)
            // to avoid adding unnecessary query latency to standard web requests.
            if (PHP_SAPI === 'cli') {
                try {
                    self::$connection->query('SELECT 1');
                    return self::$connection;
                } catch (PDOException $e) {
                    // Connection is stale - reconnect below
                    self::$connection = null;
                }
            } else {
                return self::$connection;
            }
        }

        if (DB_NAME === '') {
            throw new \RuntimeException('Database is not configured yet. Set DB_DATABASE in .env');
        }

        try {
            self::$connection = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => (PHP_SAPI !== 'cli'),
                ]
            );
            self::$connection->exec("SET time_zone = '" . DB_TIMEZONE . "'");
            if (PHP_SAPI === 'cli' || (defined('APP_ENV') && APP_ENV !== 'production')) {
                self::ensureMigrations(self::$connection);
            }
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage() . ' (Host: ' . DB_HOST . ', Port: ' . DB_PORT . ', User: ' . DB_USER . ', Database: ' . DB_NAME . ')', 0, $e);
            }
            throw new \RuntimeException('Database connection failed. Check server configuration.', 0, $e);
        }

        return self::$connection;
    }

    private static function ensureMigrations(PDO $db): void
    {
        try {
            // 1. Add preferred_status to user_presence
            $stmt = $db->query("SHOW COLUMNS FROM user_presence LIKE 'preferred_status'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE user_presence ADD COLUMN preferred_status ENUM('online', 'away', 'dnd') NOT NULL DEFAULT 'online'");
            }

            // 2. Add session_token to websocket_tickets
            $stmt = $db->query("SHOW COLUMNS FROM websocket_tickets LIKE 'session_token'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE websocket_tickets ADD COLUMN session_token VARCHAR(128) NOT NULL AFTER ticket");
            }
            // 3. Auto-heal missing channel conversations
            $stmt = $db->query("
                SELECT c.id, c.workspace_id 
                FROM channels c
                LEFT JOIN conversations conv ON conv.channel_id = c.id
                WHERE conv.id IS NULL
            ");
            $missingChannels = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($missingChannels)) {
                $stmtInsert = $db->prepare("
                    INSERT INTO conversations (workspace_id, type, channel_id)
                    VALUES (?, 'channel', ?)
                ");
                foreach ($missingChannels as $ch) {
                    $stmtInsert->execute([$ch['workspace_id'], $ch['id']]);
                }
            }
        } catch (PDOException $e) {
            // Ignore if errors occur due to tables not existing yet during installer/seeder phase
        }
    }
}
