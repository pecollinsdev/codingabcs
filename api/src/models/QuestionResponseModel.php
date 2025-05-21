<?php

namespace App\Models;

use App\Core\Database;

class QuestionResponseModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new question response
     */
    public function create(array $data): int|false
    {
        try {
            $sql = "INSERT INTO question_responses (
                attempt_id,
                question_id,
                answer_id,
                submitted_code,
                output,
                is_correct
            ) VALUES (
                :attempt_id,
                :question_id,
                :answer_id,
                :submitted_code,
                :output,
                :is_correct
            )";

            $this->db->query($sql, [
                'attempt_id' => $data['attempt_id'],
                'question_id' => $data['question_id'],
                'answer_id' => $data['answer_id'],
                'submitted_code' => $data['submitted_code'],
                'output' => $data['output'],
                'is_correct' => $data['is_correct'] ? 1 : 0
            ]);

            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Error creating question response: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get responses for a quiz attempt
     */
    public function getByAttemptId(int $attemptId): array
    {
        try {
            $sql = "SELECT * FROM question_responses WHERE attempt_id = :attempt_id";
            return $this->db->query($sql, ['attempt_id' => $attemptId])->fetchAll();
        } catch (\Exception $e) {
            error_log("Error getting question responses: " . $e->getMessage());
            return [];
        }
    }
} 