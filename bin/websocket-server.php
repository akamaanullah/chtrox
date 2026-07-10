<?php

require dirname(__DIR__) . '/vendor/autoload.php';

// Register local autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $baseDir . $relative . '.php';

    if (is_file($file)) {
        require $file;
    }
});

use App\Core\DotEnv;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\ChatServer;

// Load environment variables
$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    (new DotEnv($envPath))->load();
}

// Load configurations (DB defines, base url)
require_once dirname(__DIR__) . '/config/config.php';

$port = WS_PORT;
$bind = WS_BIND;

echo "==================================================\n";
echo "🚀 ChatRox Real-Time WebSocket Server\n";
echo "==================================================\n";
echo "Bind: {$bind}\n";
echo "Port: {$port}\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "Status: Running...\n";
echo "Press Ctrl+C to terminate the process.\n";
echo "==================================================\n\n";

try {
    $loop = \React\EventLoop\Loop::get();
    $socket = new \React\Socket\Server($bind . ':' . $port, $loop);

    $chatServer = new ChatServer();

    $server = new IoServer(
        new HttpServer(
            new WsServer(
                $chatServer
            )
        ),
        $socket,
        $loop
    );

    // Periodically revalidate active sessions and prune expired conversation caches every 5 minutes (300 seconds)
    $loop->addPeriodicTimer(300, function() use ($chatServer) {
        try {
            $chatServer->revalidateActiveSessions();
            $chatServer->pruneExpiredCache();
        } catch (\Exception $e) {
            echo "⚠️ Periodic timer error: " . $e->getMessage() . "\n";
        }
    });

    // Periodic heartbeat timer (every 30 seconds)
    $loop->addPeriodicTimer(30, function() use ($chatServer) {
        try {
            $chatServer->pingConnections();
        } catch (\Exception $e) {
            echo "⚠️ Heartbeat timer error: " . $e->getMessage() . "\n";
        }
    });

    $server->run();
} catch (\Exception $e) {
    echo "❌ Server Error: " . $e->getMessage() . "\n";
    exit(1);
}
