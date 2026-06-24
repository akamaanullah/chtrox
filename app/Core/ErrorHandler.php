<?php

namespace App\Core;

use Throwable;

class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleFatalError']);

        if (!APP_DEBUG) {
            ini_set('display_errors', '0');
        }
    }

    public static function handleException(Throwable $exception): void
    {
        self::logError($exception);
        self::renderErrorPage(500, APP_DEBUG ? $exception->getMessage() : 'An unexpected error occurred.');
    }

    public static function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (error_reporting() & $level) {
            self::handleException(new \ErrorException($message, 0, $level, $file, $line));
        }

        return true;
    }

    public static function handleFatalError(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::handleException(new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }

    private static function logError(Throwable $exception): void
    {
        $logDir = ROOT_DIR . '/storage/logs';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Date-based log rotation to prevent unbounded file growth
        $logFile = $logDir . '/error-' . date('Y-m-d') . '.log';

        $message = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        error_log($message, 3, $logFile);
    }

    private static function renderErrorPage(int $code, string $message): void
    {
        http_response_code($code);

        while (ob_get_level()) {
            ob_end_clean();
        }

        // Try code-specific template first, then fallback to generic
        $codeTemplate = VIEW_DIR . '/errors/' . $code . '.php';
        $fallbackTemplate = ($code >= 500 || $code === 0)
            ? VIEW_DIR . '/errors/500.php'
            : VIEW_DIR . '/errors/404.php';

        $error_message = $message;
        $not_found_path = $message;

        if (is_file($codeTemplate)) {
            require $codeTemplate;
        } elseif (is_file($fallbackTemplate)) {
            require $fallbackTemplate;
        } else {
            echo '<h1>Error</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        exit;
    }
}
