<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        // If we have a connection, ping it to check it's still alive
        // (important for long-running WebSocket server processes)
        if (self::$connection instanceof PDO) {
            try {
                self::$connection->query('SELECT 1');
                return self::$connection;
            } catch (PDOException $e) {
                // Connection is stale - reconnect below
                self::$connection = null;
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
                    PDO::ATTR_PERSISTENT => false,
                ]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed.', 0, $e);
        }

        return self::$connection;
    }
}
