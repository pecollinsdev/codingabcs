<?php

namespace App\Models;

use App\Core\Database;

class AchievementModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all achievements for a user
     */
    public function getUserAchievements(int $userId): array {
        $sql = "
            SELECT 
                a.id,
                a.title,
                a.description,
                a.icon,
                a.unlock_condition,
                CASE WHEN ua.id IS NOT NULL THEN 1 ELSE 0 END as unlocked,
                ua.unlocked_at
            FROM achievements a
            LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = :user_id
            ORDER BY a.id
        ";

        return $this->db->query($sql, [':user_id' => $userId])->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Check if a user has unlocked an achievement
     */
    public function hasAchievement(int $userId, int $achievementId): bool {
        $sql = "SELECT id FROM user_achievements WHERE user_id = :user_id AND achievement_id = :achievement_id";
        $result = $this->db->query($sql, [
            ':user_id' => $userId,
            ':achievement_id' => $achievementId
        ])->fetch(\PDO::FETCH_ASSOC);

        return $result !== false;
    }

    /**
     * Unlock an achievement for a user
     */
    public function unlockAchievement(int $userId, int $achievementId): bool {
        if ($this->hasAchievement($userId, $achievementId)) {
            return false;
        }

        $sql = "
            INSERT INTO user_achievements (
                user_id,
                achievement_id,
                unlocked_at
            ) VALUES (
                :user_id,
                :achievement_id,
                NOW()
            )
        ";

        return $this->db->query($sql, [
            ':user_id' => $userId,
            ':achievement_id' => $achievementId
        ]) !== false;
    }

    /**
     * Check and unlock achievements based on quiz performance
     */
    public function checkQuizAchievements(int $userId, int $quizId, float $score, ?array $stats = null, ?array $streak = null): array {
        $unlockedAchievements = [];
        
        // Get user's quiz statistics if not provided
        if ($stats === null) {
            $quizAttemptModel = new QuizAttemptModel();
            $stats = $quizAttemptModel->getUserStats($userId);
        }
        
        if ($streak === null) {
            $quizAttemptModel = new QuizAttemptModel();
            $streak = $quizAttemptModel->getUserStreak($userId);
        }
        
        // Get all achievements
        $sql = "SELECT * FROM achievements";
        $achievements = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($achievements as $achievement) {
            if ($this->hasAchievement($userId, $achievement['id'])) {
                continue;
            }

            $unlocked = false;
            switch ($achievement['unlock_condition']) {
                case 'Complete 1 quiz':
                    $unlocked = $stats['quizzes_taken'] >= 1;
                    break;
                case 'Get perfect score':
                    $unlocked = $score >= 100;
                    break;
                case 'Complete 10 quizzes':
                    $unlocked = $stats['quizzes_taken'] >= 10;
                    break;
                case 'Maintain high average':
                    $unlocked = $stats['average_score'] >= 90;
                    break;
                case 'Weekly streak':
                    $unlocked = $streak['days'] >= 7;
                    break;
                case 'Top rank':
                    $quizAttemptModel = new QuizAttemptModel();
                    $rank = $quizAttemptModel->getUserRank($userId);
                    $unlocked = $rank['rank'] === 1;
                    break;
                case 'All categories':
                    // Get all unique categories from quizzes
                    $sql = "SELECT DISTINCT category FROM quizzes WHERE is_active = 1 AND category IS NOT NULL";
                    $allCategories = $this->db->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
                    
                    // Get categories the user has completed
                    $sql = "
                        SELECT DISTINCT q.category 
                        FROM quiz_attempts qa 
                        JOIN quizzes q ON qa.quiz_id = q.id 
                        WHERE qa.user_id = :user_id 
                        AND qa.completed_at IS NOT NULL 
                        AND q.category IS NOT NULL
                    ";
                    $completedCategories = $this->db->query($sql, [':user_id' => $userId])->fetchAll(\PDO::FETCH_COLUMN);
                    
                    // Check if user has completed at least one quiz in each category
                    $unlocked = !empty($allCategories) && count(array_intersect($allCategories, $completedCategories)) === count($allCategories);
                    break;
            }

            if ($unlocked) {
                if ($this->unlockAchievement($userId, $achievement['id'])) {
                    $unlockedAchievements[] = $achievement;
                    
                    // Record the achievement in user activities
                    $activityModel = new ActivityModel($this->db);
                    $activityModel->create([
                        'user_id' => $userId,
                        'type' => 'achievement',
                        'title' => 'Earned "' . $achievement['title'] . '" Achievement',
                        'quiz_id' => $quizId
                    ]);
                }
            }
        }

        return $unlockedAchievements;
    }
} 