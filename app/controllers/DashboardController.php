<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

/**
 * DashboardController handles the dashboard functionality.
 *
 * This controller is responsible for displaying the user dashboard
 * after successful login. It interacts with the UserAttempt and Quiz
 * models to fetch data for the dashboard.
 */
class DashboardController extends Controller {
    // Display the user dashboard
    public function index() {
        // Ensure the user is logged in
        if (!Session::has('user_id')) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $userId = Session::get('user_id');

        // Initialize models
        $userAttemptModel = $this->model('UserAttemptModel');
        $quizModel = $this->model('QuizModel');

        // Fetch data for the dashboard
        $recentQuizzes = $userAttemptModel->getRecentQuizStats($userId);
        $newQuizzes = $quizModel->getNewQuizzes();
        $popularQuizzes = $quizModel->getPopularQuizzes();

        // Pass data to the dashboard view
        $this->view('dashboard', [
            'username' => Session::get('username'),
            'recentQuizzes' => $recentQuizzes,
            'newQuizzes' => $newQuizzes,
            'popularQuizzes' => $popularQuizzes
        ]);
    }
}
