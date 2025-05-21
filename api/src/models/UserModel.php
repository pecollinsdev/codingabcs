<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Logger\Logger;

class UserModel
{
    private Database $db;

    /**
     * Logger instance
     */
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    /**
     * Find a user by email
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        return $this->db->find($sql, ['email' => $email]);
    }

    /**
     * Find a user by username
     */
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
        return $this->db->find($sql, ['username' => $username]);
    }

    /**
     * Find a user by ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        return $this->db->find($sql, ['id' => $id]);
    }

    /**
     * Create a new user
     */
    public function create(array $user): string|false
    {
        $sql = "INSERT INTO users (username, email, password, role, is_active, avatar, created_at) 
                VALUES (:username, :email, :password, :role, :is_active, :avatar, NOW())";  
    
        $userId = $this->db->insert($sql, [
            'username'  => $user['username'],
            'email'     => $user['email'],
            'password'  => $user['password'],
            'role'      => $user['role'] ?? 'user',
            'is_active' => $user['is_active'] ?? 1,
            'avatar' => $user['avatar'] ?? '/codingabcs/client/assets/images/default-avatar.svg'
        ]);

        if ($userId) {
            $this->logger->info("User created", ['user_id' => $userId]);
            return $userId;
        } 
        else {
            $this->logger->error("User creation failed", ['user' => $user]);
            return false;
        }
    }    

    /**
     * Update an existing user
     */
    public function update(string $userId, array $user): bool
    {
        $sql = "UPDATE users 
                SET username = :username,
                    email = :email,
                    role = :role,
                    is_active = :is_active,
                    avatar = :avatar
                WHERE id = :id";

        $result = $this->db->execute($sql, [
            'id'        => $userId,
            'username'  => $user['username'],
            'email'     => $user['email'],
            'role'      => $user['role'],
            'is_active' => $user['is_active'],
            'avatar' => $user['avatar'] ?? '/codingabcs/client/assets/images/default-avatar.svg'
        ]);

        if ($result) {
            $this->logger->info("User updated", ['user_id' => $userId]);
            return true;
        } 
        else {
            $this->logger->error("User update failed", ['user_id' => $userId, 'user' => $user]);
            return false;
        }
    }

    /**
     * Delete user by ID
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $success = $this->db->execute($sql, ['id' => $id]);

        if ($success) {
            $this->logger->info("User deleted", ['user_id' => $id]);
        }

        return $success;    
    }

    /**
     * List all users (optional: limit and offset)
     */
    public function all(int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        return $this->db->select($sql, [
            'limit'  => $limit,
            'offset' => $offset
        ]);
    }

    public function updateLastLogin(int $userId): bool
    {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $success = $this->db->execute($sql, ['id' => $userId]);
        
        if ($success) {
            $this->logger->info("User logged in successfully", ['user_id' => $userId]);
        }
        
        return $success;
    }

    public function deactivate(int $userId): bool
    {
        $sql = "UPDATE users SET is_active = 0 WHERE id = :id";
        $success = $this->db->execute($sql, ['id' => $userId]);
    
        if ($success) {
            $this->logger->info("User deactivated", ['user_id' => $userId]);
        }
    
        return $success;
    }

    /**
     * Search users by name, email, or other fields
     */
    public function search(string $search, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT * FROM users WHERE (username LIKE :search1 OR email LIKE :search2) ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        return $this->db->select($sql, [
          'search1' => '%' . $search . '%',
          'search2' => '%' . $search . '%',
          'limit' => $limit,
          'offset' => $offset
        ]);
    }

    /**
     * Count users with optional filters
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE 1=1";
        $params = [];

        if (!empty($filters['is_active'])) {
            $sql .= " AND is_active = :is_active";
            $params['is_active'] = $filters['is_active'];
        }

        $result = $this->db->find($sql, $params);
        return (int)($result['count'] ?? 0);
    }
}
