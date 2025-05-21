<?php

namespace App\Controllers;

use App\Models\AchievementModel;
use App\Core\Controller;
use App\Core\Request;

/**
 * Controller for managing user achievements
 */
class AchievementController extends Controller {
    /**
     * @var AchievementModel Achievement model instance
     */
    private $achievementModel;

    /**
     * Constructor for the AchievementController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request) {
        parent::__construct($request);
        $this->achievementModel = new AchievementModel();
    }

    /**
     * Get the current authenticated user's ID from the session
     *
     * @return int|null The user ID if authenticated, null otherwise
     */
    private function getCurrentUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Retrieve all achievements for the current authenticated user
     *
     * @return void Sends JSON response
     */
    public function getUserAchievements() {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->respond([
                'status' => 'error',
                'message' => 'User not authenticated'
            ], 401);
            return;
        }

        $achievements = $this->achievementModel->getUserAchievements($userId);
        
        $this->respond([
            'status' => 'success',
            'data' => $achievements
        ]);
    }

    /**
     * Get achievement statistics for the current authenticated user
     * Including total, unlocked, locked, and recent achievements
     *
     * @return void Sends JSON response
     */
    public function getAchievementStats() {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->respond([
                'status' => 'error',
                'message' => 'User not authenticated'
            ], 401);
            return;
        }

        $achievements = $this->achievementModel->getUserAchievements($userId);
        
        $stats = [
            'total' => count($achievements),
            'unlocked' => count(array_filter($achievements, fn($a) => $a['unlocked'])),
            'locked' => count(array_filter($achievements, fn($a) => !$a['unlocked'])),
            'recent' => array_slice(array_filter($achievements, fn($a) => $a['unlocked']), 0, 5)
        ];

        $this->respond([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Get recently unlocked achievements for the current authenticated user
     * Limited to the 5 most recently unlocked achievements
     *
     * @return void Sends JSON response
     */
    public function getRecentAchievements() {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->respond([
                'status' => 'error',
                'message' => 'User not authenticated'
            ], 401);
            return;
        }

        $achievements = $this->achievementModel->getUserAchievements($userId);
        $unlocked = array_filter($achievements, fn($a) => $a['unlocked']);
        usort($unlocked, fn($a, $b) => strtotime($b['unlocked_at']) - strtotime($a['unlocked_at']));
        
        $this->respond([
            'status' => 'success',
            'data' => array_slice($unlocked, 0, 5)
        ]);
    }
}