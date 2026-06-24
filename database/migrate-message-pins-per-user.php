<?php

$rootDir = dirname(__DIR__);

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

$envPath = $rootDir . '/.env';
if (is_file($envPath)) {
    (new App\Core\DotEnv($envPath))->load();
}

require_once $rootDir . '/config/config.php';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$indexes = $pdo->query("SHOW INDEX FROM message_pins WHERE Key_name = 'uq_message_pins'")->fetchAll(PDO::FETCH_ASSOC);
$columns = array_column($indexes, 'Column_name');

if ($columns === ['pinned_by', 'message_id']) {
    echo "message_pins already uses per-user unique key (pinned_by, message_id)\n";
    exit(0);
}

if (in_array('conversation_id', $columns, true) && in_array('message_id', $columns, true)) {
    echo "Migrating message_pins unique key to per-user (pinned_by, message_id)...\n";
    $pdo->exec('ALTER TABLE message_pins DROP INDEX uq_message_pins');
    $pdo->exec('ALTER TABLE message_pins ADD UNIQUE KEY uq_message_pins (pinned_by, message_id)');
    echo "Migration complete.\n";
    exit(0);
}

echo "Unexpected uq_message_pins columns: " . implode(', ', $columns) . "\n";
exit(1);
