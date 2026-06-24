<?php

$rootDir = dirname(dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(dirname(__DIR__)) . '/app/';
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

$exists = $pdo->query("SHOW TABLES LIKE 'channel_join_requests'")->fetch();
if ($exists) {
    echo "channel_join_requests table already exists\n";
    exit(0);
}

$pdo->exec(
    'CREATE TABLE channel_join_requests (
' .
    '    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
' .
    '    workspace_id BIGINT UNSIGNED NOT NULL,
' .
    '    channel_id BIGINT UNSIGNED NOT NULL,
' .
    '    workspace_member_id BIGINT UNSIGNED NOT NULL,
' .
    '    status ENUM(\'pending\', \'accepted\', \'rejected\') NOT NULL DEFAULT \'pending\',
' .
    '    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
' .
    '    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
' .
    '    PRIMARY KEY (id),
' .
    '    UNIQUE KEY uq_channel_join_requests (channel_id, workspace_member_id),
' .
    '    KEY idx_channel_join_requests_workspace (workspace_id),
' .
    '    KEY idx_channel_join_requests_channel (channel_id),
' .
    '    KEY idx_channel_join_requests_member (workspace_member_id),
' .
    '    CONSTRAINT fk_channel_join_requests_workspace
' .
    '        FOREIGN KEY (workspace_id) REFERENCES workspaces (id)
' .
    '        ON DELETE CASCADE ON UPDATE CASCADE,
' .
    '    CONSTRAINT fk_channel_join_requests_channel
' .
    '        FOREIGN KEY (channel_id) REFERENCES channels (id)
' .
    '        ON DELETE CASCADE ON UPDATE CASCADE,
' .
    '    CONSTRAINT fk_channel_join_requests_member
' .
    '        FOREIGN KEY (workspace_member_id) REFERENCES workspace_members (id)
' .
    '        ON DELETE CASCADE ON UPDATE CASCADE
' .
    ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
);

echo "channel_join_requests table created\n";
