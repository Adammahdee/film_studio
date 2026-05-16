<?php

namespace App\Core;

class ErrorHandler
{
    private const LOG_FILE = ROOT_PATH . 'logs/error.log';

    /**
     * Renders a 404 Not Found page.
     *
     * @param string $message Optional custom message.
     */
    public static function render404(string $message = "The page you are looking for could not be found."): void
    {
        http_response_code(404);
        self::renderErrorPage('404 Not Found', $message, ROOT_PATH . 'templates/errors/404.php');
    }

    /**
     * Renders a 403 Forbidden page.
     *
     * @param string $message Optional custom message.
     */
    public static function render403(string $message = "You do not have permission to access this resource."): void
    {
        http_response_code(403);
        self::renderErrorPage('403 Forbidden', $message, ROOT_PATH . 'templates/errors/403.php');
    }

    /**
     * Renders a 500 Internal Server Error page.
     *
     * @param string $message Optional custom message.
     * @param \Throwable|null $exception The exception that caused the error, for logging.
     */
    public static function render500(string $message = "An unexpected error occurred.", ?\Throwable $exception = null): void
    {
        http_response_code(500);
        if ($exception) {
            self::logException($exception);
        }
        self::renderErrorPage('500 Internal Server Error', $message, ROOT_PATH . 'templates/errors/500.php');
    }

    /**
     * Logs an exception to a file.
     *
     * @param \Throwable $exception The exception to log.
     */
    public static function logException(\Throwable $exception): void
    {
        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        file_put_contents(self::LOG_FILE, $logMessage, FILE_APPEND);
    }

    /**
     * Renders the error page template.
     *
     * @param string $title The title for the error page.
     * @param string $message The message to display.
     * @param string $templatePath The path to the error template.
     */
    private static function renderErrorPage(string $title, string $message, string $templatePath): void
    {
        // Ensure the template exists, otherwise fall back to a simple message
        if (file_exists($templatePath)) {
            require_once $templatePath;
        } else {
            echo "<!DOCTYPE html><title>$title</title><h1>$title</h1><p>$message</p>";
        }
        exit();
    }
}