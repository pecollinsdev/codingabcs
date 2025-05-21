<?php

namespace App\Models;

use App\Core\Database;
use PDOException;

class QuestionModel
{
    private Database $db;
    private string $lastError = '';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all questions for a quiz.
     *
     * @param int $quizId
     * @return array
     */
    public function getByQuizId(int $quizId): array
    {
        $questions = $this->db->query(
            "SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC",
            [$quizId]
        )->fetchAll();
        return $questions;
    }

    /**
     * Get a single question by ID.
     *
     * @param int $id
     * @return array|false
     */
    public function getById(int $id): array|false
    {
        return $this->db->query("SELECT * FROM questions WHERE id = ?", [$id])->fetch();
    }

    /**
     * Create a new question.
     *
     * @param array $data
     * @return int|false Inserted ID or false
     */
    public function create(array $data): int|false
    {
        try {
            $stmt = $this->db->query(
                "INSERT INTO questions (quiz_id, question_text, type, starter_code, language, expected_output, hidden_input, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $data['quiz_id'],
                    $data['question_text'],
                    $data['type'] ?? 'multiple_choice',
                    $data['starter_code'] ?? null,
                    $data['language'] ?? null,
                    $data['expected_output'] ?? null,
                    $data['hidden_input'] ?? null,
                ]
            );

            return $stmt ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Update a question by ID.
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

            foreach (['question_text', 'type', 'starter_code', 'language', 'expected_output', 'hidden_input'] as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($fields)) {
                $this->lastError = 'No fields provided to update.';
                return false;
            }

            $params[] = $id;
            if (isset($data['quiz_id'])) {
                $params[] = $data['quiz_id'];
                $sql = "UPDATE questions SET " . implode(', ', $fields) . " WHERE id = ? AND quiz_id = ?";
            } else {
                $sql = "UPDATE questions SET " . implode(', ', $fields) . " WHERE id = ?";
            }
            return $this->db->query($sql, $params) !== false;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Delete a question.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $result = $this->db->query("DELETE FROM questions WHERE id = ?", [$id]);
        return $result !== false;
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
