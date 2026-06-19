<?php

$error_code = '404';
$error_title = 'Page Not Found';
$error_description = 'The page you are looking for does not exist, was moved, or the URL may be incorrect.';
$error_icon = 'map-pin-off';
$error_detail = trim((string) ($not_found_path ?? ''));

if ($error_detail === '' || $error_detail === '/') {
    $error_detail = '';
}

require __DIR__ . '/layout.php';
