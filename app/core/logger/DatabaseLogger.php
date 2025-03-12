<?php
namespace App\Core\Logger;

use PDO;
use PDOException;

/**
 * DatabaseLogger - Logs messages to a MySQL database.
 */
class DatabaseLogger extends AbstractLogger
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function writeLog(string $logEntry): void
    {
        try {
            // Extract the log level and message using regex
            preg_match('/\[(.*?)\] \[(.*?)\] (.*)/', $logEntry, $matches);

            $level = $matches[2] ?? 'UNKNOWN';
            $message = $matches[3] ?? 'Log format error';

            // Prepare the SQL statement
            $stmt = $this->pdo->prepare("INSERT INTO logs (level, message, created_at) VALUES (:level, :message, NOW())");
            
            // Execute the query with the extracted values
            $stmt->execute([
                'level' => $level,
                'message' => $message,
            ]);
        } catch (PDOException $e) {
            // Handle any DB errors
            error_log("DatabaseLogger Error: " . $e->getMessage());
        }
    }
}