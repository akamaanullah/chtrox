<?php

$error_code = '500';
$error_title = 'Server Error';
$error_description = 'Something went wrong on our end. Please try again in a moment.';
$error_icon = 'server-off';
$error_detail = '';

if (defined('APP_DEBUG') && APP_DEBUG && !empty($error_message)) {
    $error_detail = (string) $error_message;
}

require __DIR__ . '/layout.php';
