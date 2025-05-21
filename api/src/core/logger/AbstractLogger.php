<?php
namespace App\Core\Logger;

abstract class AbstractLogger implements LoggerInterface
{
    /**
     * Logs with a specific log level.
     *
     * @param int $level Log level as defined in LogLevel.
     * @param string $message Log message.
     * @param array $context Additional context data.
     */
    public function log(int $level, string $message, array $context = []): void
    {
        // Validate log level
        // Validate log level
        if ($level < LogLevel::EMERGENCY || $level > LogLevel::DEBUG) {
            throw new \InvalidArgumentException("Invalid log level: $level");
        }

        // Format message with context values
        $formattedMessage = $this->interpolateMessage($message, $context);
        
        // Get log level name
        $levelName = LogLevel::getName($level);

        // Get timestamp
        $timestamp = date('Y-m-d H:i:s');

        // Always include context if it exists
        $contextInfo = '';
        if (!empty($context)) {
            $contextInfo = ' | Context: ' . json_encode($context);
        }

        $logEntry = "[$timestamp] [$levelName] $formattedMessage$contextInfo";

        // Call the abstract method for writing logs (implemented by child classes)
        $this->writeLog($logEntry);
    }

    /**
     * Abstract method to be implemented by child classes for writing logs.
     *
     * @param string $logEntry Formatted log message.
     */
    abstract protected function writeLog(string $logEntry): void;

    /**
     * Handles placeholders in the message string using context values.
     *
     * Example:
     * - "User {username} failed to login" with context ["username" => "John"]
     *   -> "User John failed to login"
     *
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function interpolateMessage(string $message, array $context): string
    {
        foreach ($context as $key => $value) {
            // Convert non-string values to JSON for logging clarity
            if (!is_scalar($value)) { 
                $value = json_encode($value);
            }
            $message = str_replace("{" . $key . "}", $value, $message);
        }
        return $message;
    }

    /**
     * Convenience methods for logging with specific levels.
     */
    public function emergency(string $message, array $context = []): void { $this->log(LogLevel::EMERGENCY, $message, $context); }
    public function alert(string $message, array $context = []): void { $this->log(LogLevel::ALERT, $message, $context); }
    public function critical(string $message, array $context = []): void { $this->log(LogLevel::CRITICAL, $message, $context); }
    public function error(string $message, array $context = []): void { $this->log(LogLevel::ERROR, $message, $context); }
    public function warning(string $message, array $context = []): void { $this->log(LogLevel::WARNING, $message, $context); }
    public function notice(string $message, array $context = []): void { $this->log(LogLevel::NOTICE, $message, $context); }
    public function info(string $message, array $context = []): void { $this->log(LogLevel::INFO, $message, $context); }
    public function debug(string $message, array $context = []): void { $this->log(LogLevel::DEBUG, $message, $context); }
}