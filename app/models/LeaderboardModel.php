<?php
namespace App\Models;

use App\Core\Database;

/**
 * Leaderboard model handles the leaderboard data.
 *
 * This model is responsible for fetching the leaderboard data from the database.
 * It interacts with the UserPerformance model to get the necessary data.
 * It also updates the leaderboard with the user's latest score.
 */
class LeaderboardModel {
    // Database instance
    private $db;

    // Constructor
    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Get the leaderboard
    public function getLeaderboard() {
        $sql = "SELECT 
                    u.username, 
                    SUM(up.correct_answers) AS total_correct, 
                    (SUM(up.correct_answers) / NULLIF(SUM(up.total_questions), 0)) * 100 AS correct_percentage, 
                    SUM(up.total_questions) AS total_questions,
                    MAX(ua.attempt_date) AS last_attempt
                FROM (
                    SELECT user_id, quiz_id, MAX(attempt_date) AS latest_attempt
                    FROM user_attempts
                    GROUP BY user_id, quiz_id
                ) latest_attempts
                JOIN user_performance up ON latest_attempts.user_id = up.user_id AND latest_attempts.quiz_id = up.quiz_id
                JOIN users u ON up.user_id = u.id
                JOIN user_attempts ua ON up.user_id = ua.user_id AND ua.attempt_date = latest_attempts.latest_attempt
                GROUP BY u.username
                ORDER BY correct_percentage DESC, last_attempt DESC
                LIMIT 10";
    
        return $this->db->query($sql)->fetchAll();
    }
    
    // Update the leaderboard with the user's latest score
    public function updateLeaderboard($userId, $quizId, $score) {
        $existingScore = $this->db->query(
            "SELECT high_score FROM leaderboards WHERE user_id = ? AND quiz_id = ?",
            [$userId, $quizId]
        )->fetchColumn();
    
        if ($existingScore === false) {
            // No existing score, insert a new record
            $sql = "INSERT INTO leaderboards (user_id, quiz_id, high_score, recorded_at) 
                    VALUES (?, ?, ?, NOW())";
            $this->db->query($sql, [$userId, $quizId, $score]);
        } elseif ($score > $existingScore) {
            // Higher score achieved, update the record
            $sql = "UPDATE leaderboards SET high_score = ?, recorded_at = NOW() 
                    WHERE user_id = ? AND quiz_id = ?";
            $this->db->query($sql, [$score, $userId, $quizId]);
        }
    }     
}
?>
