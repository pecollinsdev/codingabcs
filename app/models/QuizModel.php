<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger\LoggerFactory;

/**
 * Quiz model handles the quiz data.
 *
 * This model is responsible for fetching the quiz data from the database.
 * It interacts with the QuestionModel to get the necessary data.
 */
class QuizModel {
    // Database instance
    private $db;

    // Logger instance
    private $logger;

    // Question model instance
    private $questionModel;

    // Constructor
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = LoggerFactory::initializeLogger();
        $this->questionModel = new QuestionModel();
    }

    // Get all available quizzes
    public function getAllQuizzes() {
        return $this->db->query("SELECT * FROM quizzes ORDER BY created_at DESC")->fetchAll();
    }

    // Find a quiz by its ID
    public function getQuizById($quizId) {
        return $this->db->query("SELECT * FROM quizzes WHERE id = ?", [$quizId])->fetch();
    }

    // Save quiz progress for a user
    public function saveProgress($userId, $quizId, $answersJson) {
        $sql = "INSERT INTO quiz_progress (user_id, quiz_id, progress_data) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE progress_data = VALUES(progress_data)";
        return $this->db->query($sql, [$userId, $quizId, $answersJson]);
    }

    // Retrieve saved quiz progress
    public function getSavedProgress($userId, $quizId) {
        return $this->db->query("SELECT progress_data FROM quiz_progress WHERE user_id = ? AND quiz_id = ?", 
               [$userId, $quizId])->fetchColumn();
    }

    // Clear quiz progress after submission
    public function clearProgress($userId, $quizId) {
        return $this->db->query("DELETE FROM quiz_progress WHERE user_id = ? AND quiz_id = ?", [$userId, $quizId]);
    }

    // Fetch new quizzes (latest created quizzes)
    public function getNewQuizzes($limit = 5) {
        return $this->db->query(
            "SELECT id, title, description, created_at 
             FROM quizzes 
             ORDER BY created_at DESC 
             LIMIT ?", 
            [$limit]
        )->fetchAll();
    }

    // Fetch popular quizzes (most attempted)
    public function getPopularQuizzes($limit = 5) {
        return $this->db->query(
            "SELECT q.id, q.title, q.description, COUNT(ua.id) AS attempt_count 
             FROM quizzes q
             LEFT JOIN user_attempts ua ON q.id = ua.quiz_id
             GROUP BY q.id
             ORDER BY attempt_count DESC 
             LIMIT ?", 
            [$limit]
        )->fetchAll();
    }
    
    // Fetch filtered quizzes based on search query and category
    public function getFilteredQuizzes($search = '', $category = '') {
        $sql = "SELECT * FROM quizzes WHERE 1=1"; 
        $params = [];
    
        if (!empty($search)) {
            $sql .= " AND (title LIKE :search OR description LIKE :search)";
            $params['search'] = "%$search%";
        }
    
        // Only filter by category if it's not empty
        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = :category";
            $params['category'] = $category;
        }
    
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->query($sql, $params)->fetchAll();
    }    
    
    // Fetch all unique quiz categories
    public function getQuizCategories() {
        $sql = "SELECT DISTINCT category FROM quizzes";
        $result = $this->db->query($sql)->fetchAll();
        
        // Extract categories into a simple array
        return array_column($result, 'category');
    }    
}
