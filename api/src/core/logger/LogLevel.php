<?php
namespace App\Core\Logger;

/**
 * Simulating an Enum for log levels (PHP 8.0 PSR-3)
 * This defines the available logging levels in order of decreasing severity.
 */
class LogLevel
{
    const EMERGENCY = 0; // System is unusable. Immediate action required.
    const ALERT = 1; // Immediate attention needed (e.g., database down, security breach).
    const CRITICAL = 2; // Critical conditions (e.g., application component failure).
    const ERROR = 3; // Runtime errors that require attention but don’t stop execution.
    const WARNING = 4; // Potential issues (e.g., deprecated API usage, incorrect configuration).
    const NOTICE = 5; // Normal but significant events (e.g., unusual API usage).
    const INFO = 6;   // General informational messages.
    const DEBUG = 7;  // Detailed debugging information.

    // Method to get name of the log level.
    public static function getName(int $level): string
    {
        switch ($level) {
            case self::EMERGENCY: return "EMERGENCY";
            case self::ALERT: return "ALERT";
            case self::CRITICAL: return "CRITICAL";
            case self::ERROR: return "ERROR";
            case self::WARNING: return "WARNING";
            case self::NOTICE: return "NOTICE";
            case self::INFO: return "INFO";
            case self::DEBUG: return "DEBUG";
            default:
                throw new \InvalidArgumentException("Invalid log level: $level");
        }
    }
}