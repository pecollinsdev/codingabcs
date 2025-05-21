<?php

namespace App\Models;

use App\Core\Database;

class ActivityModel {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Create a new activity
     * 
     * @param array $data
     * @return int The ID of the new activity
     */
    public function create(array $data): int {
        $sql = "
            INSERT INTO user_activities (
                user_id,
                type,
                title,
                quiz_id,
                quiz_attempt_id,
                created_at
            ) VALUES (
                :user_id,
                :type,
                :title,
                :quiz_id,
                :quiz_attempt_id,
                NOW()
            )
        ";

        $this->db->query($sql, [
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'quiz_id' => $data['quiz_id'] ?? null,
            'quiz_attempt_id' => $data['quiz_attempt_id'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Get recent activities for a user
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecentActivities(int $userId, int $limit = 10): array {
        $sql = "
            SELECT 
                ua.id,
                ua.type,
                ua.title,
                ua.quiz_id,
                ua.quiz_attempt_id,
                ua.created_at,
                q.title as quiz_title,
                qa.score as quiz_score
            FROM user_activities ua
            LEFT JOIN quizzes q ON ua.quiz_id = q.id
            LEFT JOIN quiz_attempts qa ON ua.quiz_attempt_id = qa.id
            WHERE ua.user_id = :user_id
            ORDER BY ua.created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->query($sql, [
            'user_id' => $userId,
            'limit' => $limit
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get user's activity count by type
     * 
     * @param int $userId
     * @return array
     */
    public function getActivityCounts(int $userId): array {
        $sql = "
            SELECT 
                type,
                COUNT(*) as count
            FROM user_activities
            WHERE user_id = :user_id
            GROUP BY type
        ";

        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
} 