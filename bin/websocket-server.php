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

$port = $_ENV['WS_PORT'] ?? 8080;

echo "==================================================\n";
echo "🚀 ChatRox Real-Time WebSocket Server\n";
echo "==================================================\n";
echo "Port: {$port}\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "Status: Running...\n";
echo "Press Ctrl+C to terminate the process.\n";
echo "==================================================\n\n";

try {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new ChatServer()
            )
        ),
        (int)$port
    );

    $server->run();
} catch (\Exception $e) {
    echo "❌ Server Error: " . $e->getMessage() . "\n";
    exit(1);
}
