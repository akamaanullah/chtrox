<?php

/**
 * ChatRox Maintenance Cleanup Script
 *
 * LOW-03: Cleans up expired / used password_reset_tokens
 * LOW-04: Removes stale user_sessions inactive for more than 30 days
 * LOW-05: Rotates / deletes old log files older than 30 days
 *
 * Run via cron:
 *   0 3 * * * php /path/to/chatrox/bin/cleanup.php >> /path/to/chatrox/logs/cleanup.log 2>&1
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/vendor/autoload.php';

// Register local autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $baseDir . $relative . '.php';

    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Database;
use App\Core\DotEnv;

$envPath = APP_ROOT . '/.env';
if (is_file($envPath)) {
    (new DotEnv($envPath))->load();
}

require_once APP_ROOT . '/config/config.php';

$db  = Database::connection();
$now = date('Y-m-d H:i:s');

echo "[{$now}] ChatRox Cleanup Started\n";
echo str_repeat('-', 50) . "\n";

// LOW-03: Password reset tokens
try {
    $stmt = $db->prepare("
        DELETE FROM password_reset_tokens
        WHERE used_at IS NOT NULL
           OR expires_at < NOW() - INTERVAL 7 DAY
    ");
    $stmt->execute();
    echo "[LOW-03] Deleted {$stmt->rowCount()} stale password_reset_tokens\n";
} catch (\Exception $e) {
    echo "[LOW-03] ERROR: " . $e->getMessage() . "\n";
}

// LOW-04: User sessions
try {
    $stmt = $db->prepare("DELETE FROM user_sessions WHERE last_seen_at < NOW() - INTERVAL 30 DAY");
    $stmt->execute();
    echo "[LOW-04] Deleted {$stmt->rowCount()} stale user_sessions\n";
} catch (\Exception $e) {
    echo "[LOW-04] ERROR: " . $e->getMessage() . "\n";
}

// LOW-05: Log rotation (corrected path: storage/logs)
$logDir = APP_ROOT . '/storage/logs';
if (is_dir($logDir)) {
    $threshold = strtotime('-30 days');
    $rotated   = 0;
    foreach (glob($logDir . '/error-*.log') ?: [] as $logFile) {
        $mtime = filemtime($logFile);
        if ($mtime !== false && $mtime < $threshold && unlink($logFile)) {
            $rotated++;
        }
    }
    echo "[LOW-05] Deleted {$rotated} log files older than 30 days\n";
}

// System cache pruning
try {
    $stmt = $db->prepare("DELETE FROM system_cache WHERE expires_at < NOW()");
    $stmt->execute();
    echo "[CACHE]  Pruned {$stmt->rowCount()} expired system_cache entries\n";
} catch (\Exception $e) {
    echo "[CACHE]  ERROR: " . $e->getMessage() . "\n";
}

// WebSocket tickets cleanup
try {
    $stmt = $db->prepare("DELETE FROM websocket_tickets WHERE expires_at < NOW()");
    $stmt->execute();
    echo "[WS-TKT] Deleted {$stmt->rowCount()} expired websocket_tickets\n";
} catch (\Exception $e) {
    echo "[WS-TKT] ERROR: " . $e->getMessage() . "\n";
}

// Rate limits cleanup
try {
    $stmt = $db->prepare("DELETE FROM rate_limits WHERE expires_at < NOW()");
    $stmt->execute();
    echo "[RATE]   Deleted {$stmt->rowCount()} expired rate_limits\n";
} catch (\Exception $e) {
    echo "[RATE]   ERROR: " . $e->getMessage() . "\n";
}

echo str_repeat('-', 50) . "\n";
echo "[" . date('Y-m-d H:i:s') . "] Cleanup Finished\n";
