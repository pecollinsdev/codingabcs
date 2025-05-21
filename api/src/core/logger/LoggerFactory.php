<?php
namespace App\Core\Logger;

use PDO;
use PDOException;
use RuntimeException;

class LoggerFactory
{
    private static ?LoggerInterface $instance = null;
    private static array $config = [
        'type' => 'database',
        'filePath' => __DIR__ . '/../../log/app.log',
        'dbConfig' => [
            'host' => 'localhost',
            'dbname' => 'logger_db',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8'
        ]
    ];

    private function __construct() {}

    public static function getInstance(): LoggerInterface
    {
        if (self::$instance === null) {
            self::$instance = self::createLogger();
        }
        return self::$instance;
    }

    private static function createLogger(): LoggerInterface
    {
        $type = self::$config['type'];

        switch ($type) {
            case 'file':
                return new FileLogger(self::$config['filePath']);

            case 'database':
                try {
                    $pdo = new PDO(
                        "mysql:host=" . self::$config['dbConfig']['host'] . 
                        ";dbname=" . self::$config['dbConfig']['dbname'] . 
                        ";charset=" . self::$config['dbConfig']['charset'],
                        self::$config['dbConfig']['username'],
                        self::$config['dbConfig']['password'],
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]
                    );
                    return new DatabaseLogger($pdo);
                } catch (PDOException $e) {
                    // Fallback to file logger if database connection fails
                    return new FileLogger(self::$config['filePath']);
                }

            default:
                throw new RuntimeException("Invalid logger type: $type");
        }
    }

    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
        // Reset instance to force recreation with new config
        self::$instance = null;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize a singleton.");
    }
}