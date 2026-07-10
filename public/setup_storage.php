<?php
/**
 * ChatRox Storage Setup Helper
 * Run this once via browser to create storage folders under correct web owner (e.g. newc_chatrox)
 */

header('Content-Type: text/html; charset=utf-8');

$rootDir = dirname(__DIR__);
$paths = [
    $rootDir . '/storage',
    $rootDir . '/storage/uploads',
    $rootDir . '/storage/uploads/workspace_1',
    $rootDir . '/storage/uploads/workspace_1/2026-07',
    $rootDir . '/storage/sessions',
    $rootDir . '/storage/logs',
];

echo "<h2>System Storage Directory Setup</h2>";

foreach ($paths as $path) {
    if (!is_dir($path)) {
        if (@mkdir($path, 0777, true)) {
            @chmod($path, 0777);
            echo "✅ Created directory: <strong>" . htmlspecialchars(basename($path)) . "</strong><br>";
        } else {
            echo "❌ Failed to create: " . htmlspecialchars($path) . " (Check parent folder ownership)<br>";
        }
    } else {
        @chmod($path, 0777);
        echo "ℹ️ Already exists (Permissions updated to 777): <strong>" . htmlspecialchars(basename($path)) . "</strong><br>";
    }
}

echo "<br><strong>Done! Now try uploading files in ChatRox.</strong>";
