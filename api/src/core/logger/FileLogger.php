<?php
namespace App\Core\Logger;

use RuntimeException;

class FileLogger extends AbstractLogger
{
    private string $filePath;

    /**
     * Constructor to initialize log file path.
     *
     * @param string $filePath Path to the log file.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

        // Ensure the log directory exists
        $logDirectory = dirname($this->filePath);
        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0777, true);
        }
    }

    /**
     * Writes the log message to the file.
     *
     * @param string $formattedMessage The formatted log message.
     */
    protected function writeLog(string $formattedMessage): void
    {
        try {
            // Open the file in append mode
            $fileHandle = fopen($this->filePath, 'a');

            if ($fileHandle === false) {
                throw new RuntimeException("Failed to open log file: {$this->filePath}");
            }

            // Lock the file before writing to prevent conflicts
            if (flock($fileHandle, LOCK_EX)) {
                fwrite($fileHandle, $formattedMessage . PHP_EOL);
                fflush($fileHandle); // Ensure data is written
                flock($fileHandle, LOCK_UN); // Release lock
            } else {
                throw new RuntimeException("Failed to lock log file: {$this->filePath}");
            }

            fclose($fileHandle);
        } catch (\Exception $e) {
            // Log errors to PHP's error log
            error_log("FileLogger Error: " . $e->getMessage());
        }
    }
}