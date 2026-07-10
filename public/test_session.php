<?php
/**
 * ChatRox Session Diagnosis Utility
 */

header('Content-Type: text/html; charset=utf-8');

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/Core/Session.php';

use App\Core\Session;

echo "<h2>Session Diagnostic Tool</h2>";

// 1. Check Session Save Path
$sessionPath = dirname(__DIR__) . '/storage/sessions';
echo "🔍 Configured Session Path: <strong>" . htmlspecialchars($sessionPath) . "</strong><br>";
echo "📁 Directory Exists? " . (is_dir($sessionPath) ? "✅ Yes" : "❌ No") . "<br>";
echo "✍️ Writable? " . (is_writable($sessionPath) ? "✅ Yes" : "❌ No") . "<br>";

if (is_dir($sessionPath)) {
    echo "👤 Owner ID: " . fileowner($sessionPath) . " | Group ID: " . filegroup($sessionPath) . "<br>";
    echo "🔒 Permissions: " . substr(sprintf('%o', fileperms($sessionPath)), -4) . "<br>";
}

// 2. Initialize Session
echo "<h3>Initializing Session...</h3>";
Session::init();

echo "🆔 Session ID: <strong>" . session_id() . "</strong><br>";
echo "🍪 Cookie Name: <strong>" . session_name() . "</strong><br>";
echo "📂 PHP Active Save Path: <strong>" . session_save_path() . "</strong><br>";

// 3. Test Read/Write
if (empty($_SESSION['diag_test'])) {
    $_SESSION['diag_test'] = 'test_' . time();
    echo "📝 Wrote test value: <strong>" . $_SESSION['diag_test'] . "</strong> (Reload this page to see if it persists)<br>";
} else {
    echo "📖 Read test value from previous load: <strong>" . $_SESSION['diag_test'] . "</strong><br>";
    unset($_SESSION['diag_test']);
}

echo "🎟️ Current CSRF Token: <strong>" . Session::csrfToken() . "</strong><br>";
