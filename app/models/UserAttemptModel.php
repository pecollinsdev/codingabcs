<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger\LoggerFactory;
use App\Core\Session;

/**
 * UserAttempt model handles the user's quiz attempts and answers.
 *
 * This model is responsible for storing and retrieving the user's quiz attempts and answers.
 * It interacts with the UserPerformance model to save the user's performance data.
 */
class UserAttemptModel {
    // Database instance
    private $db;

    // Logger instance
    private $logger;

    // Constructor
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = LoggerFactory::initializeLogger();
    }

    // Store the user's attempt for a quiz
    public function storeUserAttempt($userId, $quizId, $score) {
        $sql = "INSERT INTO user_attempts (user_id, quiz_id, score) VALUES (?, ?, ?)";
        $this->db->query($sql, [$userId, $quizId, $score]);
        return $this->db->getLastInsertedId();
    }    

    // Save the user's answers for a quiz attempt
    public function saveUserAnswers($attemptId, $answers) {
        $userId = Session::get('user_id');

        // Fetch quiz_id from the attempt
        $quizId = $this->db->query("SELECT quiz_id FROM user_attempts WHERE id = ?", [$attemptId])->fetchColumn();

        if (!$quizId) {
            die("Error: quiz_id not found for attempt $attemptId.");
        }

        $correctCount = 0;
        $totalQuestions = count($answers); // Get total questions attempted

        foreach ($answers as $questionId => $selectedAnswers) {
            $selectedAnswers = (array) $selectedAnswers; // Ensure it's an array for multiple selections

            // Fetch correct answers for this question
            $correctAnswers = $this->db->query(
                "SELECT id FROM answers WHERE question_id = ? AND is_correct = 1", [$questionId]
            )->fetchAll();
            $correctAnswerIds = array_column($correctAnswers, 'id');

            foreach ($selectedAnswers as $selectedAnswerId) {
                $isCorrect = in_array($selectedAnswerId, $correctAnswerIds) ? 1 : 0;
                $correctCount += $isCorrect; // Increment correct answer count

                $this->db->query(
                    "INSERT INTO user_answers (user_id, quiz_id, attempt_id, question_id, selected_answer_id, is_correct) 
                    VALUES (?, ?, ?, ?, ?, ?)", 
                    [$userId, $quizId, $attemptId, $questionId, $selectedAnswerId, $isCorrect]
                );
            }
        }

        // Save the user's performance after storing answers
        $this->saveUserPerformance($userId, $quizId, $totalQuestions, $correctCount);
    }

    // Get user quiz performance details
    public function getQuizAttemptById($attemptId) {
        return $this->db->query("SELECT * FROM user_attempts WHERE id = ?", [$attemptId])->fetch();
    }

    // Retrieve details of the attempt, including questions and selected answers
    public function getQuizAttemptDetails($attemptId) {
        $sql = "
            SELECT q.id AS question_id, 
                   q.question_text, 
                   COALESCE(ua.selected_answer_id, NULL) AS selected_answer_id, 
                   (ua.is_correct = 1) AS is_correct, 
                   (SELECT answer_text FROM answers WHERE question_id = q.id AND is_correct = 1 LIMIT 1) AS correct_answer
            FROM questions q
            LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
            WHERE q.id IN (SELECT question_id FROM user_answers WHERE attempt_id = ?)
        ";
    
        return $this->db->query($sql, [$attemptId, $attemptId])->fetchAll();
    }
    
    // Get the latest attempt ID for a user in a specific quiz
    public function getLastAttemptId($userId, $quizId) {
        return $this->db->query("SELECT id FROM user_attempts WHERE user_id = ? AND quiz_id = ? ORDER BY created_at DESC LIMIT 1", 
               [$userId, $quizId])->fetchColumn();
    }

    // Get overall user performance stats
    public function getUserPerformance($userId) {
        return $this->db->query("SELECT quiz_id, COUNT(*) AS attempts, AVG(score) AS avg_score 
                                 FROM user_attempts WHERE user_id = ? GROUP BY quiz_id", 
               [$userId])->fetchAll();
    }

    // Save the user's performance for a quiz
    public function saveUserPerformance($userId, $quizId, $totalQuestions, $score) {
        $wrongAnswers = $totalQuestions - $score; // Calculate wrong answers
    
        $sql = "INSERT INTO user_performance (user_id, quiz_id, total_questions, correct_answers, wrong_answers) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                total_questions = VALUES(total_questions), 
                correct_answers = VALUES(correct_answers), 
                wrong_answers = VALUES(wrong_answers)";
    
        $this->db->query($sql, [$userId, $quizId, $totalQuestions, $score, $wrongAnswers]);
    
        // Fetch the result after insert
        $result = $this->db->query("SELECT * FROM user_performance WHERE user_id = ? AND quiz_id = ?", 
                   [$userId, $quizId])->fetch();
    }
        
    // Get the user's quiz history
    public function getUserQuizHistory($userId) {
        $sql = "SELECT ua.id AS attempt_id, ua.quiz_id, q.title, ua.score, ua.attempt_date, 
                       up.total_questions 
                FROM user_attempts ua
                JOIN quizzes q ON ua.quiz_id = q.id
                LEFT JOIN user_performance up ON ua.user_id = up.user_id AND ua.quiz_id = up.quiz_id
                WHERE ua.user_id = ?
                ORDER BY ua.attempt_date DESC";
    
        return $this->db->query($sql, [$userId])->fetchAll();
    }    
    
    // Get the user's overall stats
    public function getUserOverallStats($userId) {
        $sql = "SELECT COUNT(ua.id) AS total_attempts, 
                       AVG(ua.score) AS avg_score, 
                       MAX(ua.score) AS best_score,
                       MIN(ua.score) AS worst_score
                FROM user_attempts ua
                WHERE ua.user_id = ?";
    
        return $this->db->query($sql, [$userId])->fetch();
    }

    // Get the user's recent quiz stats
    public function getRecentQuizStats($userId) {
        $sql = "SELECT ua.id AS attempt_id, q.title, ua.score, ua.attempt_date, 
                       up.total_questions 
                FROM user_attempts ua
                JOIN quizzes q ON ua.quiz_id = q.id
                LEFT JOIN user_performance up ON ua.user_id = up.user_id AND ua.quiz_id = up.quiz_id
                WHERE ua.user_id = ?
                ORDER BY ua.attempt_date DESC
                LIMIT 5";
    
        return $this->db->query($sql, [$userId])->fetchAll();
    }      

    // Get the user's quiz performance details
    public function getUserAnswersForAttempt($attemptId) {
        $sql = "
            SELECT q.id AS question_id, 
                   q.question_text, 
                   ua.selected_answer_id, 
                   ua.is_correct, 
                   (SELECT answer_text FROM answers WHERE id = ua.selected_answer_id) AS selected_answer,
                   (SELECT answer_text FROM answers WHERE question_id = q.id AND is_correct = 1 LIMIT 1) AS correct_answer
            FROM questions q
            LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
            WHERE q.id IN (SELECT question_id FROM user_answers WHERE attempt_id = ?)
        ";
        
        return $this->db->query($sql, [$attemptId, $attemptId])->fetchAll();
    }

    // Get the total number of attempts for a user
    public function getTotalAttempts($userId) {
        return $this->db->query(
            "SELECT COUNT(*) FROM user_attempts WHERE user_id = ?", [$userId]
        )->fetchColumn();
    }
    
    // Get the paginated history of quiz attempts for a user
    public function getPaginatedHistory($userId, $perPage, $offset) {
        return $this->db->query(
            "SELECT ua.id AS attempt_id, ua.quiz_id, q.title, ua.score, ua.attempt_date, up.total_questions 
             FROM user_attempts ua
             JOIN quizzes q ON ua.quiz_id = q.id
             LEFT JOIN user_performance up ON ua.user_id = up.user_id AND ua.quiz_id = up.quiz_id
             WHERE ua.user_id = ?
             ORDER BY ua.attempt_date DESC
             LIMIT ? OFFSET ?", 
            [$userId, $perPage, $offset]
        )->fetchAll();
    }
}
