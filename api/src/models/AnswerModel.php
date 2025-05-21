<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Logger\Logger;
use PDOException;

class AnswerModel
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
     * Get all answers for a given question.
     *
     * @param int $questionId
     * @return array
     */
    public function getByQuestionId(int $questionId): array
    {
        return $this->db->query(
            "SELECT * FROM answers WHERE question_id = ? ORDER BY id ASC",
            [$questionId]
        )->fetchAll();
    }

    /**
     * Get a single answer by ID.
     *
     * @param int $id
     * @return array|false
     */
    public function getById(int $id): array|false
    {
        return $this->db->query("SELECT * FROM answers WHERE id = ?", [$id])->fetch();
    }

    /**
     * Create a new answer.
     *
     * @param int $questionId
     * @param string $text
     * @param bool $isCorrect
     * @return int|false
     */
    public function create(int $questionId, string $text, bool $isCorrect = false): int|false
    {
        try {
            $stmt = $this->db->query(
                "INSERT INTO answers (question_id, answer_text, is_correct, created_at) VALUES (?, ?, ?, NOW())",
                [$questionId, $text, $isCorrect]
            );

            return $stmt ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            $this->logger->error('Answer creation failed: ' . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Update an existing answer.
     *
     * @param int $id
     * @param string $text
     * @param bool $isCorrect
     * @return bool
     */
    public function update(int $id, string $text, bool $isCorrect): bool
    {
        try {
            return $this->db->query(
                "UPDATE answers SET answer_text = ?, is_correct = ?, updated_at = NOW() WHERE id = ?",
                [$text, $isCorrect, $id]
            ) !== false;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Validate a batch of answers without saving.
     *
     * @param array $answers
     * @return array List of validation errors
     */
    public function validateBatch(array $answers): array
    {
        $errors = [];
        $hasCorrect = false;

        foreach ($answers as $index => $answer) {
            $text = $answer['answer_text'] ?? '';
            $isCorrect = isset($answer['is_correct']) && $answer['is_correct'];

            if (empty($text)) {
                $errors["$index.answer_text"] = 'Answer text is required.';
                continue;
            }

            if ($isCorrect) {
                $hasCorrect = true;
            }
        }

        if (!$hasCorrect) {
            $errors["correct_answer"] = 'At least one correct answer is required.';
        }

        return $errors;
    }

    /**
     * Batch-create answers.
     *
     * @param int $questionId
     * @param array $answers
     * @return array Empty if successful, errors if any fail
     */
    public function createBatch(int $questionId, array $answers): array
    {
        $errors = $this->validateBatch($answers);
        if (!empty($errors)) return $errors;

        foreach ($answers as $index => $answer) {
            $text = $answer['answer_text'];
            $isCorrect = isset($answer['is_correct']) && $answer['is_correct'];

            $result = $this->create($questionId, $text, $isCorrect);
            if (!$result) {
                $errors["$index.db"] = 'Failed to save answer: ' . $this->getLastError();
            }
        }

        return $errors;
    }

    /**
     * Delete all answers for a given question.
     *
     * @param int $questionId
     * @return bool
     */
    public function deleteByQuestionId(int $questionId): bool
    {
        $result = $this->db->query("DELETE FROM answers WHERE question_id = ?", [$questionId]);
        return $result !== false;
    }

    /**
     * Delete a single answer by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->db->query("DELETE FROM answers WHERE id = ?", [$id]) !== false;
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
