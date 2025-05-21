<?php

namespace App\Core;

use App\Core\Logger\Logger;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Database class
 * 
 * This class provides a singleton database connection using the PDO extension.
 * It also includes logging functionality for database operations.
 */
class Database
{
    /**
     * Singleton instance
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * PDO instance
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Logger instance
     */
    private Logger $logger;

    /**
     * Constructor for the Database class.
     * @param array $config
     */
    private function __construct(array $config)
    {
        $this->logger = Logger::getInstance();
        
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            $this->logger->error('Database connection failed', [
                'error' => $e->getMessage(),
                'dsn' => $dsn
            ]);
            throw $e;
        }
    }

    /**
     * Get the singleton instance of the database. Provides a way to override the default configuration.
     * @param array|null $overrideConfig
     * @return self
     */
    public static function getInstance(?array $overrideConfig = null): self
    {
        if (self::$instance === null) {
            $config = $overrideConfig ?? require __DIR__ . '/../config/database.php';
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Reset the singleton (useful in testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Run a generic query with binding parameters.
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logger->error('Database query failed', [
                'error' => $e->getMessage(),
                'sql' => $sql,
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * Select multiple rows.
     */
    public function select(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Select a single row.
     */
    public function find(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Execute a non-select query (update/delete).
     */
    public function execute(string $sql, array $params = []): bool
    {
        return $this->query($sql, $params)->rowCount() > 0;
    }

    /**
     * Insert a record and return last inserted ID.
     */
    public function insert(string $sql, array $params = []): string|false
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }

    /**
     * Get last inserted ID.
     */
    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Get raw PDO instance (optional, for advanced cases or testing).
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}