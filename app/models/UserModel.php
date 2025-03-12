<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger\LoggerFactory;

/**
 * UserModel handles user-related database operations.
 *
 * This model interacts with the database to perform CRUD operations
 * related to users, including registration, login, and fetching user data.
 */
class UserModel {
    // Database instance
    private $db;
    
    // Logger instance
    private $logger;

    // Constructor
    public function __construct() {
        // Get the singleton instance of the Database class and initilaize the logger
        $this->db = Database::getInstance();
        $this->logger = LoggerFactory::initializeLogger();
    }

    // Register a new user
    public function register($username, $email, $passwordHash){
        
        $success = $this->db->insert(
        "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)", 
        [$username, $email, $passwordHash]
        );

        if ($success) {
            $this->logger->info("User created successfully", [
                'email' => $email,
            ]);
        } else {
            $this->logger->error("Failed to create user", [
                'email' => $email,
            ]);
        }

        return $success;
    }

    // Find a user by email
    public function findByEmail($email) {
        return $this->db->query("SELECT * FROM users WHERE email = ?", [$email])->fetch();
    }
    
    // Check if username exists
    public function usernameExists($username) {
        $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE username = ?", [$username])->fetch();
        return $result['count'] > 0;
    }

    // Check if email exists
    public function emailExists($email) {
        $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE email = ?", [$email])->fetch();
        return $result['count'] > 0;
    }

    // Get last inserted ID
    public function getLastInsertedId() {
        return $this->db->getLastInsertedId();
    }
}