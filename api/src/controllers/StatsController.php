<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\QuizAttemptModel;
use App\Models\UserModel;

/**
 * Controller for retrieving user statistics
 * 
 * Provides summary statistics for the user's dashboard
 */
class StatsController extends Controller {
    /**
     * @var QuizAttemptModel Quiz attempt model instance
     */
    private QuizAttemptModel $attemptModel;
    
    /**
     * @var UserModel User model instance
     */
    private UserModel $userModel;

    /**
     * Constructor for the StatsController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request) {
        parent::__construct($request);
        $this->attemptModel = new QuizAttemptModel();
        $this->userModel = new UserModel();
    }

    /**
     * Get user's dashboard statistics
     * 
     * Endpoint: GET /api/stats
     * Access: Authentication required
     * Returns: User statistics including quizzes taken, average score,
     * current streak, and overall rank
     *
     * @return void Sends JSON response with user statistics
     */
    public function getStats(): void {
        try {
            $user = $this->request->getUser();
            if (!$user) {
                $this->respondError('Unauthorized', 401);
                return;
            }
            
            // Get user data
            $user = $this->userModel->findById($user['id']);
            if (!$user) {
                $this->respondError('User not found', 404);
                return;
            }

            // Get quizzes taken count and average score
            $stats = $this->attemptModel->getUserStats($user['id']);
            
            // Get current streak
            $streak = $this->attemptModel->getUserStreak($user['id']);
            
            // Get user rank
            $rank = $this->attemptModel->getUserRank($user['id']) ?? ['rank' => 0];

            $this->respond([
                'status' => 'success',
                'data' => [
                    'name' => $user['email'],
                    'quizzes_taken' => (int)$stats['quizzes_taken'],
                    'average_score' => round($stats['average_score'] ?? 0, 1),
                    'current_streak' => (int)$streak['days'],
                    'rank' => (int)$rank['rank']
                ]
            ]);
        } catch (\Exception $e) {
            $this->respondError('Failed to get user stats', 500);
        }
    }
}