<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Database class is responsible for handling database operations.
 *
 * This class provides methods for executing queries, inserting data,
 * and getting the last inserted ID. It uses the PDO extension to
 * interact with the database.
 */
class Database {
    // **Singleton Pattern** - Database instance
    private static $instance = null;

    // Database connection
    private $pdo;

    // Constructor
    private function __construct() {
        $config = require __DIR__ . '/../config/database.php'; // Load DB config
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8";

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch as associative array
                PDO::ATTR_EMULATE_PREPARES => false // Prevent SQL injection
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    // **Singleton Pattern** - Get Database Instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // **Execute Query with Binding (SELECT)**
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // **Insert Data & Get Last Insert ID**
    public function insert($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }

    // Get the last inserted ID
    public function getLastInsertedId() {
        return $this->pdo->lastInsertId();
    }
    
}
