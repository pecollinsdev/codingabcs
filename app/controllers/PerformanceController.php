<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

/**
 * PerformanceController handles the user's quiz performance.
 *
 * This controller is responsible for displaying the user's quiz performance
 * history, overall stats, and recent quiz stats. It interacts with the
 * UserAttempt model to fetch the necessary data.
 */
class PerformanceController extends Controller {
    // UserAttempt model instance
    private $userAttemptModel;

    // Constructor
    public function __construct() {
        Session::start(); // Ensure session is active
        $this->userAttemptModel = $this->model('UserAttemptModel'); // Load UserAttemptModel
    }

    // Display user's quiz performance
    public function index() {
        $userId = Session::get('user_id');
    
        if (!$userId) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
    
        $perPage = 10; // Adjust this value to control items per page
        $currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($currentPage - 1) * $perPage;
    
        // Get total attempts for pagination
        $totalAttempts = $this->userAttemptModel->getTotalAttempts($userId);
        $totalPages = ($totalAttempts > 0) ? ceil($totalAttempts / $perPage) : 1;
    
        // Fetch paginated quiz history
        $history = $this->userAttemptModel->getPaginatedHistory($userId, $perPage, $offset);
        
        // Fetch other performance stats
        $overallStats = $this->userAttemptModel->getUserOverallStats($userId);
        $recentStats = $this->userAttemptModel->getRecentQuizStats($userId);
    
        // Pass data to the view
        $this->view('stats/performance', [
            'history' => $history,
            'overallStats' => $overallStats,
            'recentStats' => $recentStats,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages
        ]);
    }
}    
?>
