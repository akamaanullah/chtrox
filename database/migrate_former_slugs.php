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
use App\Core\Database;

// Load environment variables
$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    (new DotEnv($envPath))->load();
}

// Load configurations (DB defines, base url)
require_once dirname(__DIR__) . '/config/config.php';

try {
    $db = Database::connection();
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM channels LIKE 'former_slugs'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $db->exec("ALTER TABLE channels ADD COLUMN former_slugs TEXT NULL AFTER slug");
        echo "✅ Column 'former_slugs' added to 'channels' table successfully.\n";
    } else {
        echo "ℹ️ Column 'former_slugs' already exists in 'channels' table. Skipping.\n";
    }
} catch (\Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
