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
            // Extract the log level, message, and context using regex
            preg_match('/\[(.*?)\] \[(.*?)\] (.*?)(?:\s+\|\s+Context:\s+(.*))?$/', $logEntry, $matches);


            $timestamp = $matches[1] ?? date('Y-m-d H:i:s');
            $level = $matches[2] ?? 'UNKNOWN';
            $message = $matches[3] ?? 'Log format error';
            $context = $matches[4] ?? null;



            // Prepare the SQL statement
            $stmt = $this->pdo->prepare("INSERT INTO logs (level, message, context, created_at) VALUES (:level, :message, :context, :created_at)");
            
            // Execute the query with the extracted values
            $stmt->execute([
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'created_at' => $timestamp
            ]);

        } catch (PDOException $e) {
            // Handle any DB errors
            
            error_log("DatabaseLogger Error: " . $e->getMessage());
        }
    }
}