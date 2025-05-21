<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;
use PDOException;

class LeaderboardModel extends Model
{
    /**
     * Get leaderboard data
     * 
     * @param int|null $currentUserId Current user ID
     * @return array{leaderboard: array, total: int, user_rank: array|null}
     */
    public function getLeaderboardData(?int $currentUserId = null): array {
        try {
            // Build the query to get top 5 performers
            $query = "
                SELECT 
                    user_id,
                    username,
                    avatar,
                    average_score,
                    total_quizzes,
                    current_streak,
                    best_score,
                    last_active,
                    rank
                FROM quiz_leaderboard
                ORDER BY rank ASC
                LIMIT 5
            ";

            // Execute main query
            $pdo = $this->db->getPDO();
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get current user's rank and stats if user ID is provided
            $userRank = null;
            if ($currentUserId) {
                $userQuery = "
                    SELECT 
                        user_id,
                        username,
                        avatar,
                        average_score,
                        total_quizzes,
                        current_streak,
                        best_score,
                        last_active,
                        rank
                    FROM quiz_leaderboard
                    WHERE user_id = :user_id
                ";
                $stmt = $pdo->prepare($userQuery);
                $stmt->bindValue(':user_id', $currentUserId, PDO::PARAM_INT);
                $stmt->execute();
                $userRank = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return [
                'leaderboard' => $leaderboard,
                'total' => 5,
                'user_rank' => $userRank ? [
                    'rank' => (int)$userRank['rank'],
                    'name' => $userRank['username'],
                    'avatar' => $userRank['avatar'],
                    'average_score' => (float)$userRank['average_score'],
                    'total_quizzes' => (int)$userRank['total_quizzes'],
                    'current_streak' => (int)$userRank['current_streak'],
                    'best_score' => (float)$userRank['best_score'],
                    'last_active' => $userRank['last_active']
                ] : null
            ];
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Get total count for filtered leaderboard
     */
    private function getFilteredTotal(string $timeRange, string $quizType): int
    {
        $whereClauses = [];
        $params = [];

        if ($timeRange !== 'all') {
            switch ($timeRange) {
                case 'daily':
                    $whereClauses[] = "DATE(qa.completed_at) = CURDATE()";
                    break;
                case 'weekly':
                    $whereClauses[] = "qa.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'monthly':
                    $whereClauses[] = "qa.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }

        if ($quizType !== 'all') {
            $whereClauses[] = "q.category = :quiz_type";
            $params[':quiz_type'] = $quizType;
        }

        $whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $query = "SELECT COUNT(DISTINCT u.id) as total 
                 FROM users u 
                 LEFT JOIN quiz_attempts qa ON u.id = qa.user_id 
                 LEFT JOIN quizzes q ON qa.quiz_id = q.id 
                 $whereClause";

        return $this->db->query($query, $params)->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Get user stats for filtered view
     */
    private function getUserStats(int $userId, string $timeRange, string $quizType): ?array
    {
        $whereClauses = ["qa.user_id = :user_id"];
        $params = [':user_id' => $userId];

        if ($timeRange !== 'all') {
            switch ($timeRange) {
                case 'daily':
                    $whereClauses[] = "DATE(qa.completed_at) = CURDATE()";
                    break;
                case 'weekly':
                    $whereClauses[] = "qa.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'monthly':
                    $whereClauses[] = "qa.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }

        if ($quizType !== 'all') {
            $whereClauses[] = "q.category = :quiz_type";
            $params[':quiz_type'] = $quizType;
        }

        $whereClause = implode(' AND ', $whereClauses);

        $query = "SELECT 
                    AVG(qa.score) as average_score,
                    COUNT(DISTINCT qa.quiz_id) as total_quizzes,
                    MAX(qa.score) as best_score,
                    COUNT(DISTINCT DATE(qa.completed_at)) as current_streak,
                    MAX(qa.completed_at) as last_active
                 FROM quiz_attempts qa
                 LEFT JOIN quizzes q ON qa.quiz_id = q.id
                 WHERE $whereClause";

        return $this->db->query($query, $params)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate user rank for filtered view
     */
    private function calculateUserRank(int $userId, string $timeRange, string $quizType): ?int
    {
        $whereClauses = [];
        $params = [':user_id' => $userId];

        if ($timeRange !== 'all') {
            switch ($timeRange) {
                case 'daily':
                    $whereClauses[] = "DATE(qa.completed_at) = CURDATE()";
                    break;
                case 'weekly':
                    $whereClauses[] = "qa.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'monthly':
                    $whereClauses[] = "qa.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }

        if ($quizType !== 'all') {
            $whereClauses[] = "q.category = :quiz_type";
            $params[':quiz_type'] = $quizType;
        }

        $whereClause = !empty($whereClauses) ? 'AND ' . implode(' AND ', $whereClauses) : '';

        $query = "SELECT COUNT(*) + 1 as rank
                 FROM (
                     SELECT u.id, AVG(qa.score) as avg_score
                     FROM users u
                     LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
                     LEFT JOIN quizzes q ON qa.quiz_id = q.id
                     WHERE qa.completed_at IS NOT NULL $whereClause
                     GROUP BY u.id
                     HAVING avg_score > (
                         SELECT AVG(qa2.score)
                         FROM quiz_attempts qa2
                         LEFT JOIN quizzes q2 ON qa2.quiz_id = q2.id
                         WHERE qa2.user_id = :user_id $whereClause
                     )
                 ) ranked_users";

        $result = $this->db->query($query, $params)->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['rank'] : null;
    }

    /**
     * Get achievements for a user
     */
    public function getAchievements(?int $userId): array
    {
        if (!$userId) {
            return [];
        }

        $query = "SELECT 
                    a.id,
                    a.title,
                    a.description,
                    a.icon,
                    a.unlock_condition,
                    CASE WHEN ua.id IS NOT NULL THEN 1 ELSE 0 END as unlocked,
                    ua.unlocked_at
                FROM achievements a
                LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = :user_id
                ORDER BY a.id";

        return $this->db->query($query, [':user_id' => $userId])->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Manually trigger leaderboard cache update
     */
    public function refreshLeaderboardCache(): void
    {
        $this->db->query("CALL update_leaderboard_cache()", []);
    }
} 