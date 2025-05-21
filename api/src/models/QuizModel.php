<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Logger\Logger;
use PDOException;

class QuizModel
{
    private Database $db;
    private string $lastError = '';
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    /**
     * Get all quizzes with optional filtering and sorting
     */
    public function getAll(int $limit = 20, int $offset = 0, bool $includeInactive = false, array $filters = []): array
    {
        try {
            $sql = "SELECT q.*, COUNT(qu.id) as question_count FROM quizzes q 
                    LEFT JOIN questions qu ON q.id = qu.quiz_id 
                    WHERE 1=1";
            $params = [];

            if (!$includeInactive) {
                $sql .= " AND q.is_active = 1";
            }

            // Apply search filter
            if (!empty($filters['search'])) {
                $sql .= " AND q.title LIKE :search";
                $params[':search'] = "%{$filters['search']}%";
            }

            // Apply difficulty filter
            if (!empty($filters['difficulty'])) {
                $sql .= " AND q.level = :difficulty";
                $params[':difficulty'] = $filters['difficulty'];
            }

            // Apply category filter
            if (!empty($filters['category'])) {
                $sql .= " AND q.category = :category";
                $params[':category'] = $filters['category'];
            }

            // Group by quiz to get the count
            $sql .= " GROUP BY q.id";

            // Apply sorting
            switch ($filters['sort'] ?? 'newest') {
                case 'oldest':
                    $sql .= " ORDER BY q.created_at ASC";
                    break;
                case 'difficulty_asc':
                    $sql .= " ORDER BY 
                        CASE q.level 
                            WHEN 'beginner' THEN 1 
                            WHEN 'intermediate' THEN 2 
                            WHEN 'advanced' THEN 3 
                        END ASC";
                    break;
                case 'difficulty_desc':
                    $sql .= " ORDER BY 
                        CASE q.level 
                            WHEN 'beginner' THEN 1 
                            WHEN 'intermediate' THEN 2 
                            WHEN 'advanced' THEN 3 
                        END DESC";
                    break;
                case 'newest':
                default:
                    $sql .= " ORDER BY q.created_at DESC";
                    break;
            }

            // Add pagination
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;

            $result = $this->db->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
            
            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error("Failed to retrieve quizzes", [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Get a quiz by its ID.
     *
     * @param int $id
     * @return array|false
     */
    public function getById(int $id): array|false
    {
        try {
            $sql = "SELECT q.*, COUNT(qu.id) as question_count 
                    FROM quizzes q 
                    LEFT JOIN questions qu ON q.id = qu.quiz_id 
                    WHERE q.id = ? 
                    GROUP BY q.id";
            
            $result = $this->db->query($sql, [$id])->fetch();
            
            if (!$result) {
                $this->logger->warning("Quiz not found", [
                    'quiz_id' => $id
                ]);
            }
            
            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error("Failed to retrieve quiz", [
                'quiz_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create a new quiz.
     *
     * @param array $data Quiz data including title, description, category, level, and is_active.
     * @return int|false The inserted quiz ID or false on failure.
     */
    public function create(array $data): int|false
    {
        try {
            $stmt = $this->db->query(
                "INSERT INTO quizzes (title, description, category, level, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                [
                    $data['title'] ?? '',
                    $data['description'] ?? '',
                    $data['category'] ?? null,
                    $data['level'] ?? 'beginner',
                    $data['is_active'] ?? 1
                ]
            );

            $quizId = $stmt ? $this->db->lastInsertId() : false;
            
            if ($quizId) {
                $this->logger->info("Quiz created successfully", [
                    'quiz_id' => $quizId,
                    'title' => $data['title']
                ]);
            } else {
                $this->logger->error("Failed to create quiz", [
                    'data' => $data
                ]);
            }
            
            return $quizId;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error("Failed to create quiz", [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update a quiz by ID with only the fields provided.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = [];

            foreach (['title', 'description', 'category', 'level', 'is_active'] as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($fields)) {
                $this->lastError = 'No fields provided to update.';
                $this->logger->warning("No fields provided for quiz update", [
                    'quiz_id' => $id
                ]);
                return false;
            }

            $params[] = $id;
            $sql = "UPDATE quizzes SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
            $success = $this->db->query($sql, $params) !== false;
            
            if ($success) {
                $this->logger->info("Quiz updated successfully", [
                    'quiz_id' => $id,
                    'updated_fields' => array_keys($data)
                ]);
            } else {
                $this->logger->error("Failed to update quiz", [
                    'quiz_id' => $id,
                    'data' => $data
                ]);
            }
            
            return $success;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error("Failed to update quiz", [
                'quiz_id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete a quiz and all its associated questions and answers.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            // Start a transaction
            $this->db->beginTransaction();

            // 1. First, get all quiz attempt IDs for this quiz
            $attemptIds = $this->db->query(
                "SELECT id FROM quiz_attempts WHERE quiz_id = ?",
                [$id]
            )->fetchAll(\PDO::FETCH_COLUMN);

            // 2. Delete all user activities related to quiz attempts
            if (!empty($attemptIds)) {
                $placeholders = str_repeat('?,', count($attemptIds) - 1) . '?';
                $this->db->query(
                    "DELETE FROM user_activities WHERE quiz_attempt_id IN ($placeholders)",
                    $attemptIds
                );
            }

            // 3. Delete all user activities directly related to the quiz
            $this->db->query(
                "DELETE FROM user_activities WHERE quiz_id = ?",
                [$id]
            );

            // 4. Delete all quiz attempts
            $this->db->query(
                "DELETE FROM quiz_attempts WHERE quiz_id = ?",
                [$id]
            );

            // 5. Get all question IDs for this quiz
            $questionIds = $this->db->query(
                "SELECT id FROM questions WHERE quiz_id = ?",
                [$id]
            )->fetchAll(\PDO::FETCH_COLUMN);

            // 6. Delete all answers for these questions
            if (!empty($questionIds)) {
                $placeholders = str_repeat('?,', count($questionIds) - 1) . '?';
                $this->db->query(
                    "DELETE FROM answers WHERE question_id IN ($placeholders)",
                    $questionIds
                );
            }

            // 7. Delete all questions
            $this->db->query(
                "DELETE FROM questions WHERE quiz_id = ?",
                [$id]
            );

            // 8. Finally, delete the quiz itself
            $success = $this->db->query(
                "DELETE FROM quizzes WHERE id = ?",
                [$id]
            ) !== false;

            if ($success) {
                $this->db->commit();
                $this->logger->info("Quiz deleted successfully", [
                    'quiz_id' => $id,
                    'deleted_attempts' => count($attemptIds),
                    'deleted_questions' => count($questionIds)
                ]);
                return true;
            } else {
                $this->db->rollBack();
                $this->lastError = "Failed to delete quiz";
                $this->logger->error("Failed to delete quiz", [
                    'quiz_id' => $id
                ]);
                return false;
            }
        } catch (PDOException $e) {
            // Roll back the transaction on any error
            $this->db->rollBack();
            $this->lastError = $e->getMessage();
            $this->logger->error("Failed to delete quiz", [
                'quiz_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Search quizzes by title keyword with optional pagination.
     *
     * @param string $keyword
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(string $keyword, int $limit = 20, int $offset = 0): array
    {
        $like = '%' . $keyword . '%';
        return $this->db->query(
            "SELECT q.*, COUNT(qu.id) as questions_count 
             FROM quizzes q 
             LEFT JOIN questions qu ON q.id = qu.quiz_id 
             WHERE q.title LIKE ? AND q.is_active = 1 
             GROUP BY q.id 
             ORDER BY q.created_at DESC 
             LIMIT ? OFFSET ?",
            [$like, $limit, $offset]
        )->fetchAll();
    }

    /**
     * Get all active quizzes.
     *
     * @return array
     */
    public function getActive(): array
    {
        return $this->db->query(
            "SELECT q.*, COUNT(qu.id) as questions_count 
             FROM quizzes q 
             LEFT JOIN questions qu ON q.id = qu.quiz_id 
             WHERE q.is_active = 1 
             GROUP BY q.id 
             ORDER BY q.created_at DESC"
        )->fetchAll();
    }

    /**
     * Count total quizzes with optional filtering
     */
    public function count(bool $activeOnly = true, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM quizzes WHERE 1=1";
        $params = [];

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $sql .= " AND title LIKE ?";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
        }

        // Apply difficulty filter
        if (!empty($filters['difficulty'])) {
            $sql .= " AND level = ?";
            $params[] = $filters['difficulty'];
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }

        return (int)$this->db->query($sql, $params)->fetchColumn();
    }

    /**
     * Get the last error message.
     *
     * @return string
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }
}

