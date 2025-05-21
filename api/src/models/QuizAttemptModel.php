<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Logger\Logger;

class QuizAttemptModel {
    private Database $db;
    private Logger $logger;

    public function __construct() {
        $this->db = $db ?? Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    /**
     * Save a quiz attempt and create corresponding activity records
     * 
     * @param array $data
     * @return int The ID of the new attempt
     */
    public function saveAttempt(array $data): int {
        try {
            $this->db->beginTransaction();

            // Log quiz start
            $this->logger->info("Quiz attempt started", [
                'user_id' => $data['user_id'],
                'quiz_id' => $data['quiz_id']
            ]);

            // Ensure score is a valid number
            $score = isset($data['score']) ? (float)$data['score'] : 0;
            if ($score < 0 || $score > 100) {
                $this->logger->error("Invalid quiz score", [
                    'user_id' => $data['user_id'],
                    'quiz_id' => $data['quiz_id'],
                    'score' => $score
                ]);
                throw new \InvalidArgumentException('Score must be between 0 and 100');
            }

            // Save the quiz attempt
            $sql = "
                INSERT INTO quiz_attempts (
                    user_id,
                    quiz_id,
                    score,
                    time_taken,
                    completed_at
                ) VALUES (
                    :user_id,
                    :quiz_id,
                    :score,
                    :time_taken,
                    NOW()
                )
            ";

            $params = [
                'user_id' => $data['user_id'],
                'quiz_id' => $data['quiz_id'],
                'score' => $score,
                'time_taken' => $data['time_taken']
            ];

            $this->db->query($sql, $params);
            $attemptId = $this->db->lastInsertId();

            // Save question responses if provided
            if (!empty($data['answers'])) {
                $responseModel = new QuestionResponseModel();
                foreach ($data['answers'] as $answer) {
                    $responseModel->create([
                        'attempt_id' => $attemptId,
                        'question_id' => $answer['question_id'],
                        'answer_id' => $answer['answer_id'] ?? null,
                        'submitted_code' => $answer['code'] ?? null,
                        'output' => $answer['output'] ?? null,
                        'is_correct' => $answer['is_correct'] ?? false
                    ]);
                }
            }

            // Get user stats and streak for achievement checking
            $stats = $this->getUserStats($data['user_id']);
            $streak = $this->getUserStreak($data['user_id']);

            // Check for achievements
            $achievementModel = new AchievementModel();
            $unlockedAchievements = $achievementModel->checkQuizAchievements(
                $data['user_id'],
                $data['quiz_id'],
                $score,
                $stats,
                $streak
            );

            // Get quiz title
            $quizSql = "SELECT title FROM quizzes WHERE id = :quiz_id";
            $quizStmt = $this->db->query($quizSql, ['quiz_id' => $data['quiz_id']]);
            $quiz = $quizStmt->fetch(\PDO::FETCH_ASSOC);
            $quizTitle = $quiz ? $quiz['title'] : 'Quiz';

            // Record the quiz completion in activities
            $activityModel = new ActivityModel($this->db);
            $activityModel->create([
                'user_id' => $data['user_id'],
                'type' => 'quiz_completed',
                'title' => "Completed {$quizTitle}",
                'quiz_id' => $data['quiz_id'],
                'quiz_attempt_id' => $attemptId
            ]);

            $this->db->commit();

            // Log successful quiz completion
            $this->logger->info("Quiz attempt completed successfully", [
                'user_id' => $data['user_id'],
                'quiz_id' => $data['quiz_id'],
                'attempt_id' => $attemptId,
                'score' => $score,
                'time_taken' => $data['time_taken']
            ]);

            return $attemptId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            
            // Log quiz attempt failure
            $this->logger->error("Quiz attempt failed", [
                'user_id' => $data['user_id'],
                'quiz_id' => $data['quiz_id'],
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get all attempts for a user with their type scores
     */
    public function getUserAttempts(int $userId, bool $includeTypeScores = false): array {
        $sql = "
            SELECT 
                qa.id,
                qa.quiz_id,
                qa.score,
                qa.time_taken,
                qa.completed_at,
                q.title as quiz_title,
                (
                    SELECT type 
                    FROM questions 
                    WHERE quiz_id = q.id 
                    LIMIT 1
                ) as quiz_type
            FROM quiz_attempts qa
            JOIN quizzes q ON qa.quiz_id = q.id
            WHERE qa.user_id = :user_id
            ORDER BY qa.completed_at DESC
        ";

        $stmt = $this->db->query($sql, [
            'user_id' => $userId
        ]);

        $attempts = $stmt->fetchAll(\PDO::FETCH_ASSOC);


        // Ensure scores are properly formatted as numbers
        foreach ($attempts as &$attempt) {
            
            // Convert score to float and ensure it's not null
            $attempt['score'] = $attempt['score'] !== null ? (float)$attempt['score'] : 0;
            
            $attempt['time_taken'] = (int)$attempt['time_taken'];
        }

        if ($includeTypeScores) {
            // Calculate type scores based on quiz type
            foreach ($attempts as &$attempt) {
                // Default to multiple_choice if no type is set
                $quizType = $attempt['quiz_type'] ?? 'multiple_choice';
                $typeScores = [
                    'coding' => $quizType === 'coding' ? $attempt['score'] : 0,
                    'multiple_choice' => $quizType === 'multiple_choice' ? $attempt['score'] : 0
                ];
                $attempt['type_scores'] = $typeScores;
            }
        }


        return $attempts;
    }

    /**
     * Get type scores for a specific attempt
     */
    private function getAttemptTypeScores(int $attemptId): array {
        $sql = "
            SELECT 
                q.type,
                AVG(qa.score) as avg_score
            FROM quiz_attempt_questions qa
            JOIN questions q ON qa.question_id = q.id
            WHERE qa.attempt_id = :attempt_id
            GROUP BY q.type
        ";

        $stmt = $this->db->query($sql, [
            'attempt_id' => $attemptId
        ]);

        $scores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Format scores by type
        $typeScores = [
            'coding' => 0,
            'multiple_choice' => 0
        ];
        
        foreach ($scores as $score) {
            $typeScores[$score['type']] = (float)$score['avg_score'];
        }

        return $typeScores;
    }

    /**
     * Get user's current streak (consecutive days with completed quizzes)
     */
    public function getUserStreak(int $userId): array {
        $sql = "
            WITH RECURSIVE dates AS (
                SELECT CURRENT_DATE as date
                UNION ALL
                SELECT DATE_SUB(date, INTERVAL 1 DAY)
                FROM dates
                WHERE date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            ),
            quiz_dates AS (
                SELECT DISTINCT DATE(completed_at) as quiz_date
                FROM quiz_attempts
                WHERE user_id = :user_id
                AND completed_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            )
            SELECT COUNT(*) as days
            FROM (
                SELECT d.date
                FROM dates d
                LEFT JOIN quiz_dates qd ON d.date = qd.quiz_date
                WHERE qd.quiz_date IS NOT NULL
                ORDER BY d.date DESC
                LIMIT 1
            ) as streak
        ";

        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: ['days' => 0];
    }

    /**
     * Get user's quiz statistics
     */
    public function getUserStats(int $userId): array {
        $sql = "
            SELECT 
                COUNT(*) as quizzes_taken,
                AVG(score) as average_score
            FROM quiz_attempts 
            WHERE user_id = :user_id
        ";

        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: ['quizzes_taken' => 0, 'average_score' => 0];
    }

    /**
     * Get user's rank based on average quiz scores
     */
    public function getUserRank(int $userId): array {
        $sql = "
            WITH user_scores AS (
                SELECT 
                    user_id,
                    AVG(score) as avg_score,
                    DENSE_RANK() OVER (ORDER BY AVG(score) DESC) as rank
                FROM quiz_attempts 
                GROUP BY user_id
            )
            SELECT COALESCE(rank, 0) as rank
            FROM user_scores
            WHERE user_id = :user_id
        ";

        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: ['rank' => 0];
    }

    /**
     * Create a new quiz attempt
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO quiz_attempts (
            user_id,
            quiz_id,
            score,
            time_taken,
            completed_at
        ) VALUES (
            :user_id,
            :quiz_id,
            :score,
            :time_taken,
            :completed_at
        )";

        $this->db->query($sql, [
            'user_id' => $data['user_id'],
            'quiz_id' => $data['quiz_id'],
            'score' => $data['score'],
            'time_taken' => $data['time_taken'],
            'completed_at' => $data['completed_at']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Get a quiz attempt by ID
     */
    public function getById(int $id): array|false
    {
        $sql = "SELECT * FROM quiz_attempts WHERE id = :id";
        return $this->db->query($sql, ['id' => $id])->fetch();
    }

    /**
     * Get quiz attempts for a user or quiz
     */
    public function getAttempts(?int $userId = null, ?int $quizId = null): array
    {
        $sql = "SELECT * FROM quiz_attempts WHERE 1=1";
        $params = [];

        if ($userId) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }

        if ($quizId) {
            $sql .= " AND quiz_id = :quiz_id";
            $params['quiz_id'] = $quizId;
        }

        $sql .= " ORDER BY completed_at DESC";

        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get the most recent attempt for a user and quiz
     */
    public function getRecentAttempt(int $userId, int $quizId): array|false {
        $sql = "
            SELECT *
            FROM quiz_attempts
            WHERE user_id = :user_id
            AND quiz_id = :quiz_id
            ORDER BY completed_at DESC
            LIMIT 1
        ";

        $stmt = $this->db->query($sql, [
            'user_id' => $userId,
            'quiz_id' => $quizId
        ]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Delete a quiz attempt and its associated responses
     */
    public function delete(int $attemptId): bool {
        return $this->db->query("DELETE FROM quiz_attempts WHERE id = ?", [$attemptId]) !== false;
    }

    /**
     * Update a quiz attempt
     * 
     * @param int $attemptId
     * @param array $data
     * @return bool
     */
    public function update(int $attemptId, array $data): bool {
        $sql = "UPDATE quiz_attempts SET ";
        $params = [];
        $updates = [];

        foreach ($data as $key => $value) {
            $updates[] = "$key = :$key";
            $params[$key] = $value;
        }

        $sql .= implode(', ', $updates);
        $sql .= " WHERE id = :id";
        $params['id'] = $attemptId;

        return $this->db->query($sql, $params) !== false;
    }
} 