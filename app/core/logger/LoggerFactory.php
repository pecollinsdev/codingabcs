<?php
namespace App\Core\Logger;

use PDO;
use PDOException;
use RuntimeException;

class LoggerFactory
{
    private static ?LoggerInterface $instance = null;

    private function __construct() {}

    public static function getLogger(string $type, $config = null): LoggerInterface
    {
        if (self::$instance === null) {
            switch ($type) {
                case 'file':
                    self::$instance = new FileLogger($config['filePath'] ?? __DIR__ . '/../../log/app.log');
                    break;

                case 'database':
                    if (!$config instanceof PDO) {
                        throw new RuntimeException("Database logger requires a PDO instance.");
                    }
                    self::$instance = new DatabaseLogger($config);
                    break;

                default:
                    throw new RuntimeException("Invalid logger type: $type");
            }
        }

        return self::$instance;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize a singleton.");
    }

    // Initialize logger (selects between file-based or database-based logging)
    public static function initializeLogger(): LoggerInterface
    {
        $loggerType = 'database'; // Change this to 'file' for file-based logging

        if ($loggerType === 'database') {
            try {
                // Assuming the database connection for logging
                $pdo = new PDO("mysql:host=localhost;dbname=logger_db;charset=utf8", "root", "", [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                return self::getLogger('database', $pdo);
            } catch (PDOException $e) {
                die("Database connection error: " . $e->getMessage());
            }
        }

        // Fallback to file-based logging
        return self::getLogger('file', ['filePath' => __DIR__ . '/../../log/app.log']);
    }
}