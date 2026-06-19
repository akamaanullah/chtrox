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

$cols = $pdo->query("SHOW COLUMNS FROM messages LIKE 'forwarded_from_message_id'")->fetch();
if ($cols) {
    echo "forwarded_from_message_id already exists\n";
    exit(0);
}

$pdo->exec('ALTER TABLE messages ADD COLUMN forwarded_from_message_id BIGINT UNSIGNED NULL AFTER reply_to_id');
$pdo->exec('ALTER TABLE messages ADD KEY idx_messages_forwarded_from (forwarded_from_message_id)');

try {
    $pdo->exec('ALTER TABLE messages ADD CONSTRAINT fk_messages_forwarded_from FOREIGN KEY (forwarded_from_message_id) REFERENCES messages (id) ON DELETE SET NULL ON UPDATE CASCADE');
} catch (PDOException $e) {
    echo 'FK skipped: ' . $e->getMessage() . "\n";
}

echo "forwarded_from_message_id added\n";
