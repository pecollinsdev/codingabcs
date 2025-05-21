<?php

namespace App\Core;

use Throwable;
use App\Core\Response;
use App\Core\Logger\Logger;

class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleException(Throwable $e): void
    {
        Logger::getInstance()->critical('Uncaught exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        Response::error('Internal Server Error', 500)->sendAndExit();
    }

    public static function handleError(int $severity, string $message, string $file, int $line): void
    {
        Logger::getInstance()->error('PHP Error', compact('severity', 'message', 'file', 'line'));

        if (!(error_reporting() & $severity)) {
            return; // Let PHP handle it
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
}
